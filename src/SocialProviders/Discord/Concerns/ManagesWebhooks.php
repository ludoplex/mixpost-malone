<?php

namespace Inovector\Mixpost\SocialProviders\Discord\Concerns;

use Illuminate\Support\Facades\Http;

trait ManagesWebhooks
{
    /**
     * Send message via webhook (no bot required)
     */
    public function sendWebhook(string $webhookUrl, array $data): array
    {
        $payload = [];

        // Content
        if (!empty($data['content'])) {
            $payload['content'] = $data['content'];
        }

        // Username override
        if (!empty($data['username'])) {
            $payload['username'] = $data['username'];
        }

        // Avatar override
        if (!empty($data['avatar_url'])) {
            $payload['avatar_url'] = $data['avatar_url'];
        }

        // Embeds
        if (!empty($data['embeds'])) {
            $payload['embeds'] = $data['embeds'];
        }

        // Build query string properly
        $queryParams = ['wait' => 'true'];
        if (!empty($data['thread_id'])) {
            $queryParams['thread_id'] = $data['thread_id'];
        }
        $queryString = http_build_query($queryParams);

        $response = Http::post("{$webhookUrl}?{$queryString}", $payload);

        return $response->json() ?? ['error' => 'Webhook request failed'];
    }

    /**
     * Edit webhook message
     */
    public function editWebhookMessage(string $webhookUrl, string $messageId, array $data): array
    {
        $response = Http::patch("{$webhookUrl}/messages/{$messageId}", $data);
        return $response->json() ?? [];
    }

    /**
     * Delete webhook message
     */
    public function deleteWebhookMessage(string $webhookUrl, string $messageId): bool
    {
        $response = Http::delete("{$webhookUrl}/messages/{$messageId}");
        return $response->successful();
    }

    /**
     * Create webhook in a channel (requires Manage Webhooks permission)
     */
    public function createWebhook(string $channelId, string $name, ?string $avatar = null): array
    {
        $data = ['name' => $name];
        
        if ($avatar) {
            $data['avatar'] = $avatar; // Base64 encoded image
        }

        return $this->apiRequest('post', "channels/{$channelId}/webhooks", $data, true);
    }

    /**
     * Get webhooks for a channel
     */
    public function getChannelWebhooks(string $channelId): array
    {
        return $this->apiRequest('get', "channels/{$channelId}/webhooks", [], true);
    }

    /**
     * Get webhooks for a guild
     */
    public function getGuildWebhooks(string $guildId): array
    {
        return $this->apiRequest('get', "guilds/{$guildId}/webhooks", [], true);
    }

    /**
     * Delete a webhook
     */
    public function deleteWebhook(string $webhookId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->getAccessToken()['bot_token']}",
        ])->delete("{$this->apiBaseUrl}/webhooks/{$webhookId}");

        return $response->successful();
    }

    /**
     * Send rich embed via webhook
     */
    public function sendRichWebhook(string $webhookUrl, array $options): array
    {
        $embeds = [];

        if (!empty($options['title']) || !empty($options['description'])) {
            $embed = [
                'title' => $options['title'] ?? null,
                'description' => $options['description'] ?? null,
                'url' => $options['url'] ?? null,
                'color' => $options['color'] ?? 0x5865F2,
                'timestamp' => $options['timestamp'] ?? now()->toIso8601String(),
            ];

            if (!empty($options['image'])) {
                $embed['image'] = ['url' => $options['image']];
            }

            if (!empty($options['thumbnail'])) {
                $embed['thumbnail'] = ['url' => $options['thumbnail']];
            }

            if (!empty($options['author'])) {
                $embed['author'] = $options['author'];
            }

            if (!empty($options['footer'])) {
                $embed['footer'] = $options['footer'];
            }

            if (!empty($options['fields'])) {
                $embed['fields'] = $options['fields'];
            }

            $embeds[] = array_filter($embed, fn($v) => $v !== null);
        }

        return $this->sendWebhook($webhookUrl, [
            'content' => $options['content'] ?? null,
            'username' => $options['username'] ?? null,
            'avatar_url' => $options['avatar_url'] ?? null,
            'embeds' => $embeds,
        ]);
    }

    /**
     * Execute webhook with file attachments
     */
    public function sendWebhookWithFiles(string $webhookUrl, array $data, array $files): array
    {
        $request = Http::asMultipart();

        // Add payload as JSON
        $request = $request->attach('payload_json', json_encode($data), null, ['Content-Type' => 'application/json']);

        // Add files
        foreach ($files as $index => $file) {
            $request = $request->attach(
                "files[{$index}]",
                file_get_contents($file['path']),
                $file['name'] ?? basename($file['path'])
            );
        }

        $response = $request->post($webhookUrl . '?wait=true');

        return $response->json() ?? ['error' => 'Webhook request failed'];
    }
}
