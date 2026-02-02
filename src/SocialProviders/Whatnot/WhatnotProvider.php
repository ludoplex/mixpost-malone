<?php

namespace Inovector\Mixpost\SocialProviders\Whatnot;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\SocialProviders\Whatnot\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\Whatnot\Concerns\ManagesResources;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class WhatnotProvider extends SocialProvider
{
    use ManagesOAuth;
    use ManagesResources;

    public array $callbackResponseKeys = ['code', 'state'];

    protected string $apiBaseUrl = 'https://api.whatnot.com/v1';

    public static function name(): string
    {
        return 'Whatnot';
    }

    public static function service(): string
    {
        return \Inovector\Mixpost\Services\WhatnotService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(true)
            ->minTextChar(1)
            ->maxTextChar(1000) // Whatnot listing description limit
            ->minPhotos(0)
            ->maxPhotos(10) // Whatnot supports multiple images per listing
            ->minVideos(0)
            ->maxVideos(1)
            ->minGifs(0)
            ->maxGifs(0)
            ->allowMixingMediaTypes(false);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        return "https://whatnot.com/user/{$accountResource->username}";
    }

    /**
     * Make authenticated API request
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Content-Type' => 'application/json',
        ])->{$method}("{$this->apiBaseUrl}/{$endpoint}", $data);

        return $response->json() ?? [];
    }

    /**
     * Get current user info
     */
    public function getUser(): array
    {
        return $this->apiRequest('get', 'me');
    }

    /**
     * Get seller profile
     */
    public function getSellerProfile(): array
    {
        return $this->apiRequest('get', 'seller/profile');
    }

    /**
     * Get upcoming live shows
     */
    public function getUpcomingShows(): array
    {
        return $this->apiRequest('get', 'shows/upcoming');
    }

    /**
     * Get scheduled shows
     */
    public function getScheduledShows(): array
    {
        return $this->apiRequest('get', 'shows/scheduled');
    }

    /**
     * Create a show announcement/listing
     */
    public function createShowAnnouncement(array $data): array
    {
        return $this->apiRequest('post', 'shows', [
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'category' => $data['category'] ?? 'collectibles',
            'notification_enabled' => $data['notification_enabled'] ?? true,
        ]);
    }

    /**
     * Update show details
     */
    public function updateShow(string $showId, array $data): array
    {
        return $this->apiRequest('patch', "shows/{$showId}", $data);
    }

    /**
     * Create a product listing
     */
    public function createListing(array $data): array
    {
        return $this->apiRequest('post', 'listings', [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'starting_price' => $data['starting_price'] ?? 1,
            'buy_now_price' => $data['buy_now_price'] ?? null,
            'category' => $data['category'] ?? 'collectibles',
            'condition' => $data['condition'] ?? 'new',
            'shipping' => $data['shipping'] ?? [],
            'images' => $data['images'] ?? [],
        ]);
    }

    /**
     * Publish post (creates show announcement or sends notification)
     */
    public function publishPost(string $text, array $options = []): array
    {
        // Determine post type based on options
        $type = $options['type'] ?? 'announcement';

        if ($type === 'show') {
            $result = $this->createShowAnnouncement([
                'title' => $options['title'] ?? substr($text, 0, 100),
                'description' => $text,
                'scheduled_at' => $options['scheduled_at'] ?? null,
                'category' => $options['category'] ?? 'collectibles',
            ]);
        } elseif ($type === 'listing') {
            $result = $this->createListing([
                'title' => $options['title'] ?? substr($text, 0, 100),
                'description' => $text,
                'starting_price' => $options['starting_price'] ?? 1,
                'category' => $options['category'] ?? 'collectibles',
                'images' => $options['images'] ?? [],
            ]);
        } else {
            // Default: send as follower notification
            $result = $this->sendFollowerNotification($text);
        }

        if (isset($result['error'])) {
            return $this->response(static::RESPONSE_ERROR, $result);
        }

        return $this->response(static::RESPONSE_SUCCESS, [
            'id' => $result['id'] ?? uniqid('whatnot_'),
            'type' => $type,
        ]);
    }

    /**
     * Send notification to followers
     */
    public function sendFollowerNotification(string $message): array
    {
        return $this->apiRequest('post', 'notifications/followers', [
            'message' => $message,
        ]);
    }

    /**
     * Get live stream status
     */
    public function getLiveStatus(): ?array
    {
        $response = $this->apiRequest('get', 'stream/status');
        return $response['is_live'] ?? false ? $response : null;
    }
}
