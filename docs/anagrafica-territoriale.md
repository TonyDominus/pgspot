# Anagrafica territoriale

La fonte canonica è l'elenco ISTAT dei codici statistici delle unità amministrative, pubblicato tramite SITUAS.

| | |
| --- | --- |
| Fonte | ISTAT, Codici statistici delle unità amministrative territoriali |
| Pagina | https://www.istat.it/classificazione/codici-dei-comuni-delle-province-e-delle-regioni/ |
| File | https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.xlsx |
| Formato | XLSX, foglio `CODICI al 21_02_2026` |
| Data di riferimento | 21 febbraio 2026 |
| Ultima modifica del file | 26 febbraio 2026 |
| Data di download | 22 settembre 2026 |
| Comuni dichiarati | 7.894 |

Copia nel repository: `database/data/territories/istat/Elenco-comuni-italiani.xlsx`.

Il file non contiene coordinate. Le coordinate già presenti in PGSpot restano; non si fa geocoding.

## Campi usati

| Colonna ISTAT | PGSpot |
| --- | --- |
| Codice Regione | `regions.istat_code` |
| Denominazione Regione | `regions.name` |
| Codice dell'unità territoriale sovracomunale valida a fini statistici | `provinces.istat_code` |
| Denominazione di quell'unità | `provinces.name` |
| Tipologia (1–5) | `provinces.type` |
| Sigla automobilistica | `provinces.code` |
| Codice comune alfanumerico | `municipalities.istat_code` |
| Denominazione italiana e straniera | `municipalities.name` |
| Codice comune numerico con 110 province (2010–2016) | solo per riconoscere un record già importato e aggiornarne il codice |

Tipologie ufficiali:

| Codice | `provinces.type` |
| --- | --- |
| 1 | `province` |
| 2 | `autonomous_province` |
| 3 | `metropolitan_city` |
| 4 | `free_municipal_consortium` |
| 5 | `non_administrative_unit` |

La tabella si chiama ancora `provinces`, ma contiene qualunque unità sovracomunale di questa classificazione. Le unità di tipo 5 del Friuli-Venezia Giulia restano attive: sono la partizione statistica corrente, non enti soppressi.

## Sardegna dal 1° gennaio 2026

L'elenco del 21 febbraio 2026 contiene già i codici validi dal 1° gennaio 2026: Città metropolitana di Sassari (312), Gallura Nord-Est Sardegna (113), Nuoro (114), Oristano (115), Ogliastra (116), Medio Campidano (117), Città metropolitana di Cagliari (318), Sulcis Iglesiente (119).

La corrispondenza con i codici precedenti è nel file ufficiale `Codici-statistici-e-denominazioni-delle-unita-amministrative-della-Sardegna.zip`, conservato insieme alla copia UTF-8 `sardegna-2026.csv`. Quella copia è una transcodifica, non una fonte diversa.

## Cosa la sincronizzazione non tocca

- `municipalities.intro`
- `municipalities.is_indexable`
- slug già presenti
- coordinate già presenti
- righe in `pois`

Un comune o un'unità sovracomunale assenti dall'elenco ufficiale diventano `is_active = false`. Non vengono cancellati. Form, contributi, nuovi POI e lookup mostrano solo i record attivi. Se un comune disattivato è ancora agganciato a un POI, il comando lo segnala e non sceglie un successore.

```bash
php artisan pgspot:import-territories --istat
```

Un secondo lancio sullo stesso file non deve creare duplicati.

## File non più canonici

`database/data/territories/legacy/regioni.json`, `province.json` e `comuni.json` sono un'anagrafica precedente, di provenienza non verificata. Servono solo come archivio. Non vanno usati per aggiornare nomi, codici, tipi o relazioni.
