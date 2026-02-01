<?php

namespace Inovector\Mixpost\SocialProviders\Twitch\Concerns;

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
            'id' => $user['id'],
            'name' => $user['display_name'],
            'username' => $user['login'],
            'image' => $user['profile_image_url'] ?? null,
            'data' => [
                'broadcaster_type' => $user['broadcaster_type'] ?? 'none',
                'description' => $user['description'] ?? '',
                'view_count' => $user['view_count'] ?? 0,
                'email' => $user['email'] ?? null,
            ],
        ]);
    }

    /**
     * Get entities (channels) that can be managed
     * For Twitch, users typically manage their own channel
     */
    public function getEntities(): array
    {
        $user = $this->getUser();

        if (empty($user)) {
            return [];
        }

        return [
            [
                'id' => $user['id'],
                'name' => $user['display_name'],
                'username' => $user['login'],
                'image' => $user['profile_image_url'] ?? null,
            ]
        ];
    }

    /**
     * Get analytics/metrics for the channel
     */
    public function getMetrics(): array
    {
        $user = $this->getUser();
        
        if (empty($user)) {
            return [];
        }

        $channel = $this->getChannel($user['id']);
        $stream = $this->getStreamStatus($user['id']);

        return [
            'followers' => $this->getFollowerCount($user['id']),
            'is_live' => !empty($stream),
            'viewer_count' => $stream['viewer_count'] ?? 0,
            'game' => $channel['game_name'] ?? null,
            'title' => $channel['title'] ?? null,
        ];
    }

    /**
     * Get follower count
     */
    protected function getFollowerCount(string $broadcasterId): int
    {
        $response = $this->apiRequest('get', "channels/followers?broadcaster_id={$broadcasterId}&first=1");
        return $response['total'] ?? 0;
    }

    /**
     * Delete a post (not applicable for Twitch announcements)
     */
    public function deletePost(string $postId): array
    {
        // Twitch announcements cannot be deleted via API
        return $this->response(static::RESPONSE_ERROR, [
            'message' => 'Twitch announcements cannot be deleted via API'
        ]);
    }

    /**
     * Get post details (not directly applicable for Twitch)
     */
    public function getPost(string $postId): array
    {
        return $this->response(static::RESPONSE_ERROR, [
            'message' => 'Twitch does not support retrieving individual announcements'
        ]);
    }
}
