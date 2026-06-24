<?php

if (!function_exists('get_storage_url')) {
    /**
     * Get the full URL for a storage path.
     * Handles null values and ensures proper URL format.
     * Only returns URL if the file actually exists.
     *
     * @param string|null $path
     * @return string|null
     */
    function get_storage_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        // If it's already a full URL, check if it points to an existing file
        if (str_starts_with($path, 'http')) {
            // Extract the path part from the URL
            $urlParts = parse_url($path);
            if (isset($urlParts['path'])) {
                $pathPart = ltrim($urlParts['path'], '/');
                // If it's a storage URL, extract the file path
                if (str_starts_with($pathPart, 'storage/')) {
                    $filePath = substr($pathPart, 8); // Remove 'storage/'
                    $fullPath = storage_path('app/public/' . $filePath);
                    if (!file_exists($fullPath)) {
                        return null;
                    }
                }
            }
            return $path; // Return the full URL as-is if we can't validate it
        }
        
        // Remove leading 'storage/' if present to avoid double paths
        $cleanPath = ltrim($path, 'storage/');
        
        // Check if file actually exists before returning URL
        $fullPath = storage_path('app/public/' . $cleanPath);
        if (!file_exists($fullPath)) {
            return null;
        }
        
        return url('storage/' . $cleanPath);
    }
}
