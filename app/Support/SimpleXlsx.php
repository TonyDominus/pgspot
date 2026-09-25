<?php

namespace App\Support;

use ZipArchive;

class SimpleXlsx
{
    /**
     * @return list<array<string, string>>
     */
    public static function rows(string $path, int $sheetIndex = 0): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \InvalidArgumentException("Impossibile aprire il file Excel: {$path}");
        }

        $shared = self::sharedStrings($zip);
        $target = self::sheetTarget($zip, $sheetIndex);
        $xml = $zip->getFromName($target);
        $zip->close();

        if (! is_string($xml)) {
            throw new \InvalidArgumentException("Foglio Excel non trovato: {$target}");
        }

        $sheet = simplexml_load_string($xml);
        if ($sheet === false) {
            throw new \InvalidArgumentException('Foglio Excel non leggibile.');
        }

        $namespaces = $sheet->getNamespaces(true);
        $default = $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rows = [];
        $sheetData = $sheet->children($default)->sheetData ?? null;
        foreach ($sheetData?->row ?? [] as $row) {
            $values = [];
            foreach ($row->children($default)->c as $cell) {
                $attributes = $cell->attributes();
                $ref = (string) $attributes['r'];
                $column = preg_replace('/\d+/', '', $ref) ?: '';
                $type = (string) $attributes['t'];
                $raw = (string) $cell->children($default)->v;
                if ($type === 's') {
                    $raw = $shared[(int) $raw] ?? '';
                } elseif ($type === 'inlineStr') {
                    $raw = (string) ($cell->children($default)->is->t ?? '');
                }
                $values[$column] = $raw;
            }
            $rows[] = $values;
        }

        return $rows;
    }

    /** @return list<string> */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (! is_string($xml)) {
            return [];
        }

        $shared = simplexml_load_string($xml);
        if ($shared === false) {
            return [];
        }

        $namespaces = $shared->getNamespaces(true);
        $default = $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $strings = [];
        foreach ($shared->children($default)->si as $item) {
            $text = '';
            foreach ($item->children($default)->t as $node) {
                $text .= (string) $node;
            }
            if ($text === '' && isset($item->r)) {
                foreach ($item->children($default)->r as $run) {
                    $text .= (string) $run->children($default)->t;
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private static function sheetTarget(ZipArchive $zip, int $sheetIndex): string
    {
        $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
        $rels = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
        if ($workbook === false || $rels === false) {
            throw new \InvalidArgumentException('Workbook Excel non leggibile.');
        }

        $namespaces = $workbook->getNamespaces(true);
        $default = $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sheets = $workbook->children($default)->sheets->sheet ?? [];
        $sheet = $sheets[$sheetIndex] ?? null;
        if ($sheet === null) {
            throw new \InvalidArgumentException('Il foglio richiesto non esiste.');
        }

        $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $rid) {
                $target = (string) $relationship['Target'];

                return str_starts_with($target, 'xl/') ? $target : 'xl/'.ltrim($target, '/');
            }
        }

        throw new \InvalidArgumentException('Relazione del foglio Excel non trovata.');
    }
}
