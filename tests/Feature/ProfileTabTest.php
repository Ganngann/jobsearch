<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_edit_redirects_to_security_tab_on_password_error(): void
    {
        $user = User::factory()->create(['password' => 'secret']);

        $response = $this
            ->actingAs($user)
            ->from('/profile/edit')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertRedirect('/profile/edit');
        $response->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $response = $this->actingAs($user)->withSession(['errors' => session('errors')])->get('/profile/edit');
        $response->assertSee('x-data="{ tab: \'security\' }"', false);
    }
}
