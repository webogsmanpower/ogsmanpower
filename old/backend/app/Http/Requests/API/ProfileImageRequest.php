<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * ProfileImageRequest
 * 
 * Validates profile image upload requests.
 * Ensures file type, size, and dimensions are appropriate.
 * 
 * @package App\Http\Requests\API
 */
class ProfileImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profile_image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120', // 5MB max
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profile_image.required' => 'Please select an image to upload.',
            'profile_image.file' => 'The uploaded file must be a valid file.',
            'profile_image.image' => 'The uploaded file must be an image.',
            'profile_image.mimes' => 'The image must be a JPEG, PNG, GIF, or WebP file.',
            'profile_image.max' => 'The image size must not exceed 5MB.',
            'profile_image.dimensions' => 'The image dimensions must be between 100x100 and 2000x2000 pixels.',
        ];
    }

    /**
     * Get custom attributes for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'profile_image' => 'profile image',
        ];
    }

    /**
     * Additional validation after basic rules pass
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $file = $this->file('profile_image');
            
            if ($file instanceof UploadedFile) {
                // Check for common image manipulation signatures
                $this->validateImageContent($file, $validator);
            }
        });
    }

    /**
     * Validate image content for potential manipulation
     *
     * @param UploadedFile $file
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function validateImageContent(UploadedFile $file, $validator): void
    {
        try {
            $imageInfo = getimagesize($file->getPathname());
            
            if ($imageInfo === false) {
                $validator->errors()->add('profile_image', 'The uploaded file is not a valid image.');
                return;
            }

            // Check for valid image types
            $validTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
            if (!in_array($imageInfo[2], $validTypes)) {
                $validator->errors()->add('profile_image', 'The image type is not supported.');
            }

        } catch (\Exception $e) {
            $validator->errors()->add('profile_image', 'Unable to validate the image content.');
        }
    }
}
