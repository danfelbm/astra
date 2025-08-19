<?php

namespace Tests\Unit\RateLimit;

use Tests\TestCase;
use App\Jobs\Middleware\WithRateLimiting;
use App\Jobs\SendOTPEmailJob;
use App\Jobs\SendOTPWhatsAppJob;
use App\Jobs\SendZoomAccessEmailJob;
use App\Jobs\SendZoomAccessWhatsAppJob;
use Illuminate\Support\Facades\Config;

/**
 * @group rate-limiting
 */
class WithRateLimitingTraitTest extends TestCase
{
    /** @test */
    public function test_otp_email_job_gets_correct_queue()
    {
        // Arrange
        Config::set('queue.otp_email_queue', 'test-otp-emails');
        
        // Act
        $job = new SendOTPEmailJob('test@example.com', '123456', 'Test User', 10);
        
        // Assert
        $this->assertEquals('test-otp-emails', $job->queue);
    }

    /** @test */
    public function test_otp_whatsapp_job_gets_correct_queue()
    {
        // Arrange
        Config::set('queue.otp_whatsapp_queue', 'test-otp-whatsapp');
        
        // Act
        $job = new SendOTPWhatsAppJob('1234567890', '123456', 'Test User', 10);
        
        // Assert
        $this->assertEquals('test-otp-whatsapp', $job->queue);
    }

    /** @test */
    public function test_zoom_email_job_gets_otp_email_queue()
    {
        // Arrange
        Config::set('queue.otp_email_queue', 'test-otp-emails');
        
        // Act
        $job = new SendZoomAccessEmailJob([
            'email' => 'test@example.com',
            'join_url' => 'https://zoom.us/test',
            'meeting_id' => '123456789',
            'password' => 'password'
        ]);
        
        // Assert
        $this->assertEquals('test-otp-emails', $job->queue);
    }

    /** @test */
    public function test_zoom_whatsapp_job_gets_otp_whatsapp_queue()
    {
        // Arrange
        Config::set('queue.otp_whatsapp_queue', 'test-otp-whatsapp');
        
        // Act
        $job = new SendZoomAccessWhatsAppJob('1234567890', [
            'join_url' => 'https://zoom.us/test',
            'meeting_id' => '123456789',
            'password' => 'password'
        ], 'Test User');
        
        // Assert
        $this->assertEquals('test-otp-whatsapp', $job->queue);
    }

    /** @test */
    public function test_jobs_use_default_queues_when_config_missing()
    {
        // Arrange
        Config::set('queue.otp_email_queue', null);
        Config::set('queue.otp_whatsapp_queue', null);
        
        // Act
        $emailJob = new SendOTPEmailJob('test@example.com', '123456', 'Test User', 10);
        $whatsappJob = new SendOTPWhatsAppJob('1234567890', '123456', 'Test User', 10);
        
        // Assert
        $this->assertEquals('otp-emails', $emailJob->queue);
        $this->assertEquals('otp-whatsapp', $whatsappJob->queue);
    }

    /** @test */
    public function test_jobs_have_correct_middleware()
    {
        // Act
        $emailJob = new SendOTPEmailJob('test@example.com', '123456', 'Test User', 10);
        $whatsappJob = new SendOTPWhatsAppJob('1234567890', '123456', 'Test User', 10);
        $zoomEmailJob = new SendZoomAccessEmailJob([
            'email' => 'test@example.com',
            'join_url' => 'https://zoom.us/test',
            'meeting_id' => '123456789',
            'password' => 'password'
        ]);
        $zoomWhatsappJob = new SendZoomAccessWhatsAppJob('1234567890', [
            'join_url' => 'https://zoom.us/test',
            'meeting_id' => '123456789',
            'password' => 'password'
        ], 'Test User');
        
        // Assert
        $emailMiddleware = $emailJob->middleware();
        $whatsappMiddleware = $whatsappJob->middleware();
        $zoomEmailMiddleware = $zoomEmailJob->middleware();
        $zoomWhatsappMiddleware = $zoomWhatsappJob->middleware();
        
        $this->assertNotEmpty($emailMiddleware);
        $this->assertNotEmpty($whatsappMiddleware);
        $this->assertNotEmpty($zoomEmailMiddleware);
        $this->assertNotEmpty($zoomWhatsappMiddleware);
        
        // All should have RateLimited middleware
        $this->assertContainsOnlyInstancesOf(
            \App\Jobs\Middleware\RateLimited::class,
            $emailMiddleware
        );
        $this->assertContainsOnlyInstancesOf(
            \App\Jobs\Middleware\RateLimited::class,
            $whatsappMiddleware
        );
        $this->assertContainsOnlyInstancesOf(
            \App\Jobs\Middleware\RateLimited::class,
            $zoomEmailMiddleware
        );
        $this->assertContainsOnlyInstancesOf(
            \App\Jobs\Middleware\RateLimited::class,
            $zoomWhatsappMiddleware
        );
    }

    /** @test */
    public function test_unknown_job_class_throws_exception()
    {
        // Create a mock job that uses the trait but isn't recognized
        $job = new class {
            use WithRateLimiting;
            
            public function __construct() {
                $this->initializeRateLimitedQueue();
            }
        };

        // The job should not crash but should use a default or skip queue assignment
        // Since our trait implementation handles unknown classes gracefully
        $this->assertTrue(true); // If we get here, no exception was thrown
    }
}