<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class TwitchService extends Service
{
    public static string $name = 'twitch';

    public static string $label = 'Twitch';

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
                'description' => 'Get this from your Twitch Developer Console',
                'placeholder' => 'Enter your Twitch Client ID',
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'password',
                'description' => 'Get this from your Twitch Developer Console',
                'placeholder' => 'Enter your Twitch Client Secret',
            ],
        ];
    }

    public static function documentation(): string
    {
        return <<<HTML
<div>
    <h4>Twitch Setup Instructions</h4>
    <ol>
        <li>Go to <a href="https://dev.twitch.tv/console" target="_blank">Twitch Developer Console</a></li>
        <li>Click "Register Your Application"</li>
        <li>Fill in the application details:
            <ul>
                <li>Name: Your app name (e.g., "MixPost Integration")</li>
                <li>OAuth Redirect URL: <code>{callback_url}</code></li>
                <li>Category: Choose appropriate category</li>
            </ul>
        </li>
        <li>Click "Create" and copy the Client ID</li>
        <li>Click "New Secret" to generate a Client Secret</li>
        <li>Enter both values above</li>
    </ol>
</div>
HTML;
    }
}
