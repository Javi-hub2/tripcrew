<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// FE-07
class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoordinator();
    }

    public function rules(): array
    {
        return [
            'trip_day_id' => [
                'required',
                // De dag moet bij de reis uit de URL horen.
                Rule::exists('trip_days', 'id')->where('trip_id', $this->route('trip')->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'capacity.integer' => 'Capaciteit moet een geheel getal zijn.',
            'capacity.min' => 'Capaciteit moet groter dan 0 zijn.',
            'trip_day_id.exists' => 'Kies een dag die bij deze reis hoort.',
        ];
    }
}
