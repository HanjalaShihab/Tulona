<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin-only authentication & granular roles (§6, §57). */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_login_and_view_dashboard(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->post('/admin/login', ['email' => 'admin@tulona.test', 'password' => 'password']);
        $response->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::where('email', 'admin@tulona.test')->first())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Products');
    }

    public function test_analyst_cannot_manage_products(): void
    {
        $this->seed(CatalogSeeder::class);
        $analyst = User::where('email', 'analyst@tulona.test')->first();

        $this->actingAs($analyst)->get('/admin/products/create')->assertForbidden();
    }

    public function test_wrong_password_rejected(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'admin@tulona.test', 'password' => 'wrong-password'])
            ->assertRedirect('/admin/login');

        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
