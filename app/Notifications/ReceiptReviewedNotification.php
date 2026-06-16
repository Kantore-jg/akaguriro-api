<?php

namespace App\Notifications;

use App\Models\PaymentReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceiptReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private PaymentReceipt $receipt,
        private string $decision,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->decision === 'approved' ? 'validé' : 'rejeté';

        return (new MailMessage)
            ->subject('Reçu de paiement '.$status)
            ->line("Votre reçu de paiement a été {$status}.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'receipt_reviewed',
            'decision' => $this->decision,
            'receipt_id' => $this->receipt->id,
            'message' => $this->decision === 'approved'
                ? 'Votre reçu a été validé.'
                : 'Votre reçu a été rejeté.',
        ];
    }
}