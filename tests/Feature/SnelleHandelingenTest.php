<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\ActivityChoice;
use App\Models\ChecklistItem;
use App\Models\ProgramItem;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * resources/js/snel.js verstuurt een formulier met data-snel="<id>" op de achtergrond
 * en vervangt daarna alleen het blok #<id> uit de pagina waar de server naartoe stuurt.
 * Dat werkt alleen als (1) het formulier binnen dat blok staat en (2) de pagina na de
 * actie dat blok ook heeft — ook als de lijst dan leeg is. Precies die naad kan stil
 * breken als iemand een id hernoemt of een lege toestand buiten het blok zet.
 */
class SnelleHandelingenTest extends TestCase
{
    use RefreshDatabase;

    private function approvedTraveler(Trip $trip): User
    {
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        return $traveler;
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    /** Er staat een data-snel-formulier met deze action binnen het blok met hetzelfde id. */
    private function assertSnelFormulier(string $html, string $id, string $action): void
    {
        $gevonden = $this->xpath($html)->query(
            "//*[@id='{$id}']//form[@data-snel='{$id}' and @action='{$action}']"
        );

        $this->assertSame(1, $gevonden->length, "Geen formulier data-snel=\"{$id}\" met action {$action} binnen #{$id}.");
    }

    private function assertBlok(string $html, string $id): void
    {
        $this->assertSame(1, $this->xpath($html)->query("//*[@id='{$id}']")->length, "Blok #{$id} ontbreekt.");
    }

    public function test_inschrijven(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $pagina = route('traveler.registrations.index');

        $this->assertSnelFormulier(
            $this->actingAs($traveler)->get($pagina)->getContent(),
            'reizen',
            route('traveler.registrations.store', $trip)
        );

        $na = $this->from($pagina)->followingRedirects()->post(route('traveler.registrations.store', $trip));

        $na->assertOk();
        $this->assertBlok($na->getContent(), 'reizen');
    }

    public function test_activiteit_kiezen(): void
    {
        $activity = Activity::factory()->create();
        $trip = $activity->tripDay->trip;
        $traveler = $this->approvedTraveler($trip);
        $pagina = route('traveler.activities', [$trip, $activity->trip_day_id]);

        $this->assertSnelFormulier(
            $this->actingAs($traveler)->get($pagina)->getContent(),
            'activiteiten',
            route('activities.choose', $activity)
        );

        $na = $this->from($pagina)->followingRedirects()->post(route('activities.choose', $activity));

        $na->assertOk()->assertSee('Je hebt deze activiteit gekozen');
        $this->assertBlok($na->getContent(), 'activiteiten');
    }

    public function test_laatste_keuze_annuleren_laat_het_blok_staan(): void
    {
        $activity = Activity::factory()->create();
        $trip = $activity->tripDay->trip;
        $traveler = $this->approvedTraveler($trip);
        $choice = ActivityChoice::create(['user_id' => $traveler->id, 'activity_id' => $activity->id]);
        $pagina = route('traveler.my-choices', $trip);

        $this->assertSnelFormulier(
            $this->actingAs($traveler)->get($pagina)->getContent(),
            'keuzes',
            route('choices.destroy', $choice)
        );

        $na = $this->from($pagina)->followingRedirects()->delete(route('choices.destroy', $choice));

        $na->assertOk()->assertSee('Je hebt nog geen activiteiten gekozen.');
        $this->assertBlok($na->getContent(), 'keuzes');
    }

    public function test_checklist_afvinken_en_toevoegen(): void
    {
        $trip = Trip::factory()->create();
        $traveler = $this->approvedTraveler($trip);
        $item = ChecklistItem::factory()->create(['user_id' => $traveler->id, 'trip_id' => $trip->id]);
        $pagina = route('traveler.my-choices', $trip);
        $html = $this->actingAs($traveler)->get($pagina)->getContent();

        $this->assertSnelFormulier($html, 'checklist', route('checklist.toggle', $item));
        $this->assertSnelFormulier($html, 'checklist', route('traveler.checklist.store', $trip));

        $na = $this->from($pagina)->followingRedirects()->patch(route('checklist.toggle', $item));
        $na->assertOk()->assertSee('aria-pressed="true"', false);
        $this->assertBlok($na->getContent(), 'checklist');

        $na = $this->from($pagina)->followingRedirects()->post(route('traveler.checklist.store', $trip), ['label' => 'Zonnebrand']);
        $na->assertOk()->assertSee('Zonnebrand');
        $this->assertBlok($na->getContent(), 'checklist');
    }

    public function test_validatiefout_bij_toevoegen_staat_binnen_het_checklistblok(): void
    {
        $trip = Trip::factory()->create();
        $traveler = $this->approvedTraveler($trip);
        $pagina = route('traveler.my-choices', $trip);

        $na = $this->actingAs($traveler)->from($pagina)->followingRedirects()
            ->post(route('traveler.checklist.store', $trip), ['label' => '']);

        $fout = $this->xpath($na->getContent())->query("//*[@id='checklist']//*[contains(@class, 'text-danger')]");
        $this->assertGreaterThan(0, $fout->length, 'De validatiefout staat niet binnen #checklist.');
    }

    public function test_laatste_aanvraag_goedkeuren_of_afwijzen_laat_het_blok_staan(): void
    {
        $coordinator = User::factory()->coordinator()->create();
        $trip = Trip::factory()->create();
        [$sam, $lisa] = User::factory()->count(2)->create();
        $trip->registrations()->attach($sam, ['status' => RegistrationStatus::Pending->value]);
        $trip->registrations()->attach($lisa, ['status' => RegistrationStatus::Pending->value]);
        $pagina = route('coordinator.registrations.index');
        $html = $this->actingAs($coordinator)->get($pagina)->getContent();

        $this->assertSnelFormulier($html, 'aanvragen', route('coordinator.registrations.approve', [$trip, $sam]));
        $this->assertSnelFormulier($html, 'aanvragen', route('coordinator.registrations.reject', [$trip, $lisa]));

        $this->from($pagina)->patch(route('coordinator.registrations.approve', [$trip, $sam]));
        $na = $this->from($pagina)->followingRedirects()->patch(route('coordinator.registrations.reject', [$trip, $lisa]));

        $na->assertOk()->assertSee('Er staan geen aanvragen open.');
        $this->assertBlok($na->getContent(), 'aanvragen');
    }

    public function test_meldingen_blok_staat_op_elke_pagina_na_een_snelle_handeling(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();

        $na = $this->actingAs($traveler)->from(route('traveler.registrations.index'))
            ->followingRedirects()->post(route('traveler.registrations.store', $trip));

        $melding = $this->xpath($na->getContent())->query("//*[@id='meldingen']//*[@role='status']");
        $this->assertSame(1, $melding->length, 'De succesmelding staat niet in #meldingen.');
    }

    public function test_programma_toevoegen_en_verwijderen(): void
    {
        $coordinator = User::factory()->coordinator()->create();
        $trip = Trip::factory()->create();
        $day = TripDay::factory()->create(['trip_id' => $trip->id]);
        $item = ProgramItem::factory()->create(['trip_day_id' => $day->id]);
        $pagina = route('coordinator.trips.program.index', $trip);
        $html = $this->actingAs($coordinator)->get($pagina)->getContent();

        $this->assertSnelFormulier($html, 'programma', route('coordinator.trips.program.store', [$trip, $day]));
        $this->assertSnelFormulier($html, 'programma', route('coordinator.trips.program.destroy', [$trip, $item]));

        $na = $this->from($pagina)->followingRedirects()
            ->post(route('coordinator.trips.program.store', [$trip, $day]), ['time' => '12:00', 'title' => 'Lunch op het strand']);
        $na->assertOk()->assertSee('Lunch op het strand');
        $this->assertBlok($na->getContent(), 'programma');

        $na = $this->from($pagina)->followingRedirects()->delete(route('coordinator.trips.program.destroy', [$trip, $item]));
        $na->assertOk();
        $this->assertBlok($na->getContent(), 'programma');
    }

    public function test_validatiefout_bij_programma_staat_alleen_onder_de_verstuurde_dag(): void
    {
        $coordinator = User::factory()->coordinator()->create();
        $trip = Trip::factory()->create();
        $eerste = TripDay::factory()->create(['trip_id' => $trip->id, 'date' => '2030-06-01']);
        $tweede = TripDay::factory()->create(['trip_id' => $trip->id, 'date' => '2030-06-02']);
        $pagina = route('coordinator.trips.program.index', $trip);

        $na = $this->actingAs($coordinator)->from($pagina)->followingRedirects()
            ->post(route('coordinator.trips.program.store', [$trip, $tweede]), ['dag' => $tweede->id, 'time' => '', 'title' => 'Zonder tijd']);

        $xpath = $this->xpath($na->getContent());
        $formulier = fn (TripDay $day) => "//*[@id='programma']//form[@action='".route('coordinator.trips.program.store', [$trip, $day])."']";

        $this->assertSame(0, $xpath->query($formulier($eerste)."//*[contains(@class, 'text-danger')]")->length, 'Fout staat ook bij de eerste dag.');
        $this->assertGreaterThan(0, $xpath->query($formulier($tweede)."//*[contains(@class, 'text-danger')]")->length, 'Fout ontbreekt bij de tweede dag.');
        $this->assertSame('Zonder tijd', $xpath->query($formulier($tweede)."//input[@name='title']")->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query($formulier($eerste)."//input[@name='title']")->item(0)->getAttribute('value'));
    }
}
