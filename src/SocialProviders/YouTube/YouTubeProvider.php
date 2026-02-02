<?php

namespace Inovector\Mixpost\SocialProviders\YouTube;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\SocialProviders\YouTube\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\YouTube\Concerns\ManagesResources;
use Inovector\Mixpost\SocialProviders\YouTube\Concerns\ManagesUploads;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class YouTubeProvider extends SocialProvider
{
    use ManagesOAuth;
    use ManagesResources;
    use ManagesUploads;

    public bool $onlyUserAccount = false;

    public array $callbackResponseKeys = ['code', 'state'];

    protected string $apiBaseUrl = 'https://www.googleapis.com/youtube/v3';
    protected string $uploadUrl = 'https://www.googleapis.com/upload/youtube/v3';
    protected string $oauthBaseUrl = 'https://accounts.google.com/o/oauth2/v2';
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';

    public static function name(): string
    {
        return 'YouTube';
    }

    public static function service(): string
    {
        return \Inovector\Mixpost\Services\YouTubeService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(false)
            ->minTextChar(1)
            ->maxTextChar(5000) // YouTube description limit
            ->minPhotos(0)
            ->maxPhotos(1) // Thumbnail
            ->minVideos(1)
            ->maxVideos(1)
            ->minGifs(0)
            ->maxGifs(0)
            ->allowMixingMediaTypes(false);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        $videoId = $accountResource->pivot->provider_post_id ?? '';
        return "https://www.youtube.com/watch?v={$videoId}";
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

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Accept' => 'application/json',
        ])->{$method}($url, $body);

        return $response->json() ?? [];
    }

    /**
     * Get current user's channel info
     */
    public function getChannel(): array
    {
        $response = $this->apiRequest('get', 'channels', [
            'part' => 'snippet,statistics,contentDetails',
            'mine' => 'true',
        ]);

        // Check for API errors
        if (isset($response['error'])) {
            return ['error' => $response['error']['message'] ?? 'Unknown API error'];
        }

        return $response['items'][0] ?? ['error' => 'No channel found'];
    }

    /**
     * Get channel by ID
     */
    public function getChannelById(string $channelId): array
    {
        $response = $this->apiRequest('get', 'channels', [
            'part' => 'snippet,statistics,contentDetails',
            'id' => $channelId,
        ]);

        return $response['items'][0] ?? [];
    }

    /**
     * Get channel videos
     */
    public function getVideos(string $channelId, int $maxResults = 10): array
    {
        $response = $this->apiRequest('get', 'search', [
            'part' => 'snippet',
            'channelId' => $channelId,
            'type' => 'video',
            'order' => 'date',
            'maxResults' => $maxResults,
        ]);

        return $response['items'] ?? [];
    }

    /**
     * Get video details
     */
    public function getVideo(string $videoId): array
    {
        $response = $this->apiRequest('get', 'videos', [
            'part' => 'snippet,statistics,status,contentDetails',
            'id' => $videoId,
        ]);

        return $response['items'][0] ?? [];
    }

    /**
     * Update video metadata
     */
    public function updateVideo(string $videoId, array $data): array
    {
        $video = $this->getVideo($videoId);
        
        $body = [
            'id' => $videoId,
            'snippet' => array_merge($video['snippet'] ?? [], [
                'title' => $data['title'] ?? $video['snippet']['title'],
                'description' => $data['description'] ?? $video['snippet']['description'],
                'tags' => $data['tags'] ?? $video['snippet']['tags'] ?? [],
                'categoryId' => $data['category_id'] ?? $video['snippet']['categoryId'] ?? '22',
            ]),
        ];

        if (isset($data['privacy_status'])) {
            $body['status'] = [
                'privacyStatus' => $data['privacy_status'],
            ];
        }

        return $this->apiRequest('put', 'videos', ['part' => 'snippet,status'], $body);
    }

    /**
     * Create community post (requires channel to have community tab)
     */
    public function createCommunityPost(string $text): array
    {
        // Note: YouTube Data API doesn't fully support community posts
        // This would require the YouTube Studio API which is not public
        return $this->response(static::RESPONSE_ERROR, [
            'message' => 'Community posts require YouTube Studio API (not publicly available)',
        ]);
    }

    /**
     * Create a live broadcast
     */
    public function createLiveBroadcast(array $data): array
    {
        $broadcast = $this->apiRequest('post', 'liveBroadcasts', [
            'part' => 'snippet,status,contentDetails',
        ], [
            'snippet' => [
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'scheduledStartTime' => $data['scheduled_start_time'],
            ],
            'status' => [
                'privacyStatus' => $data['privacy_status'] ?? 'public',
                'selfDeclaredMadeForKids' => $data['made_for_kids'] ?? false,
            ],
            'contentDetails' => [
                'enableAutoStart' => $data['auto_start'] ?? false,
                'enableAutoStop' => $data['auto_stop'] ?? true,
            ],
        ]);

        return $broadcast;
    }

    /**
     * Publish post (upload video)
     */
    public function publishPost(string $text, array $options = []): array
    {
        if (empty($options['video_path']) && empty($options['video_url'])) {
            return $this->response(static::RESPONSE_ERROR, [
                'message' => 'Video file or URL required for YouTube',
            ]);
        }

        $title = $options['title'] ?? substr($text, 0, 100);
        $description = $options['description'] ?? $text;

        $result = $this->uploadVideo([
            'title' => $title,
            'description' => $description,
            'tags' => $options['tags'] ?? [],
            'category_id' => $options['category_id'] ?? '22', // People & Blogs
            'privacy_status' => $options['privacy_status'] ?? 'public',
            'video_path' => $options['video_path'] ?? null,
            'video_url' => $options['video_url'] ?? null,
            'thumbnail_path' => $options['thumbnail_path'] ?? null,
        ]);

        if (isset($result['id'])) {
            return $this->response(static::RESPONSE_SUCCESS, [
                'id' => $result['id'],
                'url' => "https://www.youtube.com/watch?v={$result['id']}",
            ]);
        }

        return $this->response(static::RESPONSE_ERROR, $result);
    }
}
