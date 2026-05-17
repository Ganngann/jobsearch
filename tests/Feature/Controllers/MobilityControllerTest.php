<?php

namespace Tests\Feature\Controllers;

use App\Models\Permit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_mobility_preferences_and_syncs_permits_successfully()
    {
        $user = User::factory()->create([
            'zip_code' => '1000',
            'radius' => 10,
            'contract_preferences' => [],
        ]);

        $permit1 = Permit::factory()->create(['code' => 'B']);
        $permit2 = Permit::factory()->create(['code' => 'C']);

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'zip_code' => '1200',
            'radius' => 25,
            'contract_preferences' => ['CDI', 'CDD'],
            'permits' => [$permit1->id, $permit2->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Préférences de mobilité et permis mis à jour.',
            ]);

        $user->refresh();
        $this->assertEquals('1200', $user->zip_code);
        $this->assertEquals(25, $user->radius);
        $this->assertEquals(['CDI', 'CDD'], $user->contract_preferences);

        $this->assertCount(2, $user->permits);
        $this->assertTrue($user->permits->contains($permit1->id));
        $this->assertTrue($user->permits->contains($permit2->id));
    }

    public function test_it_updates_with_empty_permits_and_contract_preferences()
    {
        $user = User::factory()->create([
            'zip_code' => '1000',
            'radius' => 10,
            'contract_preferences' => ['CDI'],
        ]);

        $permit = Permit::factory()->create(['code' => 'B']);
        $user->permits()->attach($permit);

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'zip_code' => '1200',
            'radius' => 25,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Préférences de mobilité et permis mis à jour.',
            ]);

        $user->refresh();
        $this->assertEquals('1200', $user->zip_code);
        $this->assertEquals(25, $user->radius);
        $this->assertNull($user->contract_preferences);

        $this->assertCount(0, $user->permits);
    }

    public function test_it_requires_radius()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'zip_code' => '1200',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_it_requires_radius_to_be_an_integer()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'radius' => 'not-an-integer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_it_requires_radius_to_be_at_least_0()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'radius' => -1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_it_requires_radius_to_be_at_most_500()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'radius' => 501,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_it_requires_zip_code_to_be_max_10_chars()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'zip_code' => '12345678901',
            'radius' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['zip_code']);
    }

    public function test_it_requires_permits_to_be_an_array()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'radius' => 10,
            'permits' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permits']);
    }

    public function test_it_requires_permits_to_exist_in_database()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'radius' => 10,
            'permits' => [99999], // Non-existent ID
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permits.0']);
    }

    public function test_it_requires_contract_preferences_to_be_an_array()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.mobility.update'), [
            'radius' => 10,
            'contract_preferences' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contract_preferences']);
    }
}
