<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Client\Pages\Announcements as ClientAnnouncements;
use App\Models\Announcement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_announcement(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Scheduled maintenance',
                'body' => 'We will be down tonight.',
                'type' => 'network_status',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('announcements', [
            'title' => 'Scheduled maintenance',
            'type' => 'network_status',
            'is_published' => true,
        ]);
    }

    public function test_client_page_shows_active_and_hides_inactive(): void
    {
        $active = Announcement::factory()->published()->create([
            'title' => 'Active notice',
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addDay(),
        ]);
        $inactive = Announcement::factory()->create([
            'title' => 'Draft notice',
            'is_published' => false,
        ]);

        $this->actingAs(User::factory()->withPersonalTeam()->create());

        Livewire::test(ClientAnnouncements::class)
            ->assertSuccessful()
            ->assertSee($active->title)
            ->assertDontSee($inactive->title);
    }
}
