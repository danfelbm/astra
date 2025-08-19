<?php

namespace Tests\Feature\RateLimit;

use Tests\TestCase;
use App\Services\OTPService;
use App\Jobs\SendOTPEmailJob;
use App\Jobs\SendOTPWhatsAppJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * @group rate-limiting
 */
class OTPRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure queue for testing
        Config::set('queue.default', 'database');
        Config::set('queue.otp_email_queue', 'otp-emails');
        Config::set('queue.otp_whatsapp_queue', 'otp-whatsapp');
        Config::set('queue.rate_limits.resend', 2);
        Config::set('queue.rate_limits.whatsapp', 5);
        
        // Use log mail driver for testing
        Config::set('mail.default', 'log');
        
        // Clear any existing jobs
        Queue::fake();
    }

    /** @test */
    public function test_otp_email_jobs_go_to_correct_queue()
    {
        // Arrange
        $otpService = new OTPService();
        
        // Act
        $otpService->generateOTP('test@example.com');
        
        // Assert
        Queue::assertPushedOn('otp-emails', SendOTPEmailJob::class);
    }

    /** @test */
    public function test_otp_whatsapp_jobs_go_to_correct_queue()
    {
        // Arrange
        Config::set('services.whatsapp.enabled', true);
        Config::set('services.otp.channel', 'whatsapp');
        $otpService = new OTPService();
        
        // Act
        $otpService->generateOTP('test@example.com', '1234567890');
        
        // Assert
        Queue::assertPushedOn('otp-whatsapp', SendOTPWhatsAppJob::class);
    }

    /** @test */
    public function test_both_channels_dispatch_to_correct_queues()
    {
        // Arrange
        Config::set('services.whatsapp.enabled', true);
        Config::set('services.otp.channel', 'both');
        $otpService = new OTPService();
        
        // Act
        $otpService->generateOTP('test@example.com', '1234567890');
        
        // Assert
        Queue::assertPushedOn('otp-emails', SendOTPEmailJob::class);
        Queue::assertPushedOn('otp-whatsapp', SendOTPWhatsAppJob::class);
    }

    /** @test */
    public function test_multiple_otp_requests_are_queued_properly()
    {
        // Arrange
        $otpService = new OTPService();
        $emails = [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
            'user4@example.com',
            'user5@example.com'
        ];
        
        // Act
        foreach ($emails as $email) {
            $otpService->generateOTP($email);
        }
        
        // Assert
        Queue::assertPushed(SendOTPEmailJob::class, 5);
        Queue::assertPushedOn('otp-emails', SendOTPEmailJob::class, 5);
    }

    /** @test */
    public function test_otp_jobs_contain_correct_data()
    {
        // Arrange
        $otpService = new OTPService();
        $email = 'test@example.com';
        
        // Act
        $codigo = $otpService->generateOTP($email);
        
        // Assert
        Queue::assertPushed(SendOTPEmailJob::class, function ($job) use ($email, $codigo) {
            return $job->email === $email && 
                   $job->codigo === $codigo &&
                   $job->userName === 'Usuario' &&
                   $job->expirationMinutes === 10;
        });
    }

    /** @test */
    public function test_whatsapp_jobs_contain_correct_data()
    {
        // Arrange
        Config::set('services.whatsapp.enabled', true);
        Config::set('services.otp.channel', 'whatsapp');
        $otpService = new OTPService();
        $email = 'test@example.com';
        $phone = '1234567890';
        
        // Act
        $codigo = $otpService->generateOTP($email, $phone);
        
        // Assert
        Queue::assertPushed(SendOTPWhatsAppJob::class, function ($job) use ($phone, $codigo) {
            return $job->phone === $phone && 
                   $job->codigo === $codigo &&
                   $job->userName === 'Usuario' &&
                   $job->expirationMinutes === 10;
        });
    }

    /** @test */
    public function test_otp_generation_creates_database_record()
    {
        // Arrange
        $otpService = new OTPService();
        $email = 'test@example.com';
        
        // Act
        $codigo = $otpService->generateOTP($email);
        
        // Assert
        $this->assertDatabaseHas('otps', [
            'email' => $email,
            'codigo' => $codigo,
            'usado' => false,
            'canal_enviado' => 'email'
        ]);
        
        Queue::assertPushed(SendOTPEmailJob::class);
    }

    /** @test */
    public function test_existing_user_name_is_used_in_jobs()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'John Doe'
        ]);
        
        $otpService = new OTPService();
        
        // Act
        $otpService->generateOTP($user->email);
        
        // Assert
        Queue::assertPushed(SendOTPEmailJob::class, function ($job) {
            return $job->userName === 'John Doe';
        });
    }

    /** @test */
    public function test_otp_configuration_is_applied_correctly()
    {
        // Arrange
        Config::set('services.otp.expiration_minutes', 15);
        $otpService = new OTPService();
        
        // Act
        $otpService->generateOTP('test@example.com');
        
        // Assert
        Queue::assertPushed(SendOTPEmailJob::class, function ($job) {
            return $job->expirationMinutes === 15;
        });
    }

    /** @test */
    public function test_fallback_from_whatsapp_to_email_when_no_phone()
    {
        // Arrange
        Config::set('services.whatsapp.enabled', true);
        Config::set('services.otp.channel', 'whatsapp');
        $otpService = new OTPService();
        
        // Act - No phone provided, should fallback to email
        $otpService->generateOTP('test@example.com');
        
        // Assert
        Queue::assertPushed(SendOTPEmailJob::class);
        Queue::assertNotPushed(SendOTPWhatsAppJob::class);
    }

    /** @test */
    public function test_fallback_from_whatsapp_to_email_when_whatsapp_disabled()
    {
        // Arrange
        Config::set('services.whatsapp.enabled', false);
        Config::set('services.otp.channel', 'whatsapp');
        $otpService = new OTPService();
        
        // Act
        $otpService->generateOTP('test@example.com', '1234567890');
        
        // Assert
        Queue::assertPushed(SendOTPEmailJob::class);
        Queue::assertNotPushed(SendOTPWhatsAppJob::class);
    }

    /** @test */
    public function test_otp_invalidates_previous_codes()
    {
        // Arrange
        $otpService = new OTPService();
        $email = 'test@example.com';
        
        // Act - Generate first OTP
        $firstCodigo = $otpService->generateOTP($email);
        
        // Act - Generate second OTP
        $secondCodigo = $otpService->generateOTP($email);
        
        // Assert
        $this->assertDatabaseHas('otps', [
            'email' => $email,
            'codigo' => $firstCodigo,
            'usado' => true  // Should be marked as used
        ]);
        
        $this->assertDatabaseHas('otps', [
            'email' => $email,
            'codigo' => $secondCodigo,
            'usado' => false  // Current one should be valid
        ]);
        
        Queue::assertPushed(SendOTPEmailJob::class, 2);
    }

    /** @test */
    public function test_high_volume_otp_generation()
    {
        // Arrange
        $otpService = new OTPService();
        $numberOfRequests = 50;
        
        // Act
        for ($i = 1; $i <= $numberOfRequests; $i++) {
            $otpService->generateOTP("user{$i}@example.com");
        }
        
        // Assert
        Queue::assertPushed(SendOTPEmailJob::class, $numberOfRequests);
        
        // Verify all jobs went to the correct queue
        $queuedJobs = Queue::pushedJobs()[SendOTPEmailJob::class] ?? [];
        foreach ($queuedJobs as $jobData) {
            $this->assertEquals('otp-emails', $jobData['queue']);
        }
    }
}