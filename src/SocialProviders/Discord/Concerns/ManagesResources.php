<?php

namespace Inovector\Mixpost\SocialProviders\Discord\Concerns;

trait ManagesResources
{
    /**
     * Get account (user) data for storing
     */
    public function getAccount(): array
    {
        $user = $this->getUser();

        if (empty($user) || isset($user['code'])) {
            return $this->response(static::RESPONSE_ERROR, ['message' => $user['message'] ?? 'Could not fetch user data']);
        }

        $avatarUrl = null;
        if (!empty($user['avatar'])) {
            $ext = str_starts_with($user['avatar'], 'a_') ? 'gif' : 'png';
            $avatarUrl = "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.{$ext}";
        }

        return $this->response(static::RESPONSE_SUCCESS, [
            'id' => $user['id'],
            'name' => $user['global_name'] ?? $user['username'],
            'username' => $user['username'],
            'image' => $avatarUrl,
            'data' => [
                'discriminator' => $user['discriminator'] ?? '0',
                'email' => $user['email'] ?? null,
                'verified' => $user['verified'] ?? false,
                'flags' => $user['flags'] ?? 0,
            ],
        ]);
    }

    /**
     * Get entities (servers and channels) that can be managed
     */
    public function getEntities(): array
    {
        $guilds = $this->getGuilds();
        $entities = [];

        foreach ($guilds as $guild) {
            // Check if user has manage messages or administrator permission
            $permissions = $guild['permissions'] ?? 0;
            $canPost = ($permissions & 0x800) || ($permissions & 0x8); // SEND_MESSAGES or ADMINISTRATOR

            if (!$canPost) {
                continue;
            }

            $iconUrl = null;
            if (!empty($guild['icon'])) {
                $iconUrl = "https://cdn.discordapp.com/icons/{$guild['id']}/{$guild['icon']}.png";
            }

            // Get text channels for this guild
            $channels = $this->getGuildChannels($guild['id']);
            
            foreach ($channels as $channel) {
                $entities[] = [
                    'id' => $channel['id'],
                    'name' => "#{$channel['name']} ({$guild['name']})",
                    'username' => $channel['name'],
                    'image' => $iconUrl,
                    'data' => [
                        'guild_id' => $guild['id'],
                        'guild_name' => $guild['name'],
                        'channel_id' => $channel['id'],
                        'channel_name' => $channel['name'],
                        'channel_type' => $channel['type'],
                    ],
                ];
            }
        }

        return $entities;
    }

    /**
     * Get analytics/metrics for a guild
     */
    public function getMetrics(string $guildId = null): array
    {
        if (!$guildId) {
            return [];
        }

        $guild = $this->apiRequest('get', "guilds/{$guildId}?with_counts=true", [], true);

        return [
            'member_count' => $guild['approximate_member_count'] ?? 0,
            'online_count' => $guild['approximate_presence_count'] ?? 0,
            'boost_level' => $guild['premium_tier'] ?? 0,
            'boost_count' => $guild['premium_subscription_count'] ?? 0,
        ];
    }

    /**
     * Delete a post (message)
     */
    public function deletePost(string $postId): array
    {
        // postId format: channelId:messageId
        $parts = explode(':', $postId);
        if (count($parts) !== 2) {
            return $this->response(static::RESPONSE_ERROR, ['message' => 'Invalid post ID format']);
        }

        [$channelId, $messageId] = $parts;
        $success = $this->deleteMessage($channelId, $messageId);

        if ($success) {
            return $this->response(static::RESPONSE_SUCCESS, ['deleted' => true]);
        }

        return $this->response(static::RESPONSE_ERROR, ['message' => 'Failed to delete message']);
    }

    /**
     * Get post (message) details
     */
    public function getPost(string $postId): array
    {
        $parts = explode(':', $postId);
        if (count($parts) !== 2) {
            return $this->response(static::RESPONSE_ERROR, ['message' => 'Invalid post ID format']);
        }

        [$channelId, $messageId] = $parts;
        $message = $this->apiRequest('get', "channels/{$channelId}/messages/{$messageId}", [], true);

        if (isset($message['id'])) {
            return $this->response(static::RESPONSE_SUCCESS, $message);
        }

        return $this->response(static::RESPONSE_ERROR, $message);
    }

    /**
     * Get recent messages from a channel
     */
    public function getChannelMessages(string $channelId, int $limit = 50): array
    {
        return $this->apiRequest('get', "channels/{$channelId}/messages?limit={$limit}", [], true);
    }

    /**
     * Get scheduled events for a guild
     */
    public function getScheduledEvents(string $guildId): array
    {
        return $this->apiRequest('get', "guilds/{$guildId}/scheduled-events", [], true);
    }
}
