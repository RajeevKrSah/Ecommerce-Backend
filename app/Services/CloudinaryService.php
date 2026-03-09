<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected $cloudinary;
    protected $uploadApi;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);

        $this->uploadApi = new UploadApi();
    }

    /**
     * Upload image to Cloudinary
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     */
    public function uploadImage(UploadedFile $file, string $folder = 'products', array $options = []): array
    {
        try {
            $defaultOptions = [
                'folder' => $folder,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto:good',
                    'fetch_format' => 'auto',
                ],
                'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            ];

            $uploadOptions = array_merge($defaultOptions, $options);

            $result = $this->uploadApi->upload(
                $file->getRealPath(),
                $uploadOptions
            );

            Log::info('Image uploaded to Cloudinary', [
                'public_id' => $result['public_id'],
                'url' => $result['secure_url'],
                'format' => $result['format'],
                'size' => $result['bytes'],
            ]);

            return [
                'success' => true,
                'public_id' => $result['public_id'],
                'url' => $result['secure_url'],
                'thumbnail_url' => $this->generateThumbnailUrl($result['public_id']),
                'width' => $result['width'],
                'height' => $result['height'],
                'format' => $result['format'],
                'size' => $result['bytes'],
                'created_at' => $result['created_at'],
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);

            throw new \Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple images
     *
     * @param array $files
     * @param string $folder
     * @return array
     */
    public function uploadMultipleImages(array $files, string $folder = 'products'): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $results[] = $this->uploadImage($file, $folder);
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => count($results) > 0,
            'uploaded' => $results,
            'errors' => $errors,
            'total' => count($files),
            'successful' => count($results),
            'failed' => count($errors),
        ];
    }

    /**
     * Delete image from Cloudinary
     *
     * @param string $publicId
     * @return bool
     */
    public function deleteImage(string $publicId): bool
    {
        try {
            $result = $this->uploadApi->destroy($publicId, [
                'resource_type' => 'image',
                'invalidate' => true,
            ]);

            Log::info('Image deleted from Cloudinary', [
                'public_id' => $publicId,
                'result' => $result['result'],
            ]);

            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed', [
                'error' => $e->getMessage(),
                'public_id' => $publicId,
            ]);

            return false;
        }
    }

    /**
     * Delete multiple images
     *
     * @param array $publicIds
     * @return array
     */
    public function deleteMultipleImages(array $publicIds): array
    {
        $results = [];

        foreach ($publicIds as $publicId) {
            $results[$publicId] = $this->deleteImage($publicId);
        }

        return $results;
    }

    /**
     * Generate thumbnail URL with transformations
     *
     * @param string $publicId
     * @param int $width
     * @param int $height
     * @return string
     */
    public function generateThumbnailUrl(string $publicId, int $width = 300, int $height = 300): string
    {
        return $this->cloudinary->image($publicId)
            ->resize(\Cloudinary\Transformation\Resize::fill($width, $height))
            ->delivery(\Cloudinary\Transformation\Quality::auto())
            ->delivery(\Cloudinary\Transformation\Format::auto())
            ->toUrl();
    }

    /**
     * Generate optimized URL with transformations
     *
     * @param string $publicId
     * @param array $transformations
     * @return string
     */
    public function generateOptimizedUrl(string $publicId, array $transformations = []): string
    {
        $image = $this->cloudinary->image($publicId);

        // Apply default optimizations
        $image->delivery(\Cloudinary\Transformation\Quality::auto())
              ->delivery(\Cloudinary\Transformation\Format::auto());

        // Apply custom transformations
        if (isset($transformations['width']) && isset($transformations['height'])) {
            $image->resize(\Cloudinary\Transformation\Resize::fill(
                $transformations['width'],
                $transformations['height']
            ));
        }

        return $image->toUrl();
    }

    /**
     * Get image details
     *
     * @param string $publicId
     * @return array|null
     */
    public function getImageDetails(string $publicId): ?array
    {
        try {
            $result = $this->cloudinary->adminApi()->asset($publicId);

            return [
                'public_id' => $result['public_id'],
                'url' => $result['secure_url'],
                'width' => $result['width'],
                'height' => $result['height'],
                'format' => $result['format'],
                'size' => $result['bytes'],
                'created_at' => $result['created_at'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get image details', [
                'error' => $e->getMessage(),
                'public_id' => $publicId,
            ]);

            return null;
        }
    }

    /**
     * Extract public ID from Cloudinary URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractPublicId(string $url): ?string
    {
        // Extract public_id from Cloudinary URL
        // Example: https://res.cloudinary.com/demo/image/upload/v1234567890/folder/image.jpg
        // Returns: folder/image
        
        if (preg_match('/\/v\d+\/(.+)\.\w+$/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\/upload\/(.+)\.\w+$/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Validate image file
     *
     * @param UploadedFile $file
     * @param int $maxSize Maximum size in MB
     * @return array
     */
    public function validateImage(UploadedFile $file, int $maxSize = 5): array
    {
        $errors = [];

        // Check file size (convert MB to bytes)
        $maxSizeBytes = $maxSize * 1024 * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            $errors[] = "File size must not exceed {$maxSize}MB";
        }

        // Check mime type
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'File must be an image (JPEG, PNG, GIF, or WebP)';
        }

        // Check if file is actually an image
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            $errors[] = 'File is not a valid image';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Batch validate images
     *
     * @param array $files
     * @param int $maxSize
     * @return array
     */
    public function validateMultipleImages(array $files, int $maxSize = 5): array
    {
        $results = [];

        foreach ($files as $index => $file) {
            $validation = $this->validateImage($file, $maxSize);
            if (!$validation['valid']) {
                $results[] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'errors' => $validation['errors'],
                ];
            }
        }

        return [
            'valid' => empty($results),
            'errors' => $results,
        ];
    }
}
