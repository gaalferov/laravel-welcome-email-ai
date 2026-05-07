# Laravel SaaS: AI-Personalized Welcome Email (Sandbox)

A Laravel application where new users sign up, AI generates a personalized welcome email based on their profile, and the email is sent via [Mailtrap](https://mailtrap.io) — with a config-only switch between **Sandbox** (preview) and **production** (real delivery) modes.

## How It Works

```
User fills out signup form
(name, email, role, company size, use case)
        |
        v
  Input validation
        |
        v
  AI generates personalized content ── Failed? → Use generic welcome
  (headline, body, CTA)
        |
        v
  Send welcome email via Mailtrap
  template API with variables
        |
        ├── MAILTRAP_SANDBOX=true  → Email appears in Sandbox inbox
        └── MAILTRAP_SANDBOX=false → Email delivered to real inbox
        |
        v
  Redirect with success message
```

## Features

- **AI Personalization** — OpenAI generates a personalized welcome email based on the user's role, company size, and use case
- **Mailtrap Sandbox Mode** — Preview AI-generated emails in your Mailtrap Sandbox inbox during development
- **Production Mode** — Flip one env var to send real emails via Mailtrap transactional API
- **Config-Only Switching** — `MAILTRAP_SANDBOX=true/false` toggles modes with zero code changes
- **Mailtrap Templates** — Email content delivered via Mailtrap template engine with variables (`user_name`, `headline`, `body`, `cta_text`)
- **Graceful Degradation** — If OpenAI is unavailable, a generic welcome email is sent instead; mail failures are logged without crashing

## Prerequisites

- PHP 8.3+
- [Composer](https://getcomposer.org/)
- [Mailtrap account](https://mailtrap.io) with a verified sending domain and an email template
- [OpenAI API key](https://platform.openai.com/api-keys)

## Setup

1. **Clone and install**

```bash
git clone https://github.com/mailtrap/examples.git
cd examples/php/laravel-welcome-email-ai
composer install
```

2. **Configure environment**

```bash
cp .env.example .env
php artisan key:generate
```

3. **Set your API keys** in `.env`

```
MAILTRAP_API_KEY=your_mailtrap_api_key
MAILTRAP_TEMPLATE_UUID=your_welcome_email_template_uuid
MAIL_FROM_ADDRESS=welcome@yourdomain.com

OPENAI_API_KEY=your_openai_api_key
OPENAI_MODEL=gpt-4o-mini          # optional, defaults to gpt-4o-mini
```

4. **Choose your mode**

For **Sandbox** (development — emails go to Mailtrap inbox):

```
MAILTRAP_SANDBOX=true
MAILTRAP_INBOX_ID=your_sandbox_inbox_id
```

For **Production** (real delivery):

```
MAILTRAP_SANDBOX=false
```

5. **Run the app**

```bash
php artisan serve
```

6. **Open** [http://localhost:8000/signup](http://localhost:8000/signup)

## Mailtrap Template Setup

1. Go to [Mailtrap](https://mailtrap.io) > **Sending** > **Email Templates**
2. Create a new template for the welcome email
3. Use these template variables in your design:

| Variable | Type | Example |
|---|---|---|
| `user_name` | string | `Jane Doe` |
| `headline` | string | `Welcome, Jane — let's get you shipping` |
| `body` | string | `As a Developer at a mid-size company focused on transactional emails...` |
| `cta_text` | string | `Explore the API` |

4. Copy the template UUID to your `.env` as `MAILTRAP_TEMPLATE_UUID`

### Finding your Sandbox Inbox ID

1. Go to [Mailtrap](https://mailtrap.io) > **Email Testing** > **Inboxes**
2. Click on your inbox
3. The inbox ID is in the URL: `https://mailtrap.io/inboxes/INBOX_ID/messages`
4. Copy it to your `.env` as `MAILTRAP_INBOX_ID`

## Try Different Profiles

Submit these profiles to see how AI personalizes the welcome email:

| Name | Role | Company Size | Use Case | Expected tone |
|---|---|---|---|---|
| Jane | Developer | 11-50 | Transactional emails from Node.js | Technical, API-focused |
| Mike | Founder / CTO | 1-10 | Email notifications for our MVP | Startup-friendly, quick wins |
| Sarah | Engineering Manager | 201-1000 | Migrate from SendGrid to Mailtrap | Enterprise, migration-focused |
| Alex | QA Engineer | 51-200 | Testing email templates before production | Testing/QA-focused |

## Project Structure

```
app/
  Http/Controllers/
    SignupController.php              # Form display + submission handling
  Services/
    WelcomeEmailPersonalizer.php     # AI content generation via OpenAI
    WelcomeMailer.php                # Sends email via Mailtrap template API
config/
  mail.php                            # Mailtrap mailer config
  welcome.php                         # Template UUID, sandbox toggle, AI model
resources/views/
  signup.blade.php                    # Signup form UI
routes/
  web.php                             # GET/POST /signup
tests/Feature/
  SignupTest.php                      # Form, AI fallback, mail, sandbox tests
```

## Key Integration Points

### Sandbox vs. Production Mode

The `WelcomeMailer` service initializes `MailtrapClient` with sandbox configuration based on the `MAILTRAP_SANDBOX` env var:

```php
$client = MailtrapClient::initSendingEmails(
    apiKey: config('services.mailtrap.apiKey'),
    isSandbox: $isSandbox,
    inboxId: $isSandbox ? $inboxId : null,
);
```

- **Sandbox** (`MAILTRAP_SANDBOX=true`): Emails are delivered to your Mailtrap Sandbox inbox for preview. Requires `MAILTRAP_INBOX_ID`.
- **Production** (`MAILTRAP_SANDBOX=false`): Emails are delivered to real inboxes via Mailtrap transactional API.

No code changes needed — just toggle the env var.

> **Note:** Mailtrap operates as two distinct products. **Mailtrap Sandbox** is a fake SMTP environment for development and testing — emails sent there are never delivered to real recipients. **Mailtrap Email Sending** (production) is a separate infrastructure for transactional email delivery to real inboxes. This project uses `MAILTRAP_API_KEY` which works for both, but the endpoints and behavior differ.

### AI Personalization

The `WelcomeEmailPersonalizer` sends the user's profile to OpenAI with a structured prompt and returns:

```php
[
    'headline' => 'Welcome, Jane — let\'s get you shipping',
    'body'     => 'As a Developer at a growing 11-50 person company...',
    'cta_text' => 'Explore the API',
]
```

### Mailtrap Template API

Since Mailtrap templates generate their own subject and HTML, the app uses `MailtrapClient` directly instead of Laravel's Mailable system:

```php
$email = (new MailtrapEmail())
    ->from(new Address(config('mail.from.address'), config('mail.from.name')))
    ->to(new Address($recipientEmail))
    ->templateUuid($templateUuid)
    ->templateVariables($templateVariables);

MailtrapClient::initSendingEmails(
    apiKey: config('services.mailtrap.apiKey'),
    isSandbox: $isSandbox,
    inboxId: $isSandbox ? $inboxId : null,
)->send($email);
```

### Error Handling

- **OpenAI unavailable** — falls back to a generic welcome message with the user's name. The email is still sent.
- **Mail delivery failure** — logged at error level; the user still sees a success message to avoid confusion.
- **Missing template UUID** — logged as warning; email sending is skipped gracefully.

## Running Tests

```bash
./vendor/bin/phpunit
```

Tests cover form validation, AI personalization, generic fallback, mail failure handling, sandbox/production mode switching, and missing template UUID — all without requiring real OpenAI or Mailtrap credentials.

## Links

- [Mailtrap Email API docs](https://mailtrap.io/email-api)
- [Mailtrap PHP SDK](https://github.com/railsware/mailtrap-php)
- [Mailtrap Sandbox](https://mailtrap.io/email-sandbox)
- [OpenAI PHP for Laravel](https://github.com/openai-php/laravel)

## License

MIT
