<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $event = $this->route('event');
        return $event && $event->organiser_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'game_type_id' => ['sometimes', 'exists:game_types,id'],
            'location' => ['sometimes', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date', 'after:now'],
            'skill_level' => ['sometimes', 'integer', 'min:1', 'max:3'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:50'],
            'waiting_list_enabled' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'venue_booked' => ['sometimes', 'boolean'],
        ];
    }
}
