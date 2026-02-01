<?php

namespace Inovector\Mixpost\SocialProviders\TikTok;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\SocialProviders\TikTok\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\TikTok\Concerns\ManagesResources;
use Inovector\Mixpost\SocialProviders\TikTok\Concerns\ManagesUploads;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class TikTokProvider extends SocialProvider
{
    use ManagesOAuth;
    use ManagesResources;
    use ManagesUploads;

    public array $callbackResponseKeys = ['code', 'state'];

    protected string $apiBaseUrl = 'https://open.tiktokapis.com/v2';
    protected string $oauthBaseUrl = 'https://www.tiktok.com/v2/auth/authorize';
    protected string $tokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';

    public static function name(): string
    {
        return 'TikTok';
    }

    public static function service(): string
    {
        return \Inovector\Mixpost\Services\TikTokService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(false)
            ->minTextChar(0)
            ->maxTextChar(2200) // TikTok caption limit
            ->minPhotos(0)
            ->maxPhotos(35) // TikTok photo carousel
            ->minVideos(1)
            ->maxVideos(1)
            ->minGifs(0)
            ->maxGifs(0)
            ->allowMixingMediaTypes(false);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        $username = $accountResource->username ?? '';
        $videoId = $accountResource->pivot->provider_post_id ?? '';
        return "https://www.tiktok.com/@{$username}/video/{$videoId}";
    }

    /**
     * Make authenticated API request
     */
    protected function apiRequest(string $method, string $endpoint, array $params = [], array $body = []): array
    {
        $token = $this->getAccessToken();

        $url = "{$this->apiBaseUrl}/{$endpoint}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $request = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Content-Type' => 'application/json',
        ]);

        $response = empty($body) 
            ? $request->{$method}($url)
            : $request->{$method}($url, $body);

        return $response->json() ?? [];
    }

    /**
     * Get current user info
     */
    public function getUser(): array
    {
        $response = $this->apiRequest('get', 'user/info/', [
            'fields' => 'open_id,union_id,avatar_url,avatar_url_100,avatar_large_url,display_name,bio_description,profile_deep_link,is_verified,follower_count,following_count,likes_count,video_count',
        ]);

        return $response['data']['user'] ?? [];
    }

    /**
     * Get user videos
     */
    public function getVideos(int $maxCount = 20, ?string $cursor = null): array
    {
        $params = [
            'fields' => 'id,title,video_description,duration,cover_image_url,share_url,view_count,like_count,comment_count,share_count,create_time',
            'max_count' => $maxCount,
        ];

        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = $this->apiRequest('post', 'video/list/', [], $params);

        return $response['data'] ?? [];
    }

    /**
     * Get video info
     */
    public function getVideo(string $videoId): array
    {
        $response = $this->apiRequest('post', 'video/query/', [], [
            'filters' => [
                'video_ids' => [$videoId],
            ],
            'fields' => 'id,title,video_description,duration,cover_image_url,share_url,view_count,like_count,comment_count,share_count,create_time',
        ]);

        return $response['data']['videos'][0] ?? [];
    }

    /**
     * Get creator info (for business accounts)
     */
    public function getCreatorInfo(): array
    {
        $response = $this->apiRequest('get', 'business/get/', [
            'fields' => 'display_name,profile_image,followers_count,bio',
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Publish post (upload video)
     */
    public function publishPost(string $text, array $options = []): array
    {
        if (empty($options['video_path']) && empty($options['video_url'])) {
            return $this->response(static::RESPONSE_ERROR, [
                'message' => 'Video file or URL required for TikTok',
            ]);
        }

        $result = $this->uploadVideo([
            'caption' => $text,
            'video_path' => $options['video_path'] ?? null,
            'video_url' => $options['video_url'] ?? null,
            'privacy_level' => $options['privacy_level'] ?? 'PUBLIC_TO_EVERYONE',
            'disable_duet' => $options['disable_duet'] ?? false,
            'disable_stitch' => $options['disable_stitch'] ?? false,
            'disable_comment' => $options['disable_comment'] ?? false,
            'video_cover_timestamp_ms' => $options['cover_timestamp'] ?? 0,
        ]);

        if (isset($result['data']['publish_id'])) {
            return $this->response(static::RESPONSE_SUCCESS, [
                'id' => $result['data']['publish_id'],
                'status' => 'processing',
            ]);
        }

        return $this->response(static::RESPONSE_ERROR, $result);
    }

    /**
     * Check publish status
     */
    public function getPublishStatus(string $publishId): array
    {
        $response = $this->apiRequest('post', 'post/publish/status/fetch/', [], [
            'publish_id' => $publishId,
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Get trending hashtags (if available)
     */
    public function getTrendingHashtags(): array
    {
        // Note: This endpoint may require special permissions
        $response = $this->apiRequest('get', 'research/hashtag/trending/');

        return $response['data']['hashtags'] ?? [];
    }
}
