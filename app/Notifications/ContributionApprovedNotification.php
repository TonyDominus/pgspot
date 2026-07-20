<?php

namespace App\Notifications;

use App\Models\Poi;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContributionApprovedNotification extends Notification
{
    public function __construct(private Poi $poi) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Il tuo luogo è stato pubblicato su PG Spot')
            ->greeting('Ciao '.$notifiable->name.'!')
            ->line('Ottima notizia: la tua proposta "'.$this->poi->name.'" è stata approvata ed è ora visibile sulla mappa.')
            ->action('Vedi il luogo', route('poi.show', $this->poi->slug, absolute: true))
            ->line('Grazie per aver contribuito a far crescere PG Spot!')
            ->salutation('A presto, il team PG Spot');
    }
}
