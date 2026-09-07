<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\InvoiceResource\Pages;

use App\Filament\Client\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceCheckoutService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Override;

class ViewInvoice extends ViewRecord
{
    #[Override]
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('paddleCheckout')
                ->label('Pay with Paddle')
                ->icon('heroicon-o-credit-card')
                ->visible(fn (): bool => filled(config('services.paddle.webhook_gateway_id'))
                    && in_array($this->getRecord()->getRawOriginal('status'), ['pending', 'overdue'], true))
                ->action(function (): void {
                    $invoice = $this->getRecord();
                    $user = auth()->user();
                    abort_unless($user instanceof User && $invoice instanceof Invoice && InvoiceResource::canView($invoice), 403);
                    try {
                        $url = app(InvoiceCheckoutService::class)->paddleCheckoutUrl($invoice, $user);
                    } catch (\Exception) {
                        Notification::make()->title('Unable to open checkout')
                            ->body('Your invoice has not been marked paid. Please contact support before trying again.')
                            ->danger()->send();

                        return;
                    }
                    $this->redirect($url);
                }),
        ];
    }
}
