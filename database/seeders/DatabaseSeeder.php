<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityChoice;
use App\Models\ChecklistItem;
use App\Models\ProgramItem;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

// Alleen fictieve testdata — geen echte persoonsgegevens, paspoorten of betaalgegevens.
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->coordinator()->create([
            'name' => 'Coördinator Test',
            'email' => 'coordinator@tripcrew.test',
        ]);

        $trip = Trip::create([
            'name' => 'Barcelona Zomertrip',
            'start_date' => now()->toDateString(),          // start vandaag, zodat FE-03 direct iets toont
            'end_date' => now()->addDays(4)->toDateString(),
            'practical_info' => "Verzamelen om 07:00 bij de hoofdingang van school.\nVerblijf: Hostel Gràcia, Carrer de Verdi 12.\nNoodnummer begeleiding: 06-12345678 (fictief).\nNeem je ID-kaart en EHIC-pas mee.",
        ]);

        $days = collect(Carbon::parse($trip->start_date)->toPeriod($trip->end_date))
            ->map(fn ($date) => TripDay::create(['trip_id' => $trip->id, 'date' => $date->toDateString()]));

        // Programma voor alle dagen behalve de laatste (zodat de lege melding van FE-03 ook te zien is).
        foreach ($days->slice(0, -1) as $day) {
            ProgramItem::create(['trip_day_id' => $day->id, 'time' => '08:30', 'title' => 'Ontbijt', 'location' => 'Hostel']);
            ProgramItem::create(['trip_day_id' => $day->id, 'time' => '10:00', 'title' => 'Stadswandeling', 'location' => 'Barri Gòtic']);
            ProgramItem::create(['trip_day_id' => $day->id, 'time' => '19:00', 'title' => 'Diner met de groep', 'location' => 'Centrum']);
        }

        $first = $days->first();
        $kajak = Activity::create(['trip_day_id' => $first->id, 'name' => 'Kajakken', 'capacity' => 8, 'deadline' => now()->addDays(2)]);
        $kook = Activity::create(['trip_day_id' => $first->id, 'name' => 'Kookworkshop tapas', 'capacity' => 2, 'deadline' => now()->addDays(2)]);
        Activity::create(['trip_day_id' => $first->id, 'name' => 'Fietstour (deadline voorbij)', 'capacity' => 10, 'deadline' => now()->subDay()]);

        // Vaste demo-reiziger
        $demo = User::factory()->create(['name' => 'Reiziger Demo', 'email' => 'reiziger@tripcrew.test']);

        // Nog te activeren reiziger (FE-01): activatielink verschijnt in de console
        $invited = User::factory()->notActivated()->create(['name' => 'Nog Niet Actief', 'email' => 'nieuw@tripcrew.test']);

        $others = User::factory()->count(6)->create();

        $travelers = $others->push($demo)->push($invited);
        $trip->registrations()->attach($travelers->pluck('id')->all(), ['status' => \App\Enums\RegistrationStatus::Approved->value, 'requested_at' => now(), 'decided_at' => now()]);

        foreach ($travelers as $traveler) {
            foreach (['Paspoort gecontroleerd', 'Reisverzekering geregeld', 'Tas ingepakt'] as $label) {
                ChecklistItem::create([
                    'user_id' => $traveler->id,
                    'trip_id' => $trip->id,
                    'label' => $label,
                    'checked' => fake()->boolean(),
                ]);
            }
        }

        // Kookworkshop is vol (2/2) zodat FE-04's foutpad te testen is.
        foreach ($others->take(2) as $u) {
            ActivityChoice::create(['user_id' => $u->id, 'activity_id' => $kook->id]);
        }
        ActivityChoice::create(['user_id' => $others[2]->id, 'activity_id' => $kajak->id]);

        $this->command?->info('Coördinator: coordinator@tripcrew.test / password');
        $this->command?->info('Reiziger:    reiziger@tripcrew.test / password');
        $this->command?->info('Activatielink (FE-01): '.url('/activeren/'.$invited->activation_token));
    }
}
