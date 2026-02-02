<?php

namespace Inovector\Mixpost\SocialProviders\TikTok\Concerns;

trait ManagesResources
{
    /**
     * Get account (user) data for storing
     */
    public function getAccount(): array
    {
        $user = $this->getUser();

        if (empty($user)) {
            return $this->response(static::RESPONSE_ERROR, ['message' => 'Could not fetch user data']);
        }

        return $this->response(static::RESPONSE_SUCCESS, [
            'id' => $user['open_id'],
            'name' => $user['display_name'] ?? 'TikTok User',
            'username' => $user['display_name'] ?? $user['open_id'],
            'image' => $user['avatar_url'] ?? $user['avatar_large_url'] ?? null,
            'data' => [
                'union_id' => $user['union_id'] ?? null,
                'bio' => $user['bio_description'] ?? '',
                'is_verified' => $user['is_verified'] ?? false,
                'follower_count' => $user['follower_count'] ?? 0,
                'following_count' => $user['following_count'] ?? 0,
                'likes_count' => $user['likes_count'] ?? 0,
                'video_count' => $user['video_count'] ?? 0,
                'profile_url' => $user['profile_deep_link'] ?? null,
            ],
        ]);
    }

    /**
     * Get entities (TikTok only has one account per auth)
     */
    public function getEntities(): array
    {
        $user = $this->getUser();

        if (empty($user)) {
            return [];
        }

        return [
            [
                'id' => $user['open_id'],
                'name' => $user['display_name'] ?? 'TikTok User',
                'username' => $user['display_name'] ?? $user['open_id'],
                'image' => $user['avatar_url'] ?? null,
            ]
        ];
    }

    /**
     * Get analytics/metrics for the account
     */
    public function getMetrics(): array
    {
        $user = $this->getUser();

        return [
            'followers' => $user['follower_count'] ?? 0,
            'following' => $user['following_count'] ?? 0,
            'likes' => $user['likes_count'] ?? 0,
            'videos' => $user['video_count'] ?? 0,
            'is_verified' => $user['is_verified'] ?? false,
        ];
    }

    /**
     * Delete a video (TikTok doesn't support deletion via API)
     */
    public function deletePost(string $videoId): array
    {
        return $this->response(static::RESPONSE_ERROR, [
            'message' => 'TikTok does not support video deletion via API',
        ]);
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
     * Get recent videos
     */
    public function getRecentVideos(int $count = 10): array
    {
        $data = $this->getVideos($count);

        return $data['videos'] ?? [];
    }

    /**
     * Get video analytics (requires business account)
     */
    public function getVideoAnalytics(string $videoId): array
    {
        $response = $this->apiRequest('post', 'video/query/', [], [
            'filters' => [
                'video_ids' => [$videoId],
            ],
            'fields' => 'id,view_count,like_count,comment_count,share_count',
        ]);

        return $response['data']['videos'][0] ?? [];
    }
}
