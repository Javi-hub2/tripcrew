<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Vast checklistpunt dat de coördinator aan een reis toevoegt.
class StoreTripChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoordinator();
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
        ];
    }
}
