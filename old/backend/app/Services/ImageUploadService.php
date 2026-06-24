<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * ImageUploadService
 * 
 * Handles all file upload operations for resumes.
 * Centralizes validation, storage, and URL generation.
 * 
 * @package App\Services
 */
class ImageUploadService
{
    /**
     * Maximum file size in kilobytes
     */
    private const MAX_FILE_SIZE_KB = 2048;

    /**
     * Allowed MIME types for images
     */
    private const ALLOWED_IMAGE_MIMES = ['jpeg', 'jpg', 'png'];

    /**
     * Allowed MIME types for documents
     */
    private const ALLOWED_DOC_MIMES = ['jpeg', 'jpg', 'png', 'pdf'];

    /**
     * Default images for different contexts
     */
    private const DEFAULTS = [
        'avatar' => 'images/mock/seeker-avatar.svg',
        'company_logo' => 'images/mock/company-logo.svg',
        'job_image' => 'images/default-job.svg',
    ];

    /**
     * Upload a profile image.
     *
     * @param UploadedFile $file
     * @param \App\Models\User $user
     * @return string Storage path
     * @throws ValidationException
     */
    public function uploadProfileImage(UploadedFile $file, $user): string
    {
        $this->validateProfileImage($file);

        // Create directory structure for profile images
        $path = $file->store("profile_images/{$user->id}", 'public');
        
        return $path;
    }

    /**
     * Upload a profile photo.
     *
     * @param UploadedFile $file
     * @param int $userId
     * @return array ['path' => string, 'url' => string]
     * @throws ValidationException
     */
    public function uploadProfilePhoto(UploadedFile $file, int $userId): array
    {
        $this->validateImage($file);

        $path = $file->store("resume_uploads/photos/{$userId}", 'public');
        
        return [
            'path' => $path,
            'url' => $this->getPublicUrl($path),
        ];
    }

    /**
     * Upload a full body photo.
     *
     * @param UploadedFile $file
     * @param int $userId
     * @return array ['path' => string, 'url' => string]
     * @throws ValidationException
     */
    public function uploadFullBodyPhoto(UploadedFile $file, int $userId): array
    {
        $this->validateImage($file, 400, 600);

        $path = $file->store("full_body_photos/{$userId}", 'public');
        
        return [
            'path' => $path,
            'url' => $this->getPublicUrl($path),
        ];
    }

    /**
     * Upload a document (certificate, offer letter, etc.).
     *
     * @param UploadedFile $file
     * @param int $userId
     * @param string|null $fieldName
     * @return array ['path' => string, 'url' => string, 'field_name' => string|null]
     * @throws ValidationException
     */
    public function uploadDocument(UploadedFile $file, int $userId, ?string $fieldName = null): array
    {
        $this->validateDocument($file);

        $path = $file->store("resume_uploads/docs/{$userId}", 'public');
        
        return [
            'path' => $path,
            'url' => $this->getPublicUrl($path),
            'field_name' => $fieldName,
        ];
    }

    /**
     * Get the public URL for a stored file.
     * 
     * THE SINGLE SOURCE OF TRUTH for URL resolution.
     * Handles: local storage, S3, external URLs, and defaults.
     *
     * @param string|null $path The storage path or full URL
     * @param string $defaultType Type of default image ('avatar', 'company_logo', 'job_image')
     * @return string Always returns a valid URL
     */
    public function getPublicUrl(?string $path, string $defaultType = 'avatar'): string
    {
        // No path - return default
        if (empty($path)) {
            return $this->getDefaultUrl($defaultType);
        }

        // Already a full URL (S3, external, etc.)
        if ($this->isFullUrl($path)) {
            return $path;
        }

        // Build URL from storage path
        return $this->buildStorageUrl($path);
    }

    /**
     * Get URL for an avatar image with proper fallback.
     *
     * @param string|null $path
     * @return string
     */
    public function getAvatarUrl(?string $path): string
    {
        return $this->getPublicUrl($path, 'avatar');
    }

    /**
     * Get URL for a company logo with proper fallback.
     *
     * @param string|null $path
     * @return string
     */
    public function getCompanyLogoUrl(?string $path): string
    {
        return $this->getPublicUrl($path, 'company_logo');
    }

