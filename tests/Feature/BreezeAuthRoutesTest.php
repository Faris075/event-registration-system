<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreezeAuthRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_login_and_register_routes(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
