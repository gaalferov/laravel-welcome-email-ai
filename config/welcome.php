<?php

return [
    'mailtrap_template_uuid' => env('MAILTRAP_TEMPLATE_UUID'),
    'sandbox' => filter_var(env('MAILTRAP_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
    'inbox_id' => env('MAILTRAP_INBOX_ID'),
    'ai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
];
