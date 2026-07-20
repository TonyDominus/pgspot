<?php

namespace App\Notifications;

use App\Models\Poi;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PoiUpdatedNotification extends Notification
{
    public function __construct(private Poi $poi) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aggiornamento su un luogo che hai segnalato')
            ->greeting('Ciao '.$notifiable->name.',')
            ->line('Il luogo "'.$this->poi->name.'" che hai contribuito a inserire è stato aggiornato dal team.')
            ->action('Vedi le modifiche', route('poi.show', $this->poi->slug, absolute: true))
            ->salutation('Grazie, il team PG Spot');
    }
}
