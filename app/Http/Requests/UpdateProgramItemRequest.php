<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Programmaonderdeel bewerken; mag naar een andere dag, maar alleen binnen dezelfde reis.
class UpdateProgramItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoordinator();
    }

    public function rules(): array
    {
        return [
            'trip_day_id' => ['required', Rule::exists('trip_days', 'id')->where('trip_id', $this->route('trip')->id)],
            ...StoreProgramItemRequest::fieldRules(),
        ];
    }
}
