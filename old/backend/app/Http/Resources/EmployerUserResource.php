<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * EmployerUserResource
 * 
 * API Resource for EmployerUser (team member) model.
 */
class EmployerUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'effective_permissions' => $this->getEffectivePermissions(),
            'invited_by' => $this->invited_by,
            'invited_at' => $this->invited_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'is_active' => $this->is_active,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
