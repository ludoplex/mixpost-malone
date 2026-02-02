<?php

namespace Inovector\Mixpost\SocialProviders\Discord\Concerns;

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
                'identify',
                'email',
                'guilds',
                'bot',
                'applications.commands',
            ]),
            'state' => $state,
            'permissions' => '2147485696', // Send Messages, Embed Links, Attach Files, Manage Events
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

        // Use OAuth2 endpoint (no /v10)
        $response = Http::asForm()->post("https://discord.com/api/oauth2/token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            $data['expires_in'] = time() + ($data['expires_in'] ?? 604800); // Default 7 days
            return $data;
        }

        return ['error' => $data['error_description'] ?? $data['error'] ?? 'Failed to get access token'];
    }

    /**
     * Refresh the access token
     */
    public function refreshToken(): array
    {
        $token = $this->getAccessToken();

        $response = Http::asForm()->post("https://discord.com/api/oauth2/token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $token['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            $data['expires_in'] = time() + ($data['expires_in'] ?? 604800);
            $this->updateToken($data);
            return $data;
        }

        return ['error' => $data['error_description'] ?? 'Failed to refresh token'];
    }

    /**
     * Revoke token
     */
    public function revokeToken(): bool
    {
        $token = $this->getAccessToken();

        $response = Http::asForm()->post("https://discord.com/api/oauth2/token/revoke", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'token' => $token['access_token'],
        ]);

        return $response->successful();
    }

    /**
     * Get bot application info
     */
    public function getApplicationInfo(): array
    {
        return $this->apiRequest('get', 'oauth2/applications/@me');
    }
}