    /**
     * Get the raw storage path (for frontend URL building).
     * 
     * Use this when frontend needs to build its own URL (e.g., through proxy).
     *
     * @param string|null $path
     * @return string|null
     */
    public function getRawPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If it's already a full URL, return as-is
        if ($this->isFullUrl($path)) {
            return $path;
        }

        // Return raw path - frontend will build the full URL
        return $path;
    }

    /**
     * Get absolute file system path for a storage path.
     * 
     * Used for PDF generation where we need actual file access.
     *
     * @param string|null $path
     * @return string|null
     */
    public function getAbsoluteFilePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If it's a full URL, we can't get local path
        if ($this->isFullUrl($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }

    /**
     * Check if a path is a full URL.
     *
     * @param string $path
     * @return bool
     */
    public function isFullUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    /**
     * Build a storage URL from a relative path.
     *
     * @param string $path
     * @return string
     */
    private function buildStorageUrl(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        return url('storage/' . $cleanPath);
    }

    /**
     * Get default URL for a given type.
     *
     * @param string $type
     * @return string
     */
    private function getDefaultUrl(string $type): string
    {
        $path = self::DEFAULTS[$type] ?? self::DEFAULTS['avatar'];
        return url($path);
    }

    /**
     * Delete a file from storage.
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        if (empty($path) || str_starts_with($path, 'http')) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /**
     * Validate a profile image file.
     *
     * @param UploadedFile $file
     * @throws ValidationException
     */
    private function validateProfileImage(UploadedFile $file): void
    {
        // Check file size (5MB max for profile images)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'profile_image' => 'Profile image size must not exceed 5MB.',
            ]);
        }

        // Check MIME type - allow more formats for profile images
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedMimes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'profile_image' => 'Profile image must be a JPEG, PNG, GIF, or WebP file.',
            ]);
        }

        // Check dimensions - profile images should be reasonable
        $dimensions = getimagesize($file->getPathname());
        if ($dimensions === false) {
            throw ValidationException::withMessages([
                'profile_image' => 'Unable to read image dimensions.',
            ]);
        }

        [$width, $height] = $dimensions;

        if ($width < 100 || $height < 100) {
            throw ValidationException::withMessages([
                'profile_image' => 'Profile image must be at least 100x100 pixels.',
            ]);
        }

        if ($width > 2000 || $height > 2000) {
            throw ValidationException::withMessages([
                'profile_image' => 'Profile image must not exceed 2000x2000 pixels.',
            ]);
        }
    }

    /**
     * Validate an image file.
     *
     * @param UploadedFile $file
     * @param int|null $minWidth
     * @param int|null $minHeight
     * @throws ValidationException
     */
    private function validateImage(UploadedFile $file, ?int $minWidth = null, ?int $minHeight = null): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'file' => 'File size must not exceed ' . (self::MAX_FILE_SIZE_KB / 1024) . 'MB.',
            ]);
        }

        // Check MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_IMAGE_MIMES, true)) {
            throw ValidationException::withMessages([
                'file' => 'File must be a JPEG or PNG image.',
            ]);
        }

        // Check dimensions if specified
        if ($minWidth || $minHeight) {
            $dimensions = getimagesize($file->getPathname());
            if ($dimensions === false) {
                throw ValidationException::withMessages([
                    'file' => 'Unable to read image dimensions.',
                ]);
            }

            [$width, $height] = $dimensions;

            if ($minWidth && $width < $minWidth) {
                throw ValidationException::withMessages([
                    'file' => "Image width must be at least {$minWidth}px.",
                ]);
            }

            if ($minHeight && $height < $minHeight) {
                throw ValidationException::withMessages([
                    'file' => "Image height must be at least {$minHeight}px.",
                ]);
            }
        }
    }

    /**
     * Validate a document file.
     *
     * @param UploadedFile $file
     * @throws ValidationException
     */
    private function validateDocument(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'file' => 'File size must not exceed ' . (self::MAX_FILE_SIZE_KB / 1024) . 'MB.',
            ]);
        }

        // Check MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_DOC_MIMES, true)) {
            throw ValidationException::withMessages([
                'file' => 'File must be a JPEG, PNG, or PDF.',
            ]);
        }
    }
}
