<?php

namespace Inovector\Mixpost\SocialProviders\Twitch;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\SocialProviders\Twitch\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\Twitch\Concerns\ManagesResources;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class TwitchProvider extends SocialProvider
{
    use ManagesOAuth;
    use ManagesResources;

    public array $callbackResponseKeys = ['code', 'state'];

    protected string $apiBaseUrl = 'https://api.twitch.tv/helix';
    protected string $oauthBaseUrl = 'https://id.twitch.tv/oauth2';

    public static function name(): string
    {
        return 'Twitch';
    }

    public static function service(): string
    {
        return \Inovector\Mixpost\Services\TwitchService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(true)
            ->minTextChar(1)
            ->maxTextChar(500) // Twitch chat/announcement limit
            ->minPhotos(0)
            ->maxPhotos(0) // Twitch doesn't support image posts directly
            ->minVideos(0)
            ->maxVideos(0)
            ->minGifs(0)
            ->maxGifs(0)
            ->allowMixingMediaTypes(false);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        return "https://twitch.tv/{$accountResource->username}";
    }

    /**
     * Make authenticated API request
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Client-Id' => $this->clientId,
        ])->{$method}("{$this->apiBaseUrl}/{$endpoint}", $data);

        return $response->json() ?? [];
    }

    /**
     * Get current user info
     */
    public function getUser(): array
    {
        $response = $this->apiRequest('get', 'users');
        return $response['data'][0] ?? [];
    }

    /**
     * Get channel info
     */
    public function getChannel(string $broadcasterId): array
    {
        $response = $this->apiRequest('get', "channels?broadcaster_id={$broadcasterId}");
        return $response['data'][0] ?? [];
    }

    /**
     * Update channel info (title, game, etc.)
     */
    public function updateChannel(string $broadcasterId, array $data): array
    {
        return $this->apiRequest('patch', "channels?broadcaster_id={$broadcasterId}", $data);
    }

    /**
     * Send chat announcement
     */
    public function sendAnnouncement(string $broadcasterId, string $message, string $color = 'primary'): array
    {
        return $this->apiRequest('post', "chat/announcements?broadcaster_id={$broadcasterId}&moderator_id={$broadcasterId}", [
            'message' => $message,
            'color' => $color, // blue, green, orange, purple, primary
        ]);
    }

    /**
     * Get stream status
     */
    public function getStreamStatus(string $userId): ?array
    {
        $response = $this->apiRequest('get', "streams?user_id={$userId}");
        return $response['data'][0] ?? null;
    }

    /**
     * Create stream marker
     */
    public function createStreamMarker(string $userId, string $description = ''): array
    {
        return $this->apiRequest('post', 'streams/markers', [
            'user_id' => $userId,
            'description' => $description,
        ]);
    }

    /**
     * Publish post (sends as channel announcement)
     */
    public function publishPost(string $text, array $options = []): array
    {
        $user = $this->getUser();
        $broadcasterId = $user['id'] ?? null;

        if (!$broadcasterId) {
            return $this->response(static::RESPONSE_ERROR, ['message' => 'Could not get broadcaster ID']);
        }

        $result = $this->sendAnnouncement($broadcasterId, $text, $options['color'] ?? 'primary');

        if (isset($result['error'])) {
            return $this->response(static::RESPONSE_ERROR, $result);
        }

        return $this->response(static::RESPONSE_SUCCESS, [
            'id' => uniqid('twitch_'),
            'broadcaster_id' => $broadcasterId,
        ]);
    }
}
