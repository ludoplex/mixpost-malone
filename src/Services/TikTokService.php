<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class TikTokService extends Service
{
    public static string $name = 'tiktok';

    public static string $label = 'TikTok';

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
                'label' => 'Client Key',
                'type' => 'text',
                'description' => 'Your TikTok Developer App Client Key',
                'placeholder' => 'Enter your TikTok Client Key',
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'password',
                'description' => 'Your TikTok Developer App Client Secret',
                'placeholder' => 'Enter your TikTok Client Secret',
            ],
        ];
    }

    public static function documentation(): string
    {
        return <<<HTML
<div>
    <h4>TikTok API Setup Instructions</h4>
    <ol>
        <li>Go to <a href="https://developers.tiktok.com/" target="_blank">TikTok for Developers</a></li>
        <li>Create a developer account if you don't have one</li>
        <li>Create a new app:
            <ul>
                <li>Click "Manage apps" → "Create app"</li>
                <li>Choose app type (usually "Web" for MixPost)</li>
                <li>Fill in app details</li>
            </ul>
        </li>
        <li>Configure app settings:
            <ul>
                <li>Add redirect URI: <code>{callback_url}</code></li>
                <li>Request required scopes (see below)</li>
            </ul>
        </li>
        <li>Get credentials:
            <ul>
                <li>Copy <strong>Client Key</strong></li>
                <li>Copy <strong>Client Secret</strong></li>
            </ul>
        </li>
        <li>Submit for review (required for production)</li>
    </ol>
    
    <h5>Required Scopes</h5>
    <ul>
        <li><code>user.info.basic</code> - Basic user info</li>
        <li><code>user.info.profile</code> - Profile info</li>
        <li><code>user.info.stats</code> - Follower/likes stats</li>
        <li><code>video.list</code> - View videos</li>
        <li><code>video.upload</code> - Upload videos</li>
        <li><code>video.publish</code> - Publish videos</li>
    </ul>
    
    <h5>Important Notes</h5>
    <ul>
        <li>TikTok requires app review before production use</li>
        <li>Test users can be added during development</li>
        <li>Video uploads may take time to process</li>
        <li>PKCE (code challenge) is required for auth flow</li>
    </ul>
</div>
HTML;
    }

    public static function exposedConfiguration(): array
    {
        return [
            'supports_video_upload' => true,
            'supports_photo_carousel' => true,
            'supports_scheduling' => false,
            'max_caption_length' => 2200,
            'max_video_duration' => 600, // 10 minutes in seconds
            'max_photos' => 35,
            'privacy_options' => [
                'PUBLIC_TO_EVERYONE',
                'MUTUAL_FOLLOW_FRIENDS', 
                'SELF_ONLY',
            ],
        ];
    }
}
