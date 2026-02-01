<?php

namespace Inovector\Mixpost\SocialProviders\TikTok\Concerns;

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

        // Generate code verifier and challenge for PKCE
        $codeVerifier = Str::random(64);
        $this->request->session()->put('code_verifier', $codeVerifier);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $params = http_build_query([
            'client_key' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => implode(',', [
                'user.info.basic',
                'user.info.profile',
                'user.info.stats',
                'video.list',
                'video.upload',
                'video.publish',
            ]),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "{$this->oauthBaseUrl}?{$params}";
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
        
        $codeVerifier = $this->request->session()->get('code_verifier');

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
            'code_verifier' => $codeVerifier,
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            $data['expires_in'] = time() + ($data['expires_in'] ?? 86400);
            // Clear code verifier
            $this->request->session()->forget('code_verifier');
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

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $token['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            $data['expires_in'] = time() + ($data['expires_in'] ?? 86400);
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

        $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/revoke/', [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'token' => $token['access_token'],
        ]);

        return $response->successful();
    }
}
