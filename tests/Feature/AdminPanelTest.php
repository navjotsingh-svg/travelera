<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\TravelPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_view_dashboard_and_sections(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/bookings')->assertOk();
        $this->actingAs($admin)->get('/admin/searches')->assertOk();
        $this->actingAs($admin)->get('/admin/abandoned-payments')->assertOk();
        $this->actingAs($admin)->get('/admin/packages')->assertOk();
        $this->actingAs($admin)->get('/admin/blogs')->assertOk();
    }

    public function test_admin_can_create_blog_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/blogs', [
            'title' => 'Best time to visit Goa',
            'excerpt' => 'A short guide',
            'body' => 'Goa is lovely in winter.',
            'is_published' => '1',
        ])->assertRedirect(route('admin.blogs.index'));

        $this->assertDatabaseHas('blogs', [
            'title' => 'Best time to visit Goa',
            'is_published' => true,
        ]);

        $blog = Blog::query()->first();
        $this->get('/blog/'.$blog->slug)->assertOk()->assertSee('Best time to visit Goa');
    }

    public function test_admin_can_create_package(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->seed(\Database\Seeders\TravelSeeder::class);

        $destinationId = \App\Models\Destination::query()->first()->id;

        $this->actingAs($admin)->post('/admin/packages', [
            'destination_id' => $destinationId,
            'title' => 'Admin Test Package',
            'duration_days' => 4,
            'price' => 19999,
            'description' => 'Created from admin panel.',
            'includes_text' => "Hotel\nTransfers",
            'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1400&q=80',
            'is_featured' => '1',
        ])->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('travel_packages', [
            'title' => 'Admin Test Package',
            'is_featured' => true,
        ]);

        $this->assertTrue(TravelPackage::query()->where('title', 'Admin Test Package')->exists());
    }
}
