<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Plan
    |--------------------------------------------------------------------------
    |
    | The plan slug assigned to a user when none is set, and the fallback
    | used if a user's `plan` column ever references an unknown slug.
    |
    */

    'default' => 'free',

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | Every entitlement a plan grants lives here so upgrading/downgrading a
    | user, or adding a new tier, never requires a code change. A limit of
    | `null` means unlimited. This is intentionally billing-provider
    | agnostic: when Cashier/Stripe is introduced later, a Stripe price ID
    | maps to one of these slugs and none of the entitlement checks change.
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free',
            'limits' => [
                'albums' => 10,
                'mosaics' => 5,
                'entries' => 100,
                'max_upload_size_mb' => 25,
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'limits' => [
                'albums' => null,
                'mosaics' => null,
                'entries' => null,
                'max_upload_size_mb' => 250,
            ],
        ],
    ],

];
