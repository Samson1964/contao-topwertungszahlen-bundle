# Topwertungszahlen für den DSB

Stellt die Spitzenwertungszahlen des Deutschen Schachbundes als Ranglisten
dar: zwanzig Listen — zehn nach DWZ und zehn nach FIDE-Elo, jeweils für alle
Spieler, Frauen, U20, U20w, Ü50, Ü50w, Ü65, Ü65w, Ü75 und Ü75w.

Die Daten kommen aus dem
[contao-wertungsportal-bundle](https://github.com/Samson1964/contao-wertungsportal-bundle)
(ab dessen Version 1.34.0). Bis Version 1.6.3 stammten sie aus der
abgeschalteten DeWIS-Schnittstelle.

Läuft unter **Contao 4.13 und Contao 5.3**.

## Installation

```bash
composer require schachbulle/contao-topwertungszahlen-bundle
```

Danach `contao:migrate` ausführen.

## Verwendung

Im Backend unter *Inhalte → Topwertungszahlen*. Dort werden die Spieler, ihre
Platzierungen und ihre Fotos verwaltet; die globale Operation *Wertungen
importieren* stößt einen Ranglistenlauf an.

Für den Cron des Servers:

```bash
vendor/bin/contao-console topwertungszahlen:rangliste
```

Ein Lauf schreibt die Platzierungen in die Datenbank und legt
`files/topwertungszahlen/dwz.json` und `files/topwertungszahlen/elo.json` an.

Wird nachträglich ein Foto hinterlegt, genügt *Nur Fotos auffrischen*
beziehungsweise:

```bash
vendor/bin/contao-console topwertungszahlen:rangliste --nur-fotos
```

Ausführlich beschrieben in [docs/ranglistenlauf.md](docs/ranglistenlauf.md).

## Lizenz

LGPL-3.0-or-later

**Frank Hoppe**
