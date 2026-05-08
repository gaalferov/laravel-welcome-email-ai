<?php

namespace Tests\Feature;

use App\Services\WelcomeEmailPersonalizer;
use App\Services\WelcomeMailer;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SignupTest extends TestCase
{
    private array $validForm = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'Developer',
        'company_size' => '11-50',
        'use_case' => 'Sending transactional emails from our Node.js app',
    ];

    private function fakePersonalizer(?array $content = null): void
    {
        $mock = $this->createMock(WelcomeEmailPersonalizer::class);
        $mock->method('personalize')->willReturn($content ?? [
            'headline' => 'Welcome, Jane',
            'body' => 'Personalized content for a developer at a mid-size company.',
            'cta_text' => 'Explore the API',
        ]);
        $mock->method('genericWelcome')->willReturn([
            'headline' => 'Welcome aboard, Jane Doe',
            'body' => "We're glad to have you here.",
            'cta_text' => 'Get Started',
        ]);
        $this->app->instance(WelcomeEmailPersonalizer::class, $mock);
    }

    // Form display

    public function test_signup_page_loads(): void
    {
        $response = $this->get('/signup');

        $response->assertStatus(200);
        $response->assertSee('Create your account');
    }

    public function test_home_redirects_to_signup(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/signup');
    }

    // Form validation

    public function test_validation_rejects_missing_fields(): void
    {
        $response = $this->post('/signup', []);

        $response->assertSessionHasErrors(['name', 'email', 'role', 'company_size', 'use_case']);
    }

    public function test_validation_rejects_invalid_email(): void
    {
        $response = $this->post('/signup', array_merge($this->validForm, [
            'email' => 'not-an-email',
        ]));

        $response->assertSessionHasErrors('email');
    }

    public function test_validation_rejects_long_use_case(): void
    {
        $response = $this->post('/signup', array_merge($this->validForm, [
            'use_case' => str_repeat('a', 1001),
        ]));

        $response->assertSessionHasErrors('use_case');
    }

    // Successful signup

    public function test_successful_signup_redirects_with_success_message(): void
    {
        $this->fakePersonalizer();

        $mailer = $this->mock(WelcomeMailer::class);
        $mailer->shouldReceive('send')->once();

        $response = $this->post('/signup', $this->validForm);

        $response->assertRedirect('/signup');
        $response->assertSessionHas('success');
    }

    public function test_mailer_receives_correct_email_and_template_variables(): void
    {
        $this->fakePersonalizer();

        $mailer = $this->mock(WelcomeMailer::class);
        $mailer->shouldReceive('send')
            ->once()
            ->withArgs(function (string $email, array $vars) {
                return $email === 'jane@example.com'
                    && $vars['user_name'] === 'Jane Doe'
                    && $vars['headline'] === 'Welcome, Jane'
                    && $vars['body'] === 'Personalized content for a developer at a mid-size company.'
                    && $vars['cta_text'] === 'Explore the API';
            });

        $this->post('/signup', $this->validForm);
    }

    // AI fallback

    public function test_ai_failure_falls_back_to_generic_welcome(): void
    {
        Log::spy();

        $personalizer = $this->createMock(WelcomeEmailPersonalizer::class);
        $personalizer->method('personalize')
            ->willThrowException(new \RuntimeException('OpenAI API error'));
        $personalizer->method('genericWelcome')
            ->willReturn([
                'headline' => 'Welcome aboard, Jane Doe',
                'body' => "We're glad to have you here.",
                'cta_text' => 'Get Started',
            ]);
        $this->app->instance(WelcomeEmailPersonalizer::class, $personalizer);

        $mailer = $this->mock(WelcomeMailer::class);
        $mailer->shouldReceive('send')
            ->once()
            ->withArgs(function (string $email, array $vars) {
                return $vars['headline'] === 'Welcome aboard, Jane Doe'
                    && $vars['cta_text'] === 'Get Started';
            });

        $response = $this->post('/signup', $this->validForm);

        $response->assertRedirect('/signup');
        $response->assertSessionHas('success');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $msg) => str_contains($msg, 'AI personalization failed'))
            ->once();
    }

    // Mail failure

    public function test_mail_failure_flashes_error_and_does_not_crash_app(): void
    {
        Log::spy();

        $this->fakePersonalizer();

        $mailer = $this->mock(WelcomeMailer::class);
        $mailer->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Mail service down'));

        $response = $this->post('/signup', $this->validForm);

        $response->assertRedirect('/signup');
        $response->assertSessionMissing('success');
        $response->assertSessionHas('error');

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $msg) => str_contains($msg, 'Failed to send welcome email'))
            ->once();
    }

    // Sandbox mode indicator

    public function test_sandbox_mode_shows_badge_on_form(): void
    {
        config(['services.mailtrap.sandbox' => true]);

        $response = $this->get('/signup');

        $response->assertSee('Sandbox mode');
    }

    public function test_production_mode_hides_sandbox_badge(): void
    {
        config(['services.mailtrap.sandbox' => false]);

        $response = $this->get('/signup');

        $response->assertDontSee('Sandbox mode');
    }

    public function test_success_message_includes_sandbox_mode(): void
    {
        config(['services.mailtrap.sandbox' => true]);
        $this->fakePersonalizer();

        $mailer = $this->mock(WelcomeMailer::class);
        $mailer->shouldReceive('send')->once();

        $response = $this->post('/signup', $this->validForm);

        $response->assertSessionHas('success', 'Welcome email sent (sandbox mode)! Check your inbox.');
    }

    public function test_success_message_includes_production_mode(): void
    {
        config(['services.mailtrap.sandbox' => false]);
        $this->fakePersonalizer();

        $mailer = $this->mock(WelcomeMailer::class);
        $mailer->shouldReceive('send')->once();

        $response = $this->post('/signup', $this->validForm);

        $response->assertSessionHas('success', 'Welcome email sent (production mode)! Check your inbox.');
    }

    // Missing template UUID

    public function test_missing_template_uuid_skips_email_gracefully(): void
    {
        Log::spy();

        config(['welcome.mailtrap_template_uuid' => null]);
        $this->fakePersonalizer();

        $realMailer = new WelcomeMailer;
        $this->app->instance(WelcomeMailer::class, $realMailer);

        $response = $this->post('/signup', $this->validForm);

        $response->assertRedirect('/signup');
        $response->assertSessionHas('success');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $msg) => str_contains($msg, 'Mailtrap template UUID not configured'))
            ->once();
    }
}
