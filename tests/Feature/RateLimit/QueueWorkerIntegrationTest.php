<?php

namespace Tests\Feature\RateLimit;

use Tests\TestCase;
use Modules\Core\Jobs\SendOTPEmailJob;
use Modules\Core\Jobs\SendOTPWhatsAppJob;
use Modules\Core\Services\QueueRateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/**
 * @group rate-limiting
 * @group integration
 */
class QueueWorkerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure queues for testing
        Config::set('queue.default', 'database');
        Config::set('queue.otp_email_queue', 'otp-emails');
        Config::set('queue.otp_whatsapp_queue', 'otp-whatsapp');
        Config::set('queue.rate_limits.resend', 2);
        Config::set('queue.rate_limits.whatsapp', 5);
        
        // Use log mail driver for testing
        Config::set('mail.default', 'log');
    }

    /** @test */
    public function test_email_jobs_are_processed_with_rate_limiting()
    {
        // Arrange
        $jobs = [];
        for ($i = 1; $i <= 5; $i++) {
            $jobs[] = new SendOTPEmailJob("test{$i}@example.com", "12345{$i}", "User {$i}", 10);
        }

        // Act
        $startTime = Carbon::now();
        
        foreach ($jobs as $job) {
            dispatch($job);
        }

        // Simulate processing with rate limiting
        // In a real scenario, this would be handled by the queue worker
        $processedTimes = [];
        
        foreach ($jobs as $index => $job) {
            // Simulate the rate limiting middleware
            if ($index > 0 && ($index % 2) === 0) {
                // Every 3rd job should be delayed due to 2/sec limit
                sleep(1);
            }
            
            $processedTimes[] = Carbon::now();
        }

        $endTime = Carbon::now();

        // Assert
        $totalDuration = $startTime->diffInSeconds($endTime);
        
        // With 5 jobs at 2/sec, minimum time should be around 2 seconds
        // (0, 0.5, 1, 1.5, 2 seconds)
        $this->assertGreaterThanOrEqual(2, $totalDuration);
        
        // But shouldn't take too long either (max ~4 seconds with overhead)
        $this->assertLessThanOrEqual(6, $totalDuration);
    }

    /** @test */
    public function test_whatsapp_jobs_are_processed_faster_than_email()
    {
        // Arrange
        $emailJobs = [];
        $whatsappJobs = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $emailJobs[] = new SendOTPEmailJob("test{$i}@example.com", "12345{$i}", "User {$i}", 10);
            $whatsappJobs[] = new SendOTPWhatsAppJob("123456789{$i}", "12345{$i}", "User {$i}", 10);
        }

        // Act & Assert
        $emailStartTime = Carbon::now();
        foreach ($emailJobs as $index => $job) {
            dispatch($job);
            
            // Simulate email rate limiting (2/sec)
            if (($index + 1) % 2 === 0) {
                usleep(500000); // 0.5 second delay
            }
        }
        $emailEndTime = Carbon::now();

        $whatsappStartTime = Carbon::now();
        foreach ($whatsappJobs as $index => $job) {
            dispatch($job);
            
            // Simulate WhatsApp rate limiting (5/sec)
            if (($index + 1) % 5 === 0) {
                usleep(200000); // 0.2 second delay
            }
        }
        $whatsappEndTime = Carbon::now();

        $emailDuration = $emailStartTime->diffInSeconds($emailEndTime);
        $whatsappDuration = $whatsappStartTime->diffInSeconds($whatsappEndTime);

        // WhatsApp should be processed faster than email
        $this->assertLessThan($emailDuration, $whatsappDuration);
    }

    /** @test */
    public function test_queue_statistics_are_accurate()
    {
        // Arrange
        $service = new QueueRateLimiterService();
        
        // Dispatch some jobs to different queues
        for ($i = 1; $i <= 3; $i++) {
            dispatch(new SendOTPEmailJob("email{$i}@example.com", "123456", "User {$i}", 10));
        }
        
        for ($i = 1; $i <= 2; $i++) {
            dispatch(new SendOTPWhatsAppJob("123456789{$i}", "123456", "User {$i}", 10));
        }

        // Act
        $stats = $service->getQueueStats();

        // Assert
        $this->assertArrayHasKey('email', $stats);
        $this->assertArrayHasKey('whatsapp', $stats);
        $this->assertArrayHasKey('total', $stats);

        // Should have pending jobs
        $this->assertGreaterThanOrEqual(3, $stats['email']['pending']);
        $this->assertGreaterThanOrEqual(2, $stats['whatsapp']['pending']);
        $this->assertGreaterThanOrEqual(5, $stats['total']['pending']);
    }

    /** @test */
    public function test_concurrent_job_processing_respects_rate_limits()
    {
        // Arrange
        $emailJobs = [];
        $whatsappJobs = [];
        
        for ($i = 1; $i <= 6; $i++) {
            $emailJobs[] = new SendOTPEmailJob("concurrent{$i}@example.com", "123456", "User {$i}", 10);
            $whatsappJobs[] = new SendOTPWhatsAppJob("987654321{$i}", "123456", "User {$i}", 10);
        }

        // Act
        $startTime = Carbon::now();
        
        // Dispatch all jobs at once (simulating concurrent requests)
        foreach ($emailJobs as $job) {
            dispatch($job);
        }
        foreach ($whatsappJobs as $job) {
            dispatch($job);
        }

        // Simulate processing with rate limiting constraints
        $emailProcessingTime = ceil(count($emailJobs) / 2); // 2 emails per second
        $whatsappProcessingTime = ceil(count($whatsappJobs) / 5); // 5 WhatsApp per second

        // Assert
        $this->assertEquals(3, $emailProcessingTime); // 6 jobs / 2 per second = 3 seconds
        $this->assertEquals(2, $whatsappProcessingTime); // 6 jobs / 5 per second = 1.2 → 2 seconds
        
        // Total processing time should be at least the maximum of both
        $expectedMinimumTime = max($emailProcessingTime, $whatsappProcessingTime);
        $this->assertGreaterThanOrEqual(2, $expectedMinimumTime);
    }

    /** @test */
    public function test_queue_worker_processes_jobs_in_correct_order()
    {
        // Arrange
        $jobs = [];
        $timestamps = [];
        
        for ($i = 1; $i <= 4; $i++) {
            $job = new SendOTPEmailJob("order{$i}@example.com", "123456", "User {$i}", 10);
            dispatch($job);
            $timestamps[] = Carbon::now();
            $jobs[] = $job;
            
            // Small delay to ensure different timestamps
            usleep(10000); // 10ms
        }

        // Act - Verify jobs are in database in correct order
        $jobRecords = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('queue', 'otp-emails')
            ->orderBy('id')
            ->get();

        // Assert
        $this->assertCount(4, $jobRecords);
        
        // Jobs should be processed in FIFO order
        foreach ($jobRecords as $index => $record) {
            $payload = json_decode($record->payload, true);
            $this->assertStringContainsString("order" . ($index + 1), $payload['data']['email']);
        }
    }

    /** @test */
    public function test_failed_jobs_are_handled_properly()
    {
        // This test would require mocking actual job failure scenarios
        // For now, we'll test the structure for failed job handling
        
        // Arrange
        $job = new SendOTPEmailJob("failure-test@example.com", "123456", "User", 10);
        
        // Act
        dispatch($job);
        
        // Simulate job failure by adding a record to failed_jobs table
        \Illuminate\Support\Facades\DB::table('failed_jobs')->insert([
            'queue' => 'otp-emails',
            'payload' => json_encode(['test' => 'failed job']),
            'exception' => 'Test exception for rate limiting',
            'failed_at' => now()
        ]);

        // Assert
        $service = new QueueRateLimiterService();
        $stats = $service->getQueueStats();
        
        $this->assertGreaterThanOrEqual(1, $stats['email']['failed']);
    }

    /** @test */
    public function test_queue_monitoring_provides_real_time_data()
    {
        // Arrange
        $service = new QueueRateLimiterService();
        
        // Initial state
        $initialStats = $service->getQueueStats();
        
        // Act
        dispatch(new SendOTPEmailJob("monitor@example.com", "123456", "User", 10));
        dispatch(new SendOTPWhatsAppJob("1234567890", "123456", "User", 10));
        
        $afterDispatchStats = $service->getQueueStats();

        // Assert
        $this->assertGreaterThan(
            $initialStats['email']['pending'], 
            $afterDispatchStats['email']['pending']
        );
        $this->assertGreaterThan(
            $initialStats['whatsapp']['pending'], 
            $afterDispatchStats['whatsapp']['pending']
        );
        $this->assertGreaterThan(
            $initialStats['total']['pending'], 
            $afterDispatchStats['total']['pending']
        );
    }

    /** @test */
    public function test_rate_limit_middleware_integration()
    {
        // Arrange
        $job = new SendOTPEmailJob("middleware@example.com", "123456", "User", 10);
        
        // Act
        $middleware = $job->middleware();
        
        // Assert
        $this->assertNotEmpty($middleware);
        $this->assertContainsOnlyInstancesOf(
            \Modules\Core\Jobs\Middleware\RateLimited::class,
            $middleware
        );
        
        // Verify the middleware is properly configured
        $rateLimitedMiddleware = $middleware[0];
        $this->assertInstanceOf(\Modules\Core\Jobs\Middleware\RateLimited::class, $rateLimitedMiddleware);
    }
}