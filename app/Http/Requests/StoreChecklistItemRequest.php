<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTraveler();
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
        ];
    }
}
