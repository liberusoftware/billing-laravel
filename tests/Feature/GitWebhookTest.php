<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SyncGitConnection;
use App\Models\GitConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GitWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_github_webhook_requires_valid_hmac_and_queues_sync(): void
    {
        Queue::fake();
        $connection = GitConnection::factory()->create([
            'provider' => 'github',
            'webhook_secret' => 'a-long-webhook-secret',
        ]);
        $payload = json_encode(['repository' => ['id' => 1]], JSON_THROW_ON_ERROR);

        $this->call('POST', "/api/git/webhooks/{$connection->id}", [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $payload, 'a-long-webhook-secret'),
        ], $payload)->assertAccepted();

        Queue::assertPushed(SyncGitConnection::class, fn ($job): bool => $job->connectionId === $connection->id);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        Queue::fake();
        $connection = GitConnection::factory()->create([
            'provider' => 'gitlab',
            'webhook_secret' => 'a-long-webhook-secret',
        ]);

        $this->postJson("/api/git/webhooks/{$connection->id}", [], [
            'X-Gitlab-Token' => 'wrong',
        ])->assertUnauthorized();

        Queue::assertNothingPushed();
    }
}
