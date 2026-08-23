<?php

namespace App\Services;

use App\Mail\DisputeCreated;
use App\Mail\DisputeMessageReceived;
use App\Mail\DisputeStatusUpdated;
use App\Models\Invoice;
use App\Models\InvoiceDispute;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Mail;

class DisputeService
{
    public function createDispute(Invoice $invoice, array $data)
    {
        if ($invoice->isDisputed()) {
            throw new Exception('Invoice already has an active dispute');
        }

        $dispute = InvoiceDispute::create(
            [
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'status' => 'open',
                'reason' => $data['reason'],
                'description' => $data['description'],
            ]
        );

        $invoice->update(['status' => 'disputed']);
        $this->sendDisputeNotifications(
            $dispute,
            'created'
        );

        return $dispute;
    }

    public function updateDisputeStatus(InvoiceDispute $dispute, string $status, ?string $notes = null): InvoiceDispute
    {
        $dispute->update(
            [
                'status' => $status,
                'resolution_notes' => $notes,
                'resolved_at' => in_array(
                    $status,
                    [
                        'resolved',
                        'rejected',
                    ]
                ) ? now() : null,
                'resolved_by' => in_array(
                    $status,
                    [
                        'resolved',
                        'rejected',
                    ]
                ) ? auth()->id() : null,
            ]
        );

        if ($status === 'resolved') {
            $dispute->invoice->update(['status' => 'pending']);
        }

        $this->sendDisputeNotifications(
            $dispute,
            'status_updated'
        );

        return $dispute;
    }

    public function addMessage(InvoiceDispute $dispute, array $data)
    {
        $message = $dispute->messages()->create(
            [
                'user_id' => auth()->id(),
                'message' => $data['message'],
                'attachments' => $data['attachments'] ?? null,
            ]
        );

        $this->sendDisputeNotifications(
            $dispute,
            'new_message'
        );

        return $message;
    }

    protected function sendDisputeNotifications(InvoiceDispute $dispute, string $type)
    {
        $customer = $dispute->customer;
        $adminUsers = User::where(
            'team_id',
            $customer->team_id
        )
            ->whereHas(
                'roles',
                function ($q): void {
                    $q->where(
                        'name',
                        'admin'
                    );
                }
            )->get();

        switch ($type) {
            case 'created':
                Mail::to($adminUsers)->send(new DisputeCreated($dispute));
                break;
            case 'status_updated':
                Mail::to($customer->email)->send(new DisputeStatusUpdated($dispute));
                break;
            case 'new_message':
                $recipient = auth()->id() === $customer->id ? $adminUsers : $customer;
                Mail::to($recipient)->send(new DisputeMessageReceived($dispute));
                break;
        }
    }
}
