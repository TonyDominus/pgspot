<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestMailNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Test email PG Spot')
            ->greeting('Test riuscito!')
            ->line('Questa è un\'email di prova inviata dal pannello di monitoraggio superadmin.')
            ->line('Mailer attivo: '.config('mail.default'))
            ->line('Inviata il: '.now()->timezone('Europe/Rome')->format('d/m/Y H:i'))
            ->salutation('PG Spot — Sistema');
    }
}
