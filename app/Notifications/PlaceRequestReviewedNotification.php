<?php

namespace App\Notifications;

use App\Models\PlaceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlaceRequestReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private PlaceRequest $placeRequest,
        private string $decision,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->decision === 'approved' ? 'approuvée' : 'rejetée';

        return (new MailMessage)
            ->subject('Demande de place '.$status)
            ->line("Votre demande de place a été {$status}.")
            ->line('Marché : '.$this->placeRequest->market?->name);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'place_request_reviewed',
            'decision' => $this->decision,
            'place_request_id' => $this->placeRequest->id,
            'market_id' => $this->placeRequest->market_id,
            'message' => $this->decision === 'approved'
                ? 'Votre demande de place a été approuvée.'
                : 'Votre demande de place a été rejetée.',
        ];
    }
}