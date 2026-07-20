<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordIt extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reimposta la password — PG Spot')
            ->greeting('Ciao!')
            ->line('Hai richiesto il reset della password. Clicca il pulsante per sceglierne una nuova.')
            ->action('Reimposta password', $url)
            ->line('Il link scade tra '.$expire.' minuti.')
            ->line('Se non hai richiesto il reset, ignora questa email.')
            ->salutation('Il team PG Spot');
    }
}
