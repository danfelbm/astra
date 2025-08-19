<?php

namespace Tests\Unit\RateLimit;

use Tests\TestCase;
use App\Jobs\Middleware\RateLimited;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * @group rate-limiting
 */
class RateLimitedMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_middleware_allows_job_when_under_rate_limit()
    {
        // Arrange
        $middleware = RateLimited::forEmail();
        $job = Mockery::mock('job');
        $executed = false;
        
        $next = function ($passedJob) use (&$executed) {
            $executed = true;
            return $passedJob;
        };

        Redis::shouldReceive('throttle')
            ->with('rate_limit:resend')
            ->andReturnSelf();
        Redis::shouldReceive('allow')
            ->with(2)
            ->andReturnSelf();
        Redis::shouldReceive('every')
            ->with(1)
            ->andReturn(function() use ($next, $job) {
                return $next($job);
            });

        // Act
        $middleware->handle($job, $next);

        // Assert
        $this->assertTrue($executed);
    }

    /** @test */
    public function test_middleware_releases_job_when_rate_limited()
    {
        // Arrange
        $middleware = RateLimited::forEmail();
        $job = Mockery::mock('job');
        $job->shouldReceive('release')
            ->once()
            ->with(Mockery::type('int')); // delay in seconds

        $next = function ($passedJob) {
            // This should not be called
            throw new \Exception('Job should not have been executed');
        };

        Redis::shouldReceive('throttle')
            ->with('rate_limit:resend')
            ->andReturnSelf();
        Redis::shouldReceive('allow')
            ->with(2)
            ->andReturnSelf();
        Redis::shouldReceive('every')
            ->with(1)
            ->andReturn(function() {
                throw new \Illuminate\Http\Exceptions\ThrottleRequestsException();
            });

        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::pattern('/Rate limit alcanzado/'));

        // Act
        $middleware->handle($job, $next);

        // Assert - Job release was called (verified by Mockery)
    }

    /** @test */
    public function test_middleware_applies_jitter_to_release_delay()
    {
        // Arrange
        $middleware = RateLimited::forWhatsApp();
        $job = Mockery::mock('job');
        
        $releaseDelays = [];
        $job->shouldReceive('release')
            ->times(3)
            ->with(Mockery::on(function($delay) use (&$releaseDelays) {
                $releaseDelays[] = $delay;
                return true;
            }));

        $next = function ($passedJob) {
            throw new \Exception('Job should not have been executed');
        };

        Redis::shouldReceive('throttle')->andReturnSelf();
        Redis::shouldReceive('allow')->andReturnSelf();
        Redis::shouldReceive('every')->andReturn(function() {
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException();
        });

        Log::shouldReceive('info')->times(3);

        // Act - Execute multiple times to test jitter
        for ($i = 0; $i < 3; $i++) {
            $middleware->handle($job, $next);
        }

        // Assert - Delays should be different due to jitter
        $this->assertNotEmpty($releaseDelays);
        $this->assertCount(3, $releaseDelays);
        
        // All delays should be between 0.8 and 1.2 seconds (jitter range)
        foreach ($releaseDelays as $delay) {
            $this->assertGreaterThanOrEqual(0.8, $delay);
            $this->assertLessThanOrEqual(1.2, $delay);
        }
    }

    /** @test */
    public function test_middleware_uses_correct_limits_for_email()
    {
        // Arrange
        Config::set('queue.rate_limits.resend', 3);
        $middleware = RateLimited::forEmail();
        
        Redis::shouldReceive('throttle')
            ->with('rate_limit:resend')
            ->andReturnSelf();
        Redis::shouldReceive('allow')
            ->with(3) // Should use the configured limit
            ->andReturnSelf();
        Redis::shouldReceive('every')
            ->with(1)
            ->andReturn(function() {
                return 'success';
            });

        $job = Mockery::mock('job');
        $next = function ($passedJob) { return $passedJob; };

        // Act
        $middleware->handle($job, $next);

        // Assert - Verified by Mockery expectations
    }

    /** @test */
    public function test_middleware_uses_correct_limits_for_whatsapp()
    {
        // Arrange
        Config::set('queue.rate_limits.whatsapp', 7);
        $middleware = RateLimited::forWhatsApp();
        
        Redis::shouldReceive('throttle')
            ->with('rate_limit:whatsapp')
            ->andReturnSelf();
        Redis::shouldReceive('allow')
            ->with(7) // Should use the configured limit
            ->andReturnSelf();
        Redis::shouldReceive('every')
            ->with(1)
            ->andReturn(function() {
                return 'success';
            });

        $job = Mockery::mock('job');
        $next = function ($passedJob) { return $passedJob; };

        // Act
        $middleware->handle($job, $next);

        // Assert - Verified by Mockery expectations
    }

    /** @test */
    public function test_middleware_bypasses_rate_limiting_in_local_without_redis()
    {
        // Arrange
        Config::set('app.env', 'local');
        $middleware = RateLimited::forEmail();
        $job = Mockery::mock('job');
        $executed = false;
        
        $next = function ($passedJob) use (&$executed) {
            $executed = true;
            return $passedJob;
        };

        Redis::shouldReceive('connection')
            ->andThrow(new \Exception('Redis connection failed'));

        Log::shouldReceive('info')
            ->once()
            ->with('Redis no disponible en local, ejecutando job sin rate limiting');

        // Act
        $middleware->handle($job, $next);

        // Assert
        $this->assertTrue($executed);
    }

    /** @test */
    public function test_middleware_factory_methods_create_correct_instances()
    {
        // Act
        $emailMiddleware = RateLimited::forEmail();
        $whatsappMiddleware = RateLimited::forWhatsApp();

        // Assert
        $this->assertInstanceOf(RateLimited::class, $emailMiddleware);
        $this->assertInstanceOf(RateLimited::class, $whatsappMiddleware);
        
        // Use reflection to verify internal properties
        $emailReflection = new \ReflectionClass($emailMiddleware);
        $whatsappReflection = new \ReflectionClass($whatsappMiddleware);
        
        $emailKey = $emailReflection->getProperty('rateLimitKey');
        $emailKey->setAccessible(true);
        $emailMaxAttempts = $emailReflection->getProperty('maxAttempts');
        $emailMaxAttempts->setAccessible(true);
        
        $whatsappKey = $whatsappReflection->getProperty('rateLimitKey');
        $whatsappKey->setAccessible(true);
        $whatsappMaxAttempts = $whatsappReflection->getProperty('maxAttempts');
        $whatsappMaxAttempts->setAccessible(true);
        
        $this->assertEquals('rate_limit:resend', $emailKey->getValue($emailMiddleware));
        $this->assertEquals('rate_limit:whatsapp', $whatsappKey->getValue($whatsappMiddleware));
        $this->assertEquals(2, $emailMaxAttempts->getValue($emailMiddleware));
        $this->assertEquals(5, $whatsappMaxAttempts->getValue($whatsappMiddleware));
    }
}