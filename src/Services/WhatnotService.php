<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class WhatnotService extends Service
{
    public static string $name = 'whatnot';

    public static string $label = 'Whatnot';

    public static function credentials(): array
    {
        return [
            'client_id' => '',
            'client_secret' => '',
        ];
    }

    public static function credentialsForm(): array
    {
        return [
            'client_id' => [
                'label' => 'Client ID',
                'type' => 'text',
                'description' => 'Get this from Whatnot Developer Portal',
                'placeholder' => 'Enter your Whatnot Client ID',
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'password',
                'description' => 'Get this from Whatnot Developer Portal',
                'placeholder' => 'Enter your Whatnot Client Secret',
            ],
        ];
    }

    public static function documentation(): string
    {
        return <<<HTML
<div>
    <h4>Whatnot Setup Instructions</h4>
    <ol>
        <li>Contact Whatnot to get API access (currently in limited beta)</li>
        <li>Once approved, access the Whatnot Developer Portal</li>
        <li>Create a new application:
            <ul>
                <li>Name: Your app name (e.g., "MixPost Integration")</li>
                <li>OAuth Redirect URL: <code>{callback_url}</code></li>
                <li>Scopes: Request seller, shows, listings, and notifications access</li>
            </ul>
        </li>
        <li>Copy the Client ID and Client Secret</li>
        <li>Enter both values above</li>
    </ol>
    <p><strong>Note:</strong> Whatnot API access may require seller verification and approval.</p>
</div>
HTML;
    }

    public static function exposedConfiguration(): array
    {
        return [
            'supports_shows' => true,
            'supports_listings' => true,
            'supports_notifications' => true,
            'categories' => [
                'collectibles',
                'sports_cards',
                'pokemon',
                'funko',
                'vintage',
                'sneakers',
                'fashion',
                'electronics',
                'comics',
                'other',
            ],
        ];
    }
}
