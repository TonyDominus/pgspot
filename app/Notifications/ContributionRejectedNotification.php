<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContributionRejectedNotification extends Notification
{
    public function __construct(
        private string $poiName,
        private ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Aggiornamento sulla tua proposta su PG Spot')
            ->greeting('Ciao '.$notifiable->name.',')
            ->line('La tua proposta "'.$this->poiName.'" non è stata pubblicata in questo momento.');

        if ($this->reason) {
            $message->line('Motivo: '.$this->reason);
        }

        return $message
            ->line('Puoi inviare una nuova proposta con informazioni aggiornate.')
            ->action('Aggiungi un luogo', route('contribute.create', absolute: true))
            ->salutation('Grazie, il team PG Spot');
    }
}
