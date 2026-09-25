<?php

namespace App\Support;

use App\Models\Municipality;
use Illuminate\Support\Str;

class MunicipalityResolver
{
    public static function find(string $value): Municipality
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Specifica il comune, ad esempio --municipality=perugia.');
        }

        if (str_starts_with(strtolower($value), 'istat:')) {
            return self::findByIstatCode(substr($value, 6));
        }

        if (ctype_digit($value)) {
            $municipality = Municipality::query()->with('province')->find((int) $value);
            if (! $municipality) {
                $padded = str_pad($value, 6, '0', STR_PAD_LEFT);
                $byCode = Municipality::query()->where('istat_code', $padded)->first();
                $hint = $byCode
                    ? " Se intendevi il codice del dataset usa --municipality=istat:{$padded}."
                    : '';

                throw new \InvalidArgumentException("Comune non trovato: id {$value}.{$hint}");
            }

            return self::ensureActive($municipality);
        }

        $slug = Str::slug($value);
        $matches = Municipality::query()->with('province')->where('slug', $slug)->where('is_active', true)->get();

        if ($matches->isEmpty()) {
            $inactive = Municipality::query()->with('province')->where('slug', $slug)->where('is_active', false)->get();
            if ($inactive->isNotEmpty()) {
                throw new \InvalidArgumentException(self::inactiveMessage($inactive->first()));
            }

            throw new \InvalidArgumentException("Comune non trovato: {$value}. Importa l'anagrafica o crealo in Admin → Territori.");
        }

        if ($matches->count() > 1) {
            $list = $matches->map(fn (Municipality $municipality) => $municipality->name.' ('.$municipality->province?->name.')')->implode(', ');

            throw new \InvalidArgumentException("Comune ambiguo «{$slug}»: {$list}. Usa --municipality=istat:CODICE oppure l'id del record.");
        }

        return $matches->first();
    }

    private static function findByIstatCode(string $code): Municipality
    {
        $code = trim($code);
        if ($code === '' || ! ctype_digit($code)) {
            throw new \InvalidArgumentException('Codice istat non valido. Esempio: --municipality=istat:054039.');
        }

        $padded = str_pad($code, 6, '0', STR_PAD_LEFT);
        $municipality = Municipality::query()->with('province')->where('istat_code', $padded)->first();

        if (! $municipality) {
            throw new \InvalidArgumentException("Comune non trovato per il codice {$padded}.");
        }

        return self::ensureActive($municipality);
    }

    private static function ensureActive(Municipality $municipality): Municipality
    {
        if (! $municipality->is_active) {
            throw new \InvalidArgumentException(self::inactiveMessage($municipality));
        }

        return $municipality;
    }

    private static function inactiveMessage(Municipality $municipality): string
    {
        $code = $municipality->istat_code ?: 'senza codice';

        return "Il comune {$municipality->name} ({$code}) non è più attivo. Nessun comune successore viene scelto in automatico.";
    }
}
