<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class WelcomeEmailPersonalizer
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an email copywriter for a SaaS product. Given a user's profile, generate a personalized welcome email. Return JSON with exactly these keys:

- "headline": A warm, personalized greeting headline (max 10 words)
- "body": 2-3 sentences welcoming the user, referencing their role, company size, and use case. Be specific and helpful, not generic. Suggest a concrete first step relevant to their use case.
- "cta_text": A call-to-action button label (2-4 words) tailored to their use case

Adjust the tone based on the user's role:
- Developer: technical, API-focused — reference SDKs, code samples, and concrete integration details
- Founder / CTO: startup-friendly, focused on quick wins and shipping fast
- Engineering Manager: enterprise-oriented, migration-focused — emphasize scale, team workflows, and switching from other providers
- QA Engineer: testing/QA-focused — reference test environments, templates, sandbox inboxes, and validation workflows
- For other roles: keep the tone professional but friendly

Do not use exclamation marks excessively.
PROMPT;

    public function personalize(array $profile): array
    {
        // Note: In production, sanitize user input before sending to the LLM
        // to mitigate prompt injection risks.
        $userInput = sprintf(
            "Name: %s\nRole: %s\nCompany size: %s\nUse case: %s",
            $profile['name'],
            $profile['role'],
            $profile['company_size'],
            $profile['use_case'],
        );

        $response = OpenAI::chat()->create([
            'model' => config('welcome.ai_model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $userInput],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
            'max_tokens' => 300,
        ]);

        $result = json_decode($response->choices[0]->message->content, true);

        if (! is_array($result) || ! isset($result['headline'], $result['body'], $result['cta_text'])) {
            Log::warning('AI returned unexpected format, falling back to generic', [
                'raw' => $response->choices[0]->message->content,
            ]);

            return $this->genericWelcome($profile['name']);
        }

        return [
            'headline' => $result['headline'],
            'body' => $result['body'],
            'cta_text' => $result['cta_text'],
        ];
    }

    public function genericWelcome(string $name): array
    {
        return [
            'headline' => "Welcome aboard, {$name}",
            'body' => "We're glad to have you here. Our platform is designed to help teams like yours work more efficiently. Take a few minutes to explore the dashboard and set up your workspace — you'll be up and running in no time.",
            'cta_text' => 'Get Started',
        ];
    }
}
