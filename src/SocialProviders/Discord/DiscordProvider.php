<?php

namespace Inovector\Mixpost\SocialProviders\Discord;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\SocialProviders\Discord\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\Discord\Concerns\ManagesResources;
use Inovector\Mixpost\SocialProviders\Discord\Concerns\ManagesWebhooks;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class DiscordProvider extends SocialProvider
{
    use ManagesOAuth;
    use ManagesResources;
    use ManagesWebhooks;

    public bool $onlyUserAccount = false; // User can select servers/channels

    public array $callbackResponseKeys = ['code', 'state'];

    protected string $apiBaseUrl = 'https://discord.com/api/v10';
    protected string $oauthBaseUrl = 'https://discord.com/api/oauth2';

    public static function name(): string
    {
        return 'Discord';
    }

    public static function service(): string
    {
        return \Inovector\Mixpost\Services\DiscordService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(true)
            ->minTextChar(1)
            ->maxTextChar(2000) // Discord message limit
            ->minPhotos(0)
            ->maxPhotos(10) // Discord embed limit
            ->minVideos(0)
            ->maxVideos(1)
            ->minGifs(0)
            ->maxGifs(1)
            ->allowMixingMediaTypes(true);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        $data = $accountResource->data ?? [];
        $guildId = $data['guild_id'] ?? '';
        $channelId = $data['channel_id'] ?? '';
        $messageId = $accountResource->pivot->provider_post_id ?? '';
        
        if ($guildId && $channelId && $messageId) {
            return "https://discord.com/channels/{$guildId}/{$channelId}/{$messageId}";
        }
        
        return "https://discord.com";
    }

    /**
     * Make authenticated API request
     */
    protected function apiRequest(string $method, string $endpoint, array $data = [], bool $useBot = false): array
    {
        $token = $this->getAccessToken();
        
        $authHeader = $useBot 
            ? "Bot {$token['bot_token']}" 
            : "Bearer {$token['access_token']}";

        $request = Http::withHeaders([
            'Authorization' => $authHeader,
            'Content-Type' => 'application/json',
        ]);

        $response = $request->{$method}("{$this->apiBaseUrl}/{$endpoint}", $data);

        return $response->json() ?? [];
    }

    /**
     * Get current user info
     */
    public function getUser(): array
    {
        return $this->apiRequest('get', 'users/@me');
    }

    /**
     * Get user's guilds (servers)
     */
    public function getGuilds(): array
    {
        $response = $this->apiRequest('get', 'users/@me/guilds');
        return is_array($response) ? $response : [];
    }

    /**
     * Get guild channels
     */
    public function getGuildChannels(string $guildId): array
    {
        $response = $this->apiRequest('get', "guilds/{$guildId}/channels", [], true);
        
        // Filter to text channels only
        return array_filter($response, fn($ch) => ($ch['type'] ?? -1) === 0);
    }

    /**
     * Send message to channel
     */
    public function sendMessage(string $channelId, array $data): array
    {
        return $this->apiRequest('post', "channels/{$channelId}/messages", $data, true);
    }

    /**
     * Edit message
     */
    public function editMessage(string $channelId, string $messageId, array $data): array
    {
        return $this->apiRequest('patch', "channels/{$channelId}/messages/{$messageId}", $data, true);
    }

    /**
     * Delete message
     */
    public function deleteMessage(string $channelId, string $messageId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->getAccessToken()['bot_token']}",
        ])->delete("{$this->apiBaseUrl}/channels/{$channelId}/messages/{$messageId}");

        return $response->successful();
    }

    /**
     * Create embed message
     */
    public function createEmbed(array $options): array
    {
        $embed = [
            'title' => $options['title'] ?? null,
            'description' => $options['description'] ?? null,
            'url' => $options['url'] ?? null,
            'color' => $options['color'] ?? 0x5865F2, // Discord blurple
            'timestamp' => $options['timestamp'] ?? now()->toIso8601String(),
        ];

        if (!empty($options['image'])) {
            $embed['image'] = ['url' => $options['image']];
        }

        if (!empty($options['thumbnail'])) {
            $embed['thumbnail'] = ['url' => $options['thumbnail']];
        }

        if (!empty($options['author'])) {
            $embed['author'] = [
                'name' => $options['author']['name'] ?? '',
                'url' => $options['author']['url'] ?? null,
                'icon_url' => $options['author']['icon'] ?? null,
            ];
        }

        if (!empty($options['footer'])) {
            $embed['footer'] = [
                'text' => $options['footer']['text'] ?? '',
                'icon_url' => $options['footer']['icon'] ?? null,
            ];
        }

        if (!empty($options['fields'])) {
            $embed['fields'] = array_map(fn($f) => [
                'name' => $f['name'],
                'value' => $f['value'],
                'inline' => $f['inline'] ?? false,
            ], $options['fields']);
        }

        return array_filter($embed, fn($v) => $v !== null);
    }

    /**
     * Publish post to Discord channel
     */
    public function publishPost(string $text, array $options = []): array
    {
        $channelId = $options['channel_id'] ?? $this->values['data']['channel_id'] ?? null;

        if (!$channelId) {
            return $this->response(static::RESPONSE_ERROR, ['message' => 'No channel ID specified']);
        }

        $payload = ['content' => $text];

        // Add embeds if provided
        if (!empty($options['embeds'])) {
            $payload['embeds'] = $options['embeds'];
        } elseif (!empty($options['embed'])) {
            $payload['embeds'] = [$this->createEmbed($options['embed'])];
        }

        // Add components (buttons, select menus) if provided
        if (!empty($options['components'])) {
            $payload['components'] = $options['components'];
        }

        // Check if using webhook
        if (!empty($options['webhook_url'])) {
            $result = $this->sendWebhook($options['webhook_url'], $payload);
        } else {
            $result = $this->sendMessage($channelId, $payload);
        }

        if (isset($result['id'])) {
            return $this->response(static::RESPONSE_SUCCESS, [
                'id' => $result['id'],
                'channel_id' => $result['channel_id'] ?? $channelId,
            ]);
        }

        return $this->response(static::RESPONSE_ERROR, $result);
    }

    /**
     * Create announcement in announcement channel
     */
    public function createAnnouncement(string $channelId, string $content, array $options = []): array
    {
        $result = $this->sendMessage($channelId, [
            'content' => $content,
            'embeds' => $options['embeds'] ?? [],
        ]);

        // Crosspost to followers if it's an announcement channel
        if (isset($result['id']) && ($options['crosspost'] ?? false)) {
            $this->apiRequest('post', "channels/{$channelId}/messages/{$result['id']}/crosspost", [], true);
        }

        return $result;
    }

    /**
     * Create scheduled event
     */
    public function createScheduledEvent(string $guildId, array $data): array
    {
        return $this->apiRequest('post', "guilds/{$guildId}/scheduled-events", [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'scheduled_start_time' => $data['start_time'],
            'scheduled_end_time' => $data['end_time'] ?? null,
            'privacy_level' => 2, // GUILD_ONLY
            'entity_type' => $data['type'] ?? 3, // EXTERNAL
            'entity_metadata' => [
                'location' => $data['location'] ?? 'Online',
            ],
        ], true);
    }
}
