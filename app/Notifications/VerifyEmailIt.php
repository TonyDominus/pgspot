<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailIt extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Conferma il tuo account su PG Spot')
            ->greeting('Benvenuto su PG Spot!')
            ->line('Per attivare il tuo account, conferma il tuo indirizzo email cliccando il pulsante qui sotto.')
            ->action('Verifica email', $url)
            ->line('Se non hai creato un account, puoi ignorare questa email.')
            ->salutation('A presto, il team PG Spot');
    }
}
