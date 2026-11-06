<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'key'              => env('OPENAI_API_KEY'),
        'organization'     => env('OPENAI_ORGANIZATION'),
        'model'            => env('OPENAI_MODEL', 'gpt-4o'),
        'embedding_model'  => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'max_tokens'       => 2048,
        'temperature'      => 0.7,
        'timeout'          => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic Claude Configuration
    |--------------------------------------------------------------------------
    */
    'anthropic' => [
        'key'     => env('ANTHROPIC_API_KEY'),
        'model'   => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'timeout' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'key'     => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'timeout' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq Configuration (Ultra-fast Llama/Mixtral inference)
    |--------------------------------------------------------------------------
    */
    'groq' => [
        'key'     => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'timeout' => 30,
    ],

    /*
    | OpenRouter (one key, hundreds of models) + automatic failover
    */
    'openrouter' => [
        'key'   => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
    ],

    // Provider retried once when a widget's own provider errors (blank = off).
    'fallback_provider' => env('AI_FALLBACK_PROVIDER'),
    // Platform-wide provider chosen by the admin (Admin > Settings > AI). Widgets cannot override it.
    'provider' => env('AI_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Training
    |--------------------------------------------------------------------------
    */
    'training' => [
        'chunk_size'           => 1000,
        'chunk_overlap'        => 200,
        'max_chunks'           => 500,
        'embedding_dim'        => 1536,
        'similarity_threshold' => 0.75,
        'max_results'          => 5,
        'crawler_max_pages'    => 50,
        'max_pdf_size_mb'      => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Response
    |--------------------------------------------------------------------------
    */
    'response' => [
        'confidence_threshold'  => 0.6,
        'max_history_messages'  => 20,
        'stream_enabled'        => true,
        'fallback_message'      => "I'm not sure about that. Could you rephrase or provide more details? I can also connect you with our team.",
        'tones' => [
            'friendly'     => 'You are a friendly, warm, and helpful AI assistant. Use a conversational tone and show genuine care for the visitor.',
            'professional' => 'You are a professional and polished business assistant. Maintain formal language while remaining approachable.',
            'sales'        => 'You are an enthusiastic sales assistant focused on understanding customer needs and helping them find the perfect solution. Highlight value and benefits.',
            'support'      => 'You are a patient and empathetic technical support specialist. Listen carefully, ask clarifying questions, and provide clear step-by-step guidance.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Qualification
    |--------------------------------------------------------------------------
    */
    'lead_qualification' => [
        'enabled'        => true,
        'hot_threshold'  => 80,
        'warm_threshold' => 50,
        'questions' => [
            'budget'     => 'What is your approximate budget for this solution?',
            'timeline'   => 'When are you looking to get started?',
            'service'    => 'Which of our services interests you the most?',
            'company'    => 'Can you tell me about your company and team size?',
            'pain_point' => 'What is the main challenge you are trying to solve?',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Security
    |--------------------------------------------------------------------------
    */
    'widget' => [
        'rate_limit_per_minute' => 30,
        'max_message_length'    => 2000,
        'session_timeout_hours' => 24,
        'token_expiry_days'     => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Insights
    |--------------------------------------------------------------------------
    */
    'insights' => [
        'generate_interval_hours' => 24,
        'min_conversations'       => 10,
        'model'                   => 'gpt-4o-mini',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smart Suggestions
    |--------------------------------------------------------------------------
    */
    'smart_suggestions' => [
        'enabled'     => true,
        'count'       => 3,
        'model'       => 'gpt-4o-mini',
        'max_tokens'  => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentiment Analysis
    |--------------------------------------------------------------------------
    */
    'sentiment' => [
        'enabled' => true,
        'model'   => 'gpt-4o-mini',
    ],

];
