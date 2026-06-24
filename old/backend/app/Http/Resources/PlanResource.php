<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'slug' => $this->slug,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'price' => (float) $this->price,
            'formatted_price' => $this->formatted_price,
            'display_price' => $this->display_price,
            'interval' => $this->interval,
            'interval_display' => $this->interval_display,
            'is_addon' => $this->is_addon,
            'role_type' => $this->role_type,
            'type_display' => $this->type_display,
            'features' => $this->features ?? [],
            'limits' => $this->limits ?? [],
            'role_limits_structure' => $this->role_limits_structure,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'trial_days' => $this->trial_days,
            'stripe_price_id' => $this->stripe_price_id,
            'paypal_plan_id' => $this->paypal_plan_id,
            'discount_enabled' => $this->discount_enabled ?? false,
            'discount_percentage' => $this->discount_percentage ?? 0,
            'discount_valid_until' => $this->discount_valid_until,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
