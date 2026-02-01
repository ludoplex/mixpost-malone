<?php

namespace Inovector\Mixpost\SocialProviders\Twitch\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

trait ManagesOAuth
{
    /**
     * Get the redirect URL for OAuth
     */
    public function getAuthorizationUrl(): string
    {
        $state = Str::random(40);
        $this->request->session()->put('state', $state);

        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'user:read:email',
                'channel:read:stream_key',
                'channel:manage:broadcast',
                'chat:edit',
                'chat:read',
                'moderator:manage:announcements',
            ]),
            'state' => $state,
            'force_verify' => 'true',
        ]);

        return "{$this->oauthBaseUrl}/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token
     */
    public function requestAccessToken(array $params = []): array
    {
        $code = $params['code'] ?? $this->request->get('code');
        
        // Validate state to prevent CSRF
        $sessionState = $this->request->session()->get('state');
        $returnedState = $params['state'] ?? $this->request->get('state');
        if (!$sessionState || $sessionState !== $returnedState) {
            return ['error' => 'Invalid state parameter'];
        }
        $this->request->session()->forget('state');

        $response = Http::asForm()->post("{$this->oauthBaseUrl}/token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            $data['expires_in'] = time() + ($data['expires_in'] ?? 3600);
            return $data;
        }

        return ['error' => $data['message'] ?? 'Failed to get access token'];
    }

    /**
     * Refresh the access token
     */
    public function refreshToken(): array
    {
        $token = $this->getAccessToken();

        $response = Http::asForm()->post("{$this->oauthBaseUrl}/token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $token['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            $data['expires_in'] = time() + ($data['expires_in'] ?? 3600);
            $this->updateToken($data);
            return $data;
        }

        return ['error' => $data['message'] ?? 'Failed to refresh token'];
    }

    /**
     * Validate current token
     */
    public function validateToken(): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "OAuth {$token['access_token']}",
        ])->get("{$this->oauthBaseUrl}/validate");

        return $response->json() ?? [];
    }

    /**
     * Revoke token
     */
    public function revokeToken(): bool
    {
        $token = $this->getAccessToken();

        $response = Http::asForm()->post("{$this->oauthBaseUrl}/revoke", [
            'client_id' => $this->clientId,
            'token' => $token['access_token'],
        ]);

        return $response->successful();
    }
}
