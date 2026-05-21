<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EasyMarketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_login(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Boutique Test',
            'phone' => '0196228860',
        ]);

        $this->get('/dashboard/'.$business->id)->assertRedirect('/connexion');
        $this->getJson('/api/businesses/'.$business->id.'/dashboard')->assertStatus(401);
    }

    public function test_owner_can_open_dashboard_api(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Boutique Test',
            'phone' => '0196228860',
        ]);
        Subscription::create([
            'business_id' => $business->id,
            'plan' => 'monthly',
            'amount' => 5000,
            'status' => 'actif',
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/businesses/'.$business->id.'/dashboard')
            ->assertOk()
            ->assertJsonPath('business.name', 'Boutique Test');
    }
}
