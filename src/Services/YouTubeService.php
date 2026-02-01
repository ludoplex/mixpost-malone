<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class YouTubeService extends Service
{
    public static string $name = 'youtube';

    public static string $label = 'YouTube';

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
                'description' => 'Your Google Cloud OAuth 2.0 Client ID',
                'placeholder' => 'Enter your Google Client ID',
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'password',
                'description' => 'Your Google Cloud OAuth 2.0 Client Secret',
                'placeholder' => 'Enter your Google Client Secret',
            ],
        ];
    }

    public static function documentation(): string
    {
        return <<<HTML
<div>
    <h4>YouTube API Setup Instructions</h4>
    <ol>
        <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
        <li>Create a new project or select existing one</li>
        <li>Enable the <strong>YouTube Data API v3</strong>:
            <ul>
                <li>Go to APIs & Services → Library</li>
                <li>Search for "YouTube Data API v3"</li>
                <li>Click Enable</li>
            </ul>
        </li>
        <li>Create OAuth 2.0 credentials:
            <ul>
                <li>Go to APIs & Services → Credentials</li>
                <li>Click "Create Credentials" → "OAuth client ID"</li>
                <li>Application type: Web application</li>
                <li>Add authorized redirect URI: <code>{callback_url}</code></li>
            </ul>
        </li>
        <li>Configure OAuth consent screen:
            <ul>
                <li>Go to APIs & Services → OAuth consent screen</li>
                <li>Fill in app information</li>
                <li>Add scopes for YouTube</li>
                <li>Add test users if in testing mode</li>
            </ul>
        </li>
        <li>Copy the Client ID and Client Secret</li>
    </ol>
    
    <h5>Required Scopes</h5>
    <ul>
        <li><code>youtube</code> - Manage your YouTube account</li>
        <li><code>youtube.upload</code> - Upload videos</li>
        <li><code>youtube.readonly</code> - View account info</li>
    </ul>
    
    <h5>Note</h5>
    <p>For production use, you'll need to verify your app with Google. During development, you can add test users to bypass verification.</p>
</div>
HTML;
    }

    public static function exposedConfiguration(): array
    {
        return [
            'supports_video_upload' => true,
            'supports_thumbnails' => true,
            'supports_scheduling' => true,
            'supports_playlists' => true,
            'max_title_length' => 100,
            'max_description_length' => 5000,
            'max_tags' => 500,
            'privacy_options' => ['public', 'unlisted', 'private'],
        ];
    }
}
