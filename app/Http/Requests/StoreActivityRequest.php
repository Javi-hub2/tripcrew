<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// FE-07
class StoreActivityRequest extends FormRequest
{
    private ?int $existingChoicesCount = null;

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
            // Bij bewerken mag de capaciteit niet onder het aantal al gemaakte keuzes.
            'capacity' => ['required', 'integer', 'min:'.max(1, $this->existingChoicesCount())],
            'deadline' => ['required', 'date'],
        ];
    }

    /** Aantal reeds gemaakte keuzes; 0 bij het aanmaken van een nieuwe activiteit. */
    private function existingChoicesCount(): int
    {
        // rules() en messages() vragen dit beide; één query volstaat.
        if ($this->existingChoicesCount === null) {
            $activity = $this->route('activity');
            $this->existingChoicesCount = $activity instanceof Activity ? $activity->choices()->count() : 0;
        }

        return $this->existingChoicesCount;
    }

    public function messages(): array
    {
        return [
            'capacity.integer' => 'Capaciteit moet een geheel getal zijn.',
            'capacity.min' => $this->existingChoicesCount() > 0
                ? 'Capaciteit kan niet lager dan '.$this->existingChoicesCount().': zoveel deelnemers hebben deze activiteit al gekozen.'
                : 'Capaciteit moet groter dan 0 zijn.',
            'trip_day_id.exists' => 'Kies een dag die bij deze reis hoort.',
        ];
    }
}
