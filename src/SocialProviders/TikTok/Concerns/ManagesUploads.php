<?php

namespace Inovector\Mixpost\SocialProviders\TikTok\Concerns;

use Illuminate\Support\Facades\Http;

trait ManagesUploads
{
    /**
     * Initialize video upload
     */
    protected function initVideoUpload(int $fileSize, int $chunkSize = 10485760): array
    {
        $totalChunks = ceil($fileSize / $chunkSize);

        $response = $this->apiRequest('post', 'post/publish/video/init/', [], [
            'post_info' => [
                'title' => '', // Set during finalize
                'privacy_level' => 'PUBLIC_TO_EVERYONE',
            ],
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'video_size' => $fileSize,
                'chunk_size' => $chunkSize,
                'total_chunk_count' => $totalChunks,
            ],
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Upload video chunk
     */
    protected function uploadChunk(string $uploadUrl, string $chunk, int $chunkIndex, int $totalChunks): bool
    {
        $response = Http::withHeaders([
            'Content-Type' => 'video/mp4',
            'Content-Range' => "bytes {$chunkIndex}-" . ($chunkIndex + strlen($chunk) - 1) . "/*",
        ])->withBody($chunk, 'video/mp4')->put($uploadUrl);

        return $response->successful();
    }

    /**
     * Upload a video to TikTok
     */
    public function uploadVideo(array $data): array
    {
        $videoPath = $data['video_path'] ?? null;
        $videoUrl = $data['video_url'] ?? null;

        // Method 1: Direct post (for video URLs)
        if ($videoUrl) {
            return $this->postVideoFromUrl($data);
        }

        // Method 2: File upload
        if ($videoPath && file_exists($videoPath)) {
            return $this->postVideoFromFile($data);
        }

        return ['error' => 'No valid video source provided'];
    }

    /**
     * Post video from URL
     */
    protected function postVideoFromUrl(array $data): array
    {
        $response = $this->apiRequest('post', 'post/publish/video/init/', [], [
            'post_info' => [
                'title' => $data['caption'] ?? '',
                'privacy_level' => $data['privacy_level'] ?? 'PUBLIC_TO_EVERYONE',
                'disable_duet' => $data['disable_duet'] ?? false,
                'disable_stitch' => $data['disable_stitch'] ?? false,
                'disable_comment' => $data['disable_comment'] ?? false,
                'video_cover_timestamp_ms' => $data['video_cover_timestamp_ms'] ?? 0,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'video_url' => $data['video_url'],
            ],
        ]);

        return $response;
    }

    /**
     * Post video from file (chunked upload)
     */
    protected function postVideoFromFile(array $data): array
    {
        $videoPath = $data['video_path'];
        $fileSize = filesize($videoPath);
        $chunkSize = 10485760; // 10MB chunks

        // Initialize upload
        $initResponse = $this->apiRequest('post', 'post/publish/video/init/', [], [
            'post_info' => [
                'title' => $data['caption'] ?? '',
                'privacy_level' => $data['privacy_level'] ?? 'PUBLIC_TO_EVERYONE',
                'disable_duet' => $data['disable_duet'] ?? false,
                'disable_stitch' => $data['disable_stitch'] ?? false,
                'disable_comment' => $data['disable_comment'] ?? false,
                'video_cover_timestamp_ms' => $data['video_cover_timestamp_ms'] ?? 0,
            ],
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'video_size' => $fileSize,
                'chunk_size' => $chunkSize,
                'total_chunk_count' => ceil($fileSize / $chunkSize),
            ],
        ]);

        if (!isset($initResponse['data']['upload_url'])) {
            return ['error' => 'Failed to initialize upload', 'details' => $initResponse];
        }

        $uploadUrl = $initResponse['data']['upload_url'];
        $publishId = $initResponse['data']['publish_id'];

        // Upload file in chunks
        $handle = fopen($videoPath, 'rb');
        $offset = 0;

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            $chunkLength = strlen($chunk);
            $endByte = $offset + $chunkLength - 1;

            $response = Http::withHeaders([
                'Content-Type' => 'video/mp4',
                'Content-Length' => $chunkLength,
                'Content-Range' => "bytes {$offset}-{$endByte}/{$fileSize}",
            ])->withBody($chunk, 'video/mp4')->put($uploadUrl);

            if (!$response->successful() && $response->status() !== 308) {
                fclose($handle);
                return ['error' => 'Chunk upload failed', 'details' => $response->json()];
            }

            $offset = $endByte + 1;
        }

        fclose($handle);

        return [
            'data' => [
                'publish_id' => $publishId,
            ],
        ];
    }

    /**
     * Upload photo carousel (TikTok photo mode)
     */
    public function uploadPhotoCarousel(array $data): array
    {
        $photos = $data['photos'] ?? [];

        if (empty($photos) || count($photos) > 35) {
            return ['error' => 'Photo carousel requires 1-35 images'];
        }

        // Initialize photo post
        $response = $this->apiRequest('post', 'post/publish/content/init/', [], [
            'post_info' => [
                'title' => $data['caption'] ?? '',
                'privacy_level' => $data['privacy_level'] ?? 'PUBLIC_TO_EVERYONE',
                'disable_comment' => $data['disable_comment'] ?? false,
            ],
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'photo_cover_index' => $data['cover_index'] ?? 0,
                'photo_images' => array_map(fn($p) => ['image_url' => $p], $photos),
            ],
            'post_mode' => 'DIRECT_POST',
            'media_type' => 'PHOTO',
        ]);

        return $response;
    }

    /**
     * Get upload limits
     */
    public function getUploadLimits(): array
    {
        return [
            'max_video_size' => '4GB',
            'max_video_duration' => '10 minutes',
            'min_video_duration' => '1 second',
            'supported_formats' => ['mp4', 'webm', 'mov'],
            'max_photos' => 35,
            'supported_image_formats' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_caption_length' => 2200,
        ];
    }
}
