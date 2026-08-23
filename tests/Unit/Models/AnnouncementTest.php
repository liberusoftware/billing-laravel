<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_includes_published_in_window(): void
    {
        $announcement = Announcement::factory()->published()->create([
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addDay(),
        ]);

        $this->assertTrue(Announcement::active()->get()->contains($announcement));
    }

    public function test_active_scope_includes_published_without_window(): void
    {
        $announcement = Announcement::factory()->published()->create([
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $this->assertTrue(Announcement::active()->get()->contains($announcement));
    }

    public function test_active_scope_excludes_unpublished(): void
    {
        $announcement = Announcement::factory()->create(['is_published' => false]);

        $this->assertFalse(Announcement::active()->get()->contains($announcement));
    }

    public function test_active_scope_excludes_not_yet_started(): void
    {
        $announcement = Announcement::factory()->published()->create([
            'starts_at' => Carbon::now()->addDay(),
        ]);

        $this->assertFalse(Announcement::active()->get()->contains($announcement));
    }

    public function test_active_scope_excludes_already_ended(): void
    {
        $announcement = Announcement::factory()->published()->create([
            'ends_at' => Carbon::now()->subDay(),
        ]);

        $this->assertFalse(Announcement::active()->get()->contains($announcement));
    }
}
