<?php

namespace App\Console\Commands;

use App\Services\WebhookService;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Process pending webhook events')]
#[Signature('webhooks:process')]
class ProcessWebhooks extends Command
{
    public function __construct(
        protected ?WebhookService $webhookService = null
    ) {
        parent::__construct();
        $this->webhookService = $webhookService ?? app(WebhookService::class);
    }

    public function handle(): int
    {
        if (cache()->get('processing_webhooks')) {
            $this->warn('Webhook processing is already running');

            return Command::FAILURE;
        }

        cache()->put(
            'processing_webhooks',
            true,
            60
        ); // Lock for 60 minutes

        try {
            $this->info('Processing pending webhooks...');

            $processed = $this->webhookService->processPending();

            $this->info("Processed {$processed} webhook events");

            cache()->forget('processing_webhooks');

            return Command::SUCCESS;
        } catch (Exception $e) {
            cache()->forget('processing_webhooks');
            $this->error('Error processing webhooks: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
