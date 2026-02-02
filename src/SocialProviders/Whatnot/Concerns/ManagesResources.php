<?php

namespace Inovector\Mixpost\SocialProviders\Whatnot\Concerns;

trait ManagesResources
{
    /**
     * Get account (user) data for storing
     */
    public function getAccount(): array
    {
        $user = $this->getUser();

        if (empty($user) || isset($user['error'])) {
            return $this->response(static::RESPONSE_ERROR, ['message' => $user['error'] ?? 'Could not fetch user data']);
        }

        $seller = $this->getSellerProfile();

        return $this->response(static::RESPONSE_SUCCESS, [
            'id' => $user['id'],
            'name' => $user['display_name'] ?? $user['username'],
            'username' => $user['username'],
            'image' => $user['avatar_url'] ?? null,
            'data' => [
                'is_seller' => $seller['is_verified'] ?? false,
                'seller_rating' => $seller['rating'] ?? null,
                'total_sales' => $seller['total_sales'] ?? 0,
                'follower_count' => $user['follower_count'] ?? 0,
                'categories' => $seller['categories'] ?? [],
            ],
        ]);
    }

    /**
     * Get entities (seller profiles) that can be managed
     */
    public function getEntities(): array
    {
        $user = $this->getUser();

        if (empty($user)) {
            return [];
        }

        $seller = $this->getSellerProfile();

        return [
            [
                'id' => $user['id'],
                'name' => $user['display_name'] ?? $user['username'],
                'username' => $user['username'],
                'image' => $user['avatar_url'] ?? null,
                'is_seller' => $seller['is_verified'] ?? false,
            ]
        ];
    }

    /**
     * Get analytics/metrics for the seller
     */
    public function getMetrics(): array
    {
        $user = $this->getUser();
        $seller = $this->getSellerProfile();
        $liveStatus = $this->getLiveStatus();

        return [
            'followers' => $user['follower_count'] ?? 0,
            'total_sales' => $seller['total_sales'] ?? 0,
            'rating' => $seller['rating'] ?? null,
            'is_live' => !empty($liveStatus),
            'viewer_count' => $liveStatus['viewer_count'] ?? 0,
            'upcoming_shows' => count($this->getUpcomingShows()['shows'] ?? []),
        ];
    }

    /**
     * Get upcoming shows for the seller
     */
    public function getShows(): array
    {
        $upcoming = $this->getUpcomingShows();
        $scheduled = $this->getScheduledShows();

        return [
            'upcoming' => $upcoming['shows'] ?? [],
            'scheduled' => $scheduled['shows'] ?? [],
        ];
    }

    /**
     * Delete a listing or show
     */
    public function deletePost(string $postId): array
    {
        // Determine if it's a listing or show based on prefix
        if (str_starts_with($postId, 'show_')) {
            $endpoint = 'shows/' . str_replace('show_', '', $postId);
        } else {
            $endpoint = 'listings/' . str_replace('listing_', '', $postId);
        }

        $response = $this->apiRequest('delete', $endpoint);

        if (isset($response['error'])) {
            return $this->response(static::RESPONSE_ERROR, $response);
        }

        return $this->response(static::RESPONSE_SUCCESS, ['deleted' => true]);
    }

    /**
     * Get post/listing details
     */
    public function getPost(string $postId): array
    {
        if (str_starts_with($postId, 'show_')) {
            $endpoint = 'shows/' . str_replace('show_', '', $postId);
        } else {
            $endpoint = 'listings/' . str_replace('listing_', '', $postId);
        }

        $response = $this->apiRequest('get', $endpoint);

        if (isset($response['error'])) {
            return $this->response(static::RESPONSE_ERROR, $response);
        }

        return $this->response(static::RESPONSE_SUCCESS, $response);
    }

    /**
     * Get active listings
     */
    public function getActiveListings(): array
    {
        return $this->apiRequest('get', 'listings?status=active');
    }

    /**
     * Get sold items
     */
    public function getSoldItems(int $limit = 50): array
    {
        return $this->apiRequest('get', "listings?status=sold&limit={$limit}");
    }
}
