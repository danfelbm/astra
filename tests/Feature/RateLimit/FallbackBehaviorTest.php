<?php

namespace Tests\Feature\RateLimit;

use Tests\TestCase;
use Modules\Core\Jobs\SendOTPEmailJob;
use Modules\Core\Services\QueueRateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * @group rate-limiting
 * @group fallback
 */
class FallbackBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure for testing
        Config::set('queue.default', 'database');
        Config::set('queue.otp_email_queue', 'otp-emails');
        Config::set('queue.rate_limits.resend', 2);
        Config::set('mail.default', 'log');
    }

    /** @test */
    public function test_system_works_without_redis_in_local_environment()
    {
        // Arrange
        Config::set('app.env', 'local');
        
        // Mock Redis to throw connection exception
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Connection refused'));

        Log::shouldReceive('info')
            ->with('Redis no disponible en local, ejecutando job sin rate limiting')
            ->once();

        $service = new QueueRateLimiterService();

        // Act
        $result = $service->throttle('test-key', 2, 1);

        // Assert
        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']);
        $this->assertEquals(0, $result['retryAfter']);
    }

    /** @test */
    public function test_system_fails_without_redis_in_production()
    {
        // Arrange
        Config::set('app.env', 'production');
        
        // Mock Redis to throw connection exception
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Connection refused'));

        $service = new QueueRateLimiterService();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Connection refused');
        
        $service->throttle('test-key', 2, 1);
    }

    /** @test */
    public function test_jobs_execute_without_rate_limiting_when_redis_unavailable_locally()
    {
        // Arrange
        Config::set('app.env', 'local');
        
        // Mock Redis to be unavailable
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Redis connection failed'));

        Log::shouldReceive('info')
            ->with('Redis no disponible en local, ejecutando job sin rate limiting')
            ->atLeast()->once();

        // Queue job normally
        Queue::fake();
        $job = new SendOTPEmailJob('test@example.com', '123456', 'Test User', 10);

        // Act
        dispatch($job);

        // Assert
        Queue::assertPushed(SendOTPEmailJob::class);
        
        // The job should be processed without rate limiting
        // (in real scenario, it would execute immediately)
    }

    /** @test */
    public function test_queue_stats_work_without_redis()
    {
        // Arrange
        Config::set('app.env', 'local');
        
        // Mock Redis to be unavailable
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Redis unavailable'));

        $service = new QueueRateLimiterService();
        
        // Add some jobs to database queue
        dispatch(new SendOTPEmailJob('test1@example.com', '123456', 'User 1', 10));
        dispatch(new SendOTPEmailJob('test2@example.com', '123456', 'User 2', 10));

        // Act
        $stats = $service->getQueueStats();

        // Assert
        $this->assertArrayHasKey('email', $stats);
        $this->assertArrayHasKey('whatsapp', $stats);
        $this->assertArrayHasKey('total', $stats);
        
        // Should have jobs even without Redis
        $this->assertGreaterThanOrEqual(2, $stats['email']['pending']);
        
        // Throttle status should indicate Redis unavailable but system functional
        $this->assertArrayHasKey('throttle_status', $stats['email']);
    }

    /** @test */
    public function test_is_redis_available_detects_connection_properly()
    {
        // Arrange
        $service = new QueueRateLimiterService();

        // Test when Redis is available
        Redis::shouldReceive('connection')
            ->once()
            ->andReturnSelf();
        Redis::shouldReceive('ping')
            ->once()
            ->andReturn('PONG');

        // Act & Assert
        $this->assertTrue($service->isRedisAvailable());

        // Test when Redis is not available
        Redis::shouldReceive('connection')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        // Act & Assert
        $this->assertFalse($service->isRedisAvailable());
    }

    /** @test */
    public function test_graceful_degradation_maintains_functionality()
    {
        // Arrange
        Config::set('app.env', 'local');
        
        // Simulate Redis being unavailable
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Redis service down'));

        Log::shouldReceive('info')->atLeast()->once();

        $service = new QueueRateLimiterService();

        // Act
        $emailResult = $service->throttle('email-key', 2, 1);
        $whatsappResult = $service->throttle('whatsapp-key', 5, 1);

        // Assert
        // Both should be allowed without Redis in local environment
        $this->assertTrue($emailResult['allowed']);
        $this->assertTrue($whatsappResult['allowed']);
        
        // Should have expected limits
        $this->assertEquals(2, $emailResult['remaining']);
        $this->assertEquals(5, $whatsappResult['remaining']);
    }

    /** @test */
    public function test_redis_recovery_after_initial_failure()
    {
        // Arrange
        Config::set('app.env', 'local');
        $service = new QueueRateLimiterService();

        // First call - Redis fails
        Redis::shouldReceive('connection')
            ->once()
            ->andThrow(new \Exception('Temporary Redis failure'));

        Log::shouldReceive('info')
            ->with('Redis no disponible en local, ejecutando job sin rate limiting')
            ->once();

        // Act - First call
        $firstResult = $service->throttle('test-key', 2, 1);

        // Assert - Should work without Redis
        $this->assertTrue($firstResult['allowed']);

        // Second call - Redis is back
        Redis::shouldReceive('throttle')
            ->with('test-key')
            ->andReturnSelf();
        Redis::shouldReceive('allow')
            ->with(2)
            ->andReturnSelf();
        Redis::shouldReceive('every')
            ->with(1)
            ->andReturn([
                'allowed' => true,
                'remaining' => 1,
                'retryAfter' => 0,
                'limit' => 2
            ]);

        // Act - Second call
        $secondResult = $service->throttle('test-key', 2, 1);

        // Assert - Should work with Redis
        $this->assertTrue($secondResult['allowed']);
        $this->assertEquals(1, $secondResult['remaining']);
    }

    /** @test */
    public function test_fallback_logging_provides_visibility()
    {
        // Arrange
        Config::set('app.env', 'local');
        
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Redis connection failed'));

        // Expect specific log messages
        Log::shouldReceive('info')
            ->with('Redis no disponible en local, ejecutando job sin rate limiting')
            ->times(3);

        $service = new QueueRateLimiterService();

        // Act
        $service->throttle('key1', 2, 1);
        $service->throttle('key2', 5, 1);
        $service->throttle('key3', 3, 1);

        // Assert - Verified by Log expectations
    }

    /** @test */
    public function test_database_queue_continues_working_without_redis()
    {
        // Arrange
        Config::set('app.env', 'local');
        
        // Simulate Redis being down
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Redis service unavailable'));

        Log::shouldReceive('info')->atLeast()->once();

        // Act
        $job1 = new SendOTPEmailJob('user1@example.com', '123456', 'User 1', 10);
        $job2 = new SendOTPEmailJob('user2@example.com', '654321', 'User 2', 10);
        
        dispatch($job1);
        dispatch($job2);

        // Assert
        $this->assertDatabaseHas('jobs', [
            'queue' => 'otp-emails'
        ]);
        
        $jobCount = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('queue', 'otp-emails')
            ->count();
            
        $this->assertEquals(2, $jobCount);
    }
}