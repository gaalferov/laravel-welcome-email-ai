<?php

namespace App\Http\Controllers;

use App\Services\WelcomeEmailPersonalizer;
use App\Services\WelcomeMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SignupController extends Controller
{
    public function __construct(
        private readonly WelcomeEmailPersonalizer $personalizer,
        private readonly WelcomeMailer $mailer,
    ) {}

    public function show(): View
    {
        return view('signup');
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:Developer,Engineering Manager,Product Manager,DevOps / SRE,QA Engineer,Founder / CTO,Other',
            'company_size' => 'required|string|in:1-10,11-50,51-200,201-1000,1000+',
            'use_case' => 'required|string|max:1000',
        ]);

        $profile = [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'company_size' => $validated['company_size'],
            'use_case' => $validated['use_case'],
        ];

        try {
            $content = $this->personalizer->personalize($profile);
        } catch (\Throwable $e) {
            Log::warning('AI personalization failed, using generic welcome', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            $content = $this->personalizer->genericWelcome($profile['name']);
        }

        $templateVariables = [
            'user_name' => $validated['name'],
            'headline' => $content['headline'],
            'body' => $content['body'],
            'cta_text' => $content['cta_text'],
        ];

        try {
            $this->mailer->send($validated['email'], $templateVariables);
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome email', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('signup.show')
                ->with('error', 'We could not send the welcome email. Please try again or contact support.');
        }

        $mode = config('services.mailtrap.sandbox') ? 'sandbox' : 'production';

        return redirect()->route('signup.show')
            ->with('success', "Welcome email sent ({$mode} mode)! Check your inbox.");
    }
}
