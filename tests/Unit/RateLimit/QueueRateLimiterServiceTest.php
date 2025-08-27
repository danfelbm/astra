<?php

namespace Tests\Unit\RateLimit;

use Tests\TestCase;
use App\Services\Core\QueueRateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * @group rate-limiting
 */
class QueueRateLimiterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QueueRateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QueueRateLimiterService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_throttle_allows_requests_within_limit()
    {
        // Arrange
        Redis::shouldReceive('throttle')
            ->with('test-key')
            ->andReturnSelf();
        Redis::shouldReceive('allow')
            ->with(5)
            ->andReturnSelf();
        Redis::shouldReceive('every')
            ->with(1)
            ->andReturn([
                'allowed' => true,
                'remaining' => 4,
                'retryAfter' => 0,
                'limit' => 5
            ]);

        // Act
        $result = $this->service->throttle('test-key', 5, 1);

        // Assert
        $this->assertTrue($result['allowed']);
        $this->assertEquals(4, $result['remaining']);
    }

    /** @test */
    public function test_throttle_blocks_requests_over_limit()
    {
        // Arrange
        Redis::shouldReceive('throttle')
            ->with('test-key')
            ->andReturnSelf();
        Redis::shouldReceive('allow')
            ->with(2)
            ->andReturnSelf();
        Redis::shouldReceive('every')
            ->with(1)
            ->andReturn([
                'allowed' => false,
                'remaining' => 0,
                'retryAfter' => 0.5,
                'limit' => 2
            ]);

        // Act
        $result = $this->service->throttle('test-key', 2, 1);

        // Assert
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertEquals(0.5, $result['retryAfter']);
    }

    /** @test */
    public function test_throttle_with_redis_unavailable_in_local()
    {
        // Arrange
        Config::set('app.env', 'local');
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Connection refused'));

        // Act
        $result = $this->service->throttle('test-key', 2, 1);

        // Assert
        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']);
        $this->assertEquals(0, $result['retryAfter']);
    }

    /** @test */
    public function test_throttle_fails_in_production_without_redis()
    {
        // Arrange
        Config::set('app.env', 'production');
        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Connection refused'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->throttle('test-key', 2, 1);
    }

    /** @test */
    public function test_get_queue_stats_returns_correct_structure()
    {
        // Arrange
        DB::table('jobs')->insert([
            ['queue' => 'otp-emails', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp],
            ['queue' => 'otp-emails', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => now()->timestamp, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp],
            ['queue' => 'otp-whatsapp', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp],
        ]);

        DB::table('failed_jobs')->insert([
            ['queue' => 'otp-emails', 'payload' => '{}', 'exception' => 'Test error', 'failed_at' => now()],
        ]);

        // Mock Redis throttle status
        Redis::shouldReceive('throttle')->andReturnSelf();
        Redis::shouldReceive('allow')->andReturnSelf();
        Redis::shouldReceive('every')->andReturn([
            'allowed' => true,
            'remaining' => 2,
            'retryAfter' => 0,
            'limit' => 2
        ]);

        // Act
        $stats = $this->service->getQueueStats();

        // Assert
        $this->assertArrayHasKey('email', $stats);
        $this->assertArrayHasKey('whatsapp', $stats);
        $this->assertArrayHasKey('total', $stats);

        $this->assertEquals(2, $stats['email']['pending']);
        $this->assertEquals(1, $stats['email']['processing']);
        $this->assertEquals(1, $stats['email']['failed']);
        $this->assertEquals(2, $stats['email']['rate_limit']);

        $this->assertEquals(1, $stats['whatsapp']['pending']);
        $this->assertEquals(0, $stats['whatsapp']['processing']);
        $this->assertEquals(5, $stats['whatsapp']['rate_limit']);
    }

    /** @test */
    public function test_get_metrics_returns_historical_data()
    {
        // Arrange - Create some historical data
        DB::table('otp_queue_metrics')->insert([
            [
                'channel' => 'email',
                'status' => 'sent',
                'queued_at' => now()->subHour(),
                'processed_at' => now()->subHour()->addMinute(),
                'attempts' => 1,
                'delay_seconds' => 60,
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ],
            [
                'channel' => 'whatsapp',
                'status' => 'throttled',
                'queued_at' => now()->subMinutes(30),
                'processed_at' => now()->subMinutes(25),
                'attempts' => 2,
                'delay_seconds' => 300,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(25),
            ]
        ]);

        // Act
        $metrics = $this->service->getMetrics();

        // Assert
        $this->assertArrayHasKey('email', $metrics);
        $this->assertArrayHasKey('whatsapp', $metrics);
        $this->assertNotEmpty($metrics['email']);
        $this->assertNotEmpty($metrics['whatsapp']);
    }

    /** @test */
    public function test_calculate_jitter_returns_valid_range()
    {
        // Act
        $jitter1 = $this->service->calculateJitter(100);
        $jitter2 = $this->service->calculateJitter(100);

        // Assert
        $this->assertGreaterThanOrEqual(80, $jitter1); // 80% of 100
        $this->assertLessThanOrEqual(120, $jitter1);   // 120% of 100
        $this->assertNotEquals($jitter1, $jitter2);    // Should be random
    }

    /** @test */
    public function test_is_redis_available_detects_connection()
    {
        // Test when Redis is available
        Redis::shouldReceive('connection')
            ->once()
            ->andReturnSelf();
        Redis::shouldReceive('ping')
            ->once()
            ->andReturn('PONG');

        $this->assertTrue($this->service->isRedisAvailable());

        // Test when Redis is not available
        Redis::shouldReceive('connection')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $this->assertFalse($this->service->isRedisAvailable());
    }
}