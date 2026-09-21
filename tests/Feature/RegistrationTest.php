<?php

namespace Tests\Feature;

use App\Mail\ActivationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_register_and_receives_an_activation_mail(): void
    {
        Mail::fake();

        $this->post('/registreren', [
            'name' => 'Nieuwe Reiziger',
            'email' => 'nieuw@example.test',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $user = User::where('email', 'nieuw@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('reiziger', $user->role);
        $this->assertNull($user->activated_at);
        $this->assertNotNull($user->activation_token);

        Mail::assertSent(ActivationMail::class, fn ($mail) => $mail->hasTo('nieuw@example.test'));
    }

    public function test_registering_with_an_existing_email_creates_no_second_account_and_sends_no_mail(): void
    {
        Mail::fake();
        $existing = User::factory()->create(['email' => 'bestaat@example.test']);

        $this->post('/registreren', [
            'name' => 'Iemand Anders',
            'email' => 'bestaat@example.test',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertSame(1, User::where('email', 'bestaat@example.test')->count());
        $this->assertSame($existing->name, $existing->fresh()->name);
        Mail::assertNothingSent();
    }

    public function test_registration_requires_a_name_and_a_valid_email(): void
    {
        $this->post('/registreren', ['name' => '', 'email' => 'geenmail'])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertSame(0, User::count());
    }

    public function test_registered_user_can_set_a_password_and_log_in(): void
    {
        Mail::fake();
        $this->post('/registreren', ['name' => 'Nieuwe Reiziger', 'email' => 'nieuw@example.test']);
        $user = User::where('email', 'nieuw@example.test')->first();

        $this->post("/activeren/{$user->activation_token}", [
            'password' => 'geheim1234',
            'password_confirmation' => 'geheim1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_logged_in_user_cannot_open_the_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/registreren')
            ->assertRedirect(route('dashboard'));
    }
}
