<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_belongs_to_project_and_author(): void
    {
        $note = ProjectNote::factory()->create();

        $this->assertInstanceOf(Project::class, $note->project);
        $this->assertInstanceOf(User::class, $note->author);
        $this->assertSame($note->project_id, $note->project->id);
        $this->assertSame($note->user_id, $note->author->id);
    }

    public function test_staff_notes_not_visible_on_client_panel(): void
    {
        // Staff-only: no Client panel resource exposes ProjectNote to customers.
        $this->assertFalse(class_exists(\App\Filament\Client\Resources\ProjectNoteResource::class));

        // The note is reachable from its project (staff side relation works).
        $note = ProjectNote::factory()->create();

        $this->assertTrue($note->project->notes->contains($note));
    }
}
