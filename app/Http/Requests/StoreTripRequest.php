<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// FE-06 (aanmaken/aanpassen)
class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoordinator();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'De einddatum mag niet vóór de begindatum liggen.',
        ];
    }
}
