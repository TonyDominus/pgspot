<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\User;
use Throwable;

class SafeMail
{
    public static function sendVerification(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();
            self::clearLastError();

            return true;
        } catch (Throwable $e) {
            self::recordError($e);

            return false;
        }
    }

    public static function send($notifiable, object $notification): bool
    {
        try {
            $notifiable->notify($notification);
            self::clearLastError();

            return true;
        } catch (Throwable $e) {
            self::recordError($e);

            return false;
        }
    }

    public static function lastError(): ?array
    {
        $value = AppSetting::getValue('system.last_mail_error');

        return is_array($value) ? $value : null;
    }

    private static function recordError(Throwable $e): void
    {
        report($e);

        AppSetting::setValue('system.last_mail_error', [
            'at' => now()->toIso8601String(),
            'message' => $e->getMessage(),
        ]);
    }

    private static function clearLastError(): void
    {
        AppSetting::setValue('system.last_mail_error', null);
    }
}
