<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Eindreview-punt 4: validatieregels zonder eigen bericht in een FormRequest
 * vielen terug op Laravels Engelse standaardteksten.
 */
class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_rule_without_its_own_message_is_translated_to_dutch(): void
    {
        // 'name' heeft wel een eigen bericht voor 'required', maar niet voor 'max'.
        $this->post('/registreren', [
            'name' => str_repeat('a', 300),
            'email' => 'iemand@example.test',
        ])->assertSessionHasErrors('name');

        $message = Session::get('errors')->first('name');

        $this->assertStringContainsString('tekens', $message, "Melding was: {$message}");
        $this->assertStringNotContainsString('greater', $message);
    }

    public function test_the_attribute_name_is_translated_too(): void
    {
        $this->post('/inloggen', ['email' => 'geen-adres', 'password' => 'geheim'])
            ->assertSessionHasErrors('email');

        $message = Session::get('errors')->first('email');

        $this->assertStringContainsString('e-mailadres', $message, "Melding was: {$message}");
    }

    public function test_the_checklist_label_has_a_dutch_name(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs($traveler)
            ->post(route('traveler.checklist.store', $trip), ['label' => ''])
            ->assertSessionHasErrors('label');

        $message = Session::get('errors')->first('label');

        $this->assertStringContainsString('checklist-item', $message, "Melding was: {$message}");
    }
}
