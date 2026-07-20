<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeVerifiedNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account attivato — benvenuto su PG Spot')
            ->greeting('Ciao '.$notifiable->name.'!')
            ->line('Il tuo account è stato verificato con successo. Ora puoi contribuire alla mappa, salvare preferiti e lasciare recensioni.')
            ->action('Esplora la mappa', route('home', absolute: true))
            ->line('Grazie per far parte della community di PG Spot!')
            ->salutation('A presto, il team PG Spot');
    }
}
