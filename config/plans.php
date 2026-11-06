<?php

return [

    'plans' => [
        'starter' => [
            'name'               => 'Starter',
            'price_monthly'      => 29,
            'price_yearly'       => 290,
            'stripe_monthly_id'  => 'price_starter_monthly',
            'stripe_yearly_id'   => 'price_starter_yearly',
            'color'              => 'blue',
            'features' => [
                'widgets'           => 1,
                'messages_per_month' => 500,
                'training_sources'  => 3,
                'leads_per_month'   => 50,
                'team_members'      => 1,
                'live_chat'         => false,
                'workflow_automations' => 0,
                'ai_insights'       => false,
                'custom_branding'   => false,
                'api_access'        => false,
                'priority_support'  => false,
                'channels_per_widget' => 1,
                'channel_types'     => ['email', 'whatsapp', 'facebook', 'sms', 'instagram', 'twitter', 'telegram', 'discord', 'slack'],
                'channel_messages_per_month' => 100,
                'channel_webhooks'  => true,
            ],
        ],
        'pro' => [
            'name'               => 'Pro',
            'price_monthly'      => 79,
            'price_yearly'       => 790,
            'stripe_monthly_id'  => 'price_pro_monthly',
            'stripe_yearly_id'   => 'price_pro_yearly',
            'color'              => 'violet',
            'popular'            => true,
            'features' => [
                'widgets'           => 5,
                'messages_per_month' => 5000,
                'training_sources'  => 20,
                'leads_per_month'   => 500,
                'team_members'      => 3,
                'live_chat'         => true,
                'workflow_automations' => 10,
                'ai_insights'       => true,
                'custom_branding'   => true,
                'api_access'        => false,
                'priority_support'  => false,
                'channels_per_widget' => 3,
                'channel_types'     => ['email', 'whatsapp', 'facebook', 'sms', 'instagram', 'twitter', 'telegram', 'discord', 'slack'],
                'channel_messages_per_month' => 1000,
                'channel_webhooks'  => true,
            ],
        ],
        'business' => [
            'name'               => 'Business',
            'price_monthly'      => 199,
            'price_yearly'       => 1990,
            'stripe_monthly_id'  => 'price_business_monthly',
            'stripe_yearly_id'   => 'price_business_yearly',
            'color'              => 'emerald',
            'features' => [
                'widgets'           => 20,
                'messages_per_month' => 25000,
                'training_sources'  => 100,
                'leads_per_month'   => 2500,
                'team_members'      => 10,
                'live_chat'         => true,
                'workflow_automations' => 50,
                'ai_insights'       => true,
                'custom_branding'   => true,
                'api_access'        => true,
                'priority_support'  => true,
                'channels_per_widget' => 10,
                'channel_types'     => ['email', 'whatsapp', 'facebook', 'sms', 'instagram', 'twitter', 'telegram', 'discord', 'slack'],
                'channel_messages_per_month' => 5000,
                'channel_webhooks'  => true,
            ],
        ],
        'enterprise' => [
            'name'               => 'Enterprise',
            'price_monthly'      => 499,
            'price_yearly'       => 4990,
            'stripe_monthly_id'  => 'price_enterprise_monthly',
            'stripe_yearly_id'   => 'price_enterprise_yearly',
            'color'              => 'amber',
            'features' => [
                'widgets'           => -1,   // unlimited
                'messages_per_month' => -1,  // unlimited
                'training_sources'  => -1,   // unlimited
                'leads_per_month'   => -1,   // unlimited
                'team_members'      => -1,   // unlimited
                'live_chat'         => true,
                'workflow_automations' => -1, // unlimited
                'ai_insights'       => true,
                'custom_branding'   => true,
                'api_access'        => true,
                'priority_support'  => true,
                'channels_per_widget' => -1, // unlimited
                'channel_types'     => ['email', 'whatsapp', 'facebook', 'sms', 'instagram', 'twitter', 'telegram', 'discord', 'slack'],
                'channel_messages_per_month' => -1, // unlimited
                'channel_webhooks'  => true,
            ],
        ],
    ],

    'trial_days' => 14,

    // AI reply top-ups: key => [credits, price in USD]. Credits never expire.
    'credit_packs' => ['s' => [1000, 5], 'm' => [5000, 19], 'l' => [20000, 59]],

    'overage' => [
        'enabled'           => true,
        'price_per_message' => 0.01, // $0.01 per message over limit
        'price_per_lead'    => 0.05, // $0.05 per lead over limit
    ],

];
