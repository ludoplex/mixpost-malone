<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class DiscordService extends Service
{
    public static string $name = 'discord';

    public static string $label = 'Discord';

    public static function credentials(): array
    {
        return [
            'client_id' => '',
            'client_secret' => '',
            'bot_token' => '',
        ];
    }

    public static function credentialsForm(): array
    {
        return [
            'client_id' => [
                'label' => 'Application ID',
                'type' => 'text',
                'description' => 'Your Discord Application ID',
                'placeholder' => 'Enter your Discord Application ID',
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'password',
                'description' => 'Your Discord OAuth2 Client Secret',
                'placeholder' => 'Enter your Discord Client Secret',
            ],
            'bot_token' => [
                'label' => 'Bot Token',
                'type' => 'password',
                'description' => 'Your Discord Bot Token (required for posting)',
                'placeholder' => 'Enter your Discord Bot Token',
            ],
        ];
    }

    public static function documentation(): string
    {
        return <<<HTML
<div>
    <h4>Discord Bot Setup Instructions</h4>
    <ol>
        <li>Go to <a href="https://discord.com/developers/applications" target="_blank">Discord Developer Portal</a></li>
        <li>Click "New Application" and give it a name</li>
        <li>Copy the <strong>Application ID</strong> from the General Information page</li>
        <li>Go to <strong>OAuth2 → General</strong>:
            <ul>
                <li>Copy the <strong>Client Secret</strong></li>
                <li>Add Redirect URL: <code>{callback_url}</code></li>
            </ul>
        </li>
        <li>Go to <strong>Bot</strong> section:
            <ul>
                <li>Click "Add Bot" if not already created</li>
                <li>Click "Reset Token" to generate a new token</li>
                <li>Copy the <strong>Bot Token</strong></li>
                <li>Enable "Message Content Intent" under Privileged Gateway Intents</li>
            </ul>
        </li>
        <li>Enter all three values above</li>
        <li>Use this URL to add the bot to your server:
            <code>https://discord.com/api/oauth2/authorize?client_id=YOUR_APP_ID&permissions=2147485696&scope=bot%20applications.commands</code>
        </li>
    </ol>
    
    <h5>Webhook-Only Mode (Alternative)</h5>
    <p>If you only need to post to specific channels, you can use webhooks without a bot:</p>
    <ol>
        <li>Right-click the channel → Edit Channel → Integrations → Webhooks</li>
        <li>Create a webhook and copy the URL</li>
        <li>Use the webhook URL directly when posting</li>
    </ol>
</div>
HTML;
    }

    public static function exposedConfiguration(): array
    {
        return [
            'supports_webhooks' => true,
            'supports_embeds' => true,
            'supports_scheduled_events' => true,
            'max_embed_fields' => 25,
            'max_embeds_per_message' => 10,
            'max_message_length' => 2000,
        ];
    }
}
