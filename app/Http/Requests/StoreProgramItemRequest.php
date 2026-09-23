<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Programmaonderdeel toevoegen aan één dag (de dag komt uit de route).
class StoreProgramItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoordinator();
    }

    /**
     * Op de programmapagina staat onder elke dag een eigen toevoegformulier. Met een
     * foutenzak per dag verschijnen de meldingen alleen bij het formulier dat verstuurd is.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = 'dag'.$this->route('tripDay')->id;
    }

    public function rules(): array
    {
        return self::fieldRules();
    }

    /** Gedeeld met UpdateProgramItemRequest. */
    public static function fieldRules(): array
    {
        return [
            'time' => ['required', 'date_format:H:i'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
