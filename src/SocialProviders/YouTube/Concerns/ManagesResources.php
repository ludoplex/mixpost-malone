<?php

namespace Inovector\Mixpost\SocialProviders\YouTube\Concerns;

trait ManagesResources
{
    /**
     * Get account (channel) data for storing
     */
    public function getAccount(): array
    {
        $channel = $this->getChannel();

        if (empty($channel)) {
            return $this->response(static::RESPONSE_ERROR, ['message' => 'Could not fetch channel data']);
        }

        $snippet = $channel['snippet'] ?? [];
        $statistics = $channel['statistics'] ?? [];

        return $this->response(static::RESPONSE_SUCCESS, [
            'id' => $channel['id'],
            'name' => $snippet['title'] ?? 'Unknown Channel',
            'username' => $snippet['customUrl'] ?? $channel['id'],
            'image' => $snippet['thumbnails']['default']['url'] ?? null,
            'data' => [
                'description' => $snippet['description'] ?? '',
                'subscriber_count' => $statistics['subscriberCount'] ?? 0,
                'video_count' => $statistics['videoCount'] ?? 0,
                'view_count' => $statistics['viewCount'] ?? 0,
                'country' => $snippet['country'] ?? null,
                'published_at' => $snippet['publishedAt'] ?? null,
            ],
        ]);
    }

    /**
     * Get entities (channels) that can be managed
     */
    public function getEntities(): array
    {
        $channel = $this->getChannel();

        if (empty($channel)) {
            return [];
        }

        $snippet = $channel['snippet'] ?? [];

        return [
            [
                'id' => $channel['id'],
                'name' => $snippet['title'] ?? 'Unknown Channel',
                'username' => $snippet['customUrl'] ?? $channel['id'],
                'image' => $snippet['thumbnails']['default']['url'] ?? null,
            ]
        ];
    }

    /**
     * Get analytics/metrics for the channel
     */
    public function getMetrics(): array
    {
        $channel = $this->getChannel();
        $statistics = $channel['statistics'] ?? [];

        return [
            'subscribers' => (int)($statistics['subscriberCount'] ?? 0),
            'videos' => (int)($statistics['videoCount'] ?? 0),
            'views' => (int)($statistics['viewCount'] ?? 0),
            'hidden_subscribers' => $statistics['hiddenSubscriberCount'] ?? false,
        ];
    }

    /**
     * Delete a video
     */
    public function deletePost(string $videoId): array
    {
        $response = $this->apiRequest('delete', 'videos', ['id' => $videoId]);

        // YouTube returns empty response on success
        if (empty($response) || !isset($response['error'])) {
            return $this->response(static::RESPONSE_SUCCESS, ['deleted' => true]);
        }

        return $this->response(static::RESPONSE_ERROR, $response);
    }

    /**
     * Get video details
     */
    public function getPost(string $videoId): array
    {
        $video = $this->getVideo($videoId);

        if (empty($video)) {
            return $this->response(static::RESPONSE_ERROR, ['message' => 'Video not found']);
        }

        return $this->response(static::RESPONSE_SUCCESS, $video);
    }

    /**
     * Get playlists
     */
    public function getPlaylists(): array
    {
        $response = $this->apiRequest('get', 'playlists', [
            'part' => 'snippet,contentDetails',
            'mine' => 'true',
            'maxResults' => 50,
        ]);

        return $response['items'] ?? [];
    }

    /**
     * Add video to playlist
     */
    public function addToPlaylist(string $playlistId, string $videoId): array
    {
        return $this->apiRequest('post', 'playlistItems', [
            'part' => 'snippet',
        ], [
            'snippet' => [
                'playlistId' => $playlistId,
                'resourceId' => [
                    'kind' => 'youtube#video',
                    'videoId' => $videoId,
                ],
            ],
        ]);
    }

    /**
     * Get video categories
     */
    public function getCategories(string $regionCode = 'US'): array
    {
        $response = $this->apiRequest('get', 'videoCategories', [
            'part' => 'snippet',
            'regionCode' => $regionCode,
        ]);

        return $response['items'] ?? [];
    }
}
