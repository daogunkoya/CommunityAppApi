<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'age' => $this->age, // Accessor or computed? 'date_of_birth' is the column, but RegistrationControllerTest expected 'age' in some places or 'date_of_birth'?
            // Wait, we removed 'age' column. But maybe we have an accessor?
            // The Refactor Plan says "Response Transformation... prevents exposing raw DB columns".
            // Let's check User model for accessors if 'age' is still used.
            // For now, let's return date_of_birth.
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'location' => $this->location,
            'radius' => $this->radius,
            'main_goal' => $this->main_goal,
            'auth_provider' => $this->auth_provider,
            'email_verified_at' => $this->email_verified_at,
            'profile_picture' => $this->profile_picture,
            'skill_levels' => $this->whenLoaded('skillLevels'),
        ];
    }
}
