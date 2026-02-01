<?php

namespace Inovector\Mixpost\SocialProviders\YouTube\Concerns;

use Illuminate\Support\Facades\Http;

trait ManagesUploads
{
    /**
     * Validate URL is safe (prevent SSRF)
     */
    protected function isValidVideoUrl(string $url): bool
    {
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return false;
        }
        
        // Only allow http/https
        if (!in_array($parsed['scheme'], ['http', 'https'], true)) {
            return false;
        }
        
        // Block private/internal IPs
        $ip = gethostbyname($parsed['host']);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        
        return true;
    }

    /**
     * Upload a video to YouTube
     */
    public function uploadVideo(array $data): array
    {
        $token = $this->getAccessToken();

        // Prepare video metadata
        $metadata = [
            'snippet' => [
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'tags' => $data['tags'] ?? [],
                'categoryId' => $data['category_id'] ?? '22',
            ],
            'status' => [
                'privacyStatus' => $data['privacy_status'] ?? 'public',
                'selfDeclaredMadeForKids' => $data['made_for_kids'] ?? false,
            ],
        ];

        // If scheduled, set publish time
        if (!empty($data['publish_at'])) {
            $metadata['status']['privacyStatus'] = 'private';
            $metadata['status']['publishAt'] = $data['publish_at'];
        }

        // Get video content - prefer chunked upload for large files
        $videoContent = null;
        
        if (!empty($data['video_path']) && file_exists($data['video_path'])) {
            // For large files, use chunked upload instead
            $fileSize = filesize($data['video_path']);
            if ($fileSize > 100 * 1024 * 1024) { // > 100MB
                return $this->uploadVideoChunked($data);
            }
            $videoContent = file_get_contents($data['video_path']);
        } elseif (!empty($data['video_url'])) {
            // Validate URL to prevent SSRF
            if (!$this->isValidVideoUrl($data['video_url'])) {
                return ['error' => 'Invalid or disallowed video URL'];
            }
            // Use HTTP client instead of file_get_contents for better error handling
            $response = Http::timeout(300)->get($data['video_url']);
            if (!$response->successful()) {
                return ['error' => 'Failed to fetch video from URL', 'status' => $response->status()];
            }
            $videoContent = $response->body();
        }

        if (!$videoContent) {
            return ['error' => 'Could not read video file'];
        }

        // Initiate resumable upload
        $initResponse = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Content-Type' => 'application/json',
            'X-Upload-Content-Type' => 'video/*',
            'X-Upload-Content-Length' => strlen($videoContent),
        ])->post("{$this->uploadUrl}/videos?uploadType=resumable&part=snippet,status", $metadata);

        if (!$initResponse->successful()) {
            return ['error' => 'Failed to initiate upload', 'details' => $initResponse->json()];
        }

        $uploadUrl = $initResponse->header('Location');

        if (!$uploadUrl) {
            return ['error' => 'No upload URL returned'];
        }

        // Upload the video content
        $uploadResponse = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Content-Type' => 'video/*',
        ])->withBody($videoContent, 'video/*')->put($uploadUrl);

        $result = $uploadResponse->json();

        if (isset($result['id'])) {
            // Upload thumbnail if provided
            if (!empty($data['thumbnail_path'])) {
                $this->uploadThumbnail($result['id'], $data['thumbnail_path']);
            }

            return $result;
        }

        return ['error' => 'Upload failed', 'details' => $result];
    }

    /**
     * Upload video thumbnail
     */
    public function uploadThumbnail(string $videoId, string $imagePath): array
    {
        $token = $this->getAccessToken();

        if (!file_exists($imagePath)) {
            return ['error' => 'Thumbnail file not found'];
        }

        $imageContent = file_get_contents($imagePath);
        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Content-Type' => $mimeType,
        ])->withBody($imageContent, $mimeType)
          ->post("{$this->uploadUrl}/thumbnails/set?videoId={$videoId}");

        return $response->json() ?? [];
    }

    /**
     * Upload video using chunked upload (for large files)
     */
    public function uploadVideoChunked(array $data, int $chunkSize = 10485760): array
    {
        $token = $this->getAccessToken();

        $videoPath = $data['video_path'] ?? null;
        if (!$videoPath || !file_exists($videoPath)) {
            return ['error' => 'Video file not found'];
        }

        $fileSize = filesize($videoPath);

        // Prepare metadata
        $metadata = [
            'snippet' => [
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'tags' => $data['tags'] ?? [],
                'categoryId' => $data['category_id'] ?? '22',
            ],
            'status' => [
                'privacyStatus' => $data['privacy_status'] ?? 'public',
            ],
        ];

        // Initiate resumable upload
        $initResponse = Http::withHeaders([
            'Authorization' => "Bearer {$token['access_token']}",
            'Content-Type' => 'application/json',
            'X-Upload-Content-Type' => 'video/*',
            'X-Upload-Content-Length' => $fileSize,
        ])->post("{$this->uploadUrl}/videos?uploadType=resumable&part=snippet,status", $metadata);

        $uploadUrl = $initResponse->header('Location');

        if (!$uploadUrl) {
            return ['error' => 'Failed to initiate chunked upload'];
        }

        // Upload in chunks with proper resource cleanup
        $handle = fopen($videoPath, 'rb');
        if (!$handle) {
            return ['error' => 'Failed to open video file'];
        }

        try {
            $offset = 0;

            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $chunkLength = strlen($chunk);
                $endByte = $offset + $chunkLength - 1;

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$token['access_token']}",
                    'Content-Type' => 'video/*',
                    'Content-Length' => $chunkLength,
                    'Content-Range' => "bytes {$offset}-{$endByte}/{$fileSize}",
                ])->withBody($chunk, 'video/*')->put($uploadUrl);

                if ($response->status() === 200 || $response->status() === 201) {
                    // Upload complete
                    $result = $response->json();

                    if (!empty($data['thumbnail_path'])) {
                        $this->uploadThumbnail($result['id'], $data['thumbnail_path']);
                    }

                    return $result;
                } elseif ($response->status() === 308) {
                    // Resume incomplete, continue
                    $offset = $endByte + 1;
                } else {
                    return ['error' => 'Chunk upload failed', 'details' => $response->json()];
                }
            }

            return ['error' => 'Upload incomplete'];
        } finally {
            fclose($handle);
        }
    }

    /**
     * Get upload quota/limits
     */
    public function getUploadLimits(): array
    {
        return [
            'max_file_size' => '256GB',
            'max_duration' => '12 hours',
            'daily_upload_limit' => 'Varies by channel',
            'supported_formats' => [
                'mov', 'mpeg4', 'mp4', 'avi', 'wmv', 'mpegps', 
                'flv', '3gpp', 'webm', 'dnxhr', 'prores', 'cineform', 'hevc'
            ],
        ];
    }
}
