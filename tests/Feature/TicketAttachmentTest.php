<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_stored_with_private_visibility(): void
    {
        Storage::fake('local');
        $ticket = Ticket::factory()->create();
        $uploader = User::factory()->create();

        $path = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')
            ->store('ticket-attachments', 'local');

        $attachment = TicketAttachment::create([
            'attachable_type' => Ticket::class,
            'attachable_id' => $ticket->id,
            'uploaded_by' => $uploader->id,
            'path' => $path,
            'original_name' => 'doc.pdf',
            'mime' => 'application/pdf',
            'size' => 10240,
        ]);

        Storage::disk('local')->assertExists($path);
        $this->assertTrue($attachment->attachable->is($ticket));
    }

    public function test_unauthorized_user_cannot_download_attachment(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);
        $attachment = TicketAttachment::factory()->create([
            'attachable_type' => Ticket::class,
            'attachable_id' => $ticket->id,
        ]);

        $other = User::factory()->create();

        $this->actingAs($other)
            ->get(route('tickets.attachments.download', $attachment))
            ->assertForbidden();
    }
}
