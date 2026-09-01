# Prompt für das contao-wertungsportal-bundle: Ranglisten-Schnittstelle

> **Zweck dieser Datei.** Der folgende Abschnitt ist als Prompt gedacht und wird
> unverändert in eine Sitzung übergeben, die am
> `contao-wertungsportal-bundle` arbeitet. Er beschreibt eine Funktion, die
> dort fehlt und die das `contao-topwertungszahlen-bundle` braucht, um von der
> abgekündigten DeWIS-SOAP-Schnittstelle wegzukommen.
>
> Im Wertungsportal-Repository wird von dieser Sitzung aus **nichts** geändert.

---

## Prompt (ab hier kopieren)

Im `contao-wertungsportal-bundle` fehlt eine öffentliche Funktion, die
**DWZ- und Elo-Ranglisten für Deutschland nach Altersklasse und Geschlecht**
zurückgibt. Bitte baue sie ein.

### Warum

Das Bundle `schachbulle/contao-topwertungszahlen-bundle` erzeugt die
Spitzenwertungszahlen des DSB (Top-10-Listen für Männer, Frauen, U20, U20w,
Ü50, Ü50w, Ü65, Ü65w, Ü75, Ü75w — jeweils DWZ und Elo). Es holt die Daten
heute noch über die DeWIS-SOAP-Schnittstelle
(`https://dwz.svw.info/services/soap/index.php`) mit `bestOfFederation()`
und — für jeden einzelnen Spieler der Trefferliste — `tournamentCardForId()`,
nur um `fideNation == 'GER'` zu prüfen. Das ist der Grund für die
Ausführungszeiten von mehreren Minuten je Liste, und die Quelle ist
mittelfristig weg.

Das Wertungsportal hat die Daten längst: Endpunkt
`/dwz/dwzliste/persons?vkz=&limit=&gender=&minAge=&maxAge=`
(in `API::autoQuery`, `case 'Verbandsliste'`) und als örtlicher Spiegel
`Lokal::verbandsliste()` samt `tl_wertungsportal_persons`,
`tl_wertungsportal_persons_memberships` und `tl_wertungsportal_elo`. Was
fehlt, ist eine **fertig aufbereitete, benannte Rangliste** statt einer rohen
Personenliste — sonst baut jedes aufrufende Bundle Platzziffern,
Nationenfilter und FIDE-Anreicherung ein zweites Mal nach.

### Was gebaut werden soll

Eine neue Klasse `Schachbulle\ContaoWertungsportalBundle\Helper\Ranglisten`
mit drei öffentlichen, statischen Methoden.

#### 1. `Ranglisten::dwz(array $params = []): array`

Liefert eine nach DWZ absteigend sortierte Rangliste.

| Parameter | Vorgabe | Bedeutung |
| --- | --- | --- |
| `limit` | `50` | Anzahl der gewünschten Plätze **nach** allen Filtern |
| `geschlecht` | `''` | `''` = alle, sonst `MALE` oder `FEMALE` (Schreibweise der Personentabelle) |
| `alter_von` | `0` | Mindestalter in Jahren, `0` = keine Untergrenze (Ü50 ⇒ `50`) |
| `alter_bis` | `0` | Höchstalter in Jahren, `0` = keine Obergrenze (U20 ⇒ `20`) |
| `nation` | `'GER'` | Nationenfilter, `''` = alle Nationen |
| `vkz` | `''` | VKZ-Präfix zur Einschränkung auf einen Verband, `''` = ganzer DSB |
| `nur_aktive` | `true` | Nur Spieler mit laufender Mitgliedschaft (DeWIS überging Passive ebenso) |

#### 2. `Ranglisten::elo(array $params = []): array`

Dieselben Parameter, aber sortiert nach Elo aus `tl_wertungsportal_elo`.
Zusätzlich:

* Der Nationenfilter greift hier auf `country` der Elo-Tabelle.
* Spieler mit einem `i`-Flag (inaktiv) bleiben draußen — so macht es die
  heutige Auswertung im Topwertungszahlen-Bundle auch
  (`flag NOT LIKE '%i%'`).
* Das Alter kommt aus `birthday` (Geburtsjahr) der Elo-Tabelle.
* Die Zuordnung zur DSB-Person läuft über `tl_wertungsportal_persons.fideId`.
  **Dieser Punkt ist der eigentliche Gewinn:** Das Topwertungszahlen-Bundle
  sucht die Personen bisher mit `searchByName()` über DeWIS zusammen und rät
  bei Namensgleichheit. Mit `fideId` ist das ein Join.
  Findet sich keine Person, bleiben die Personenfelder leer — der Elo-Eintrag
  fällt aber **nicht** aus der Liste.

#### 3. `Ranglisten::listentypen(): array`

Gibt die zehn Standardlisten mit ihren Parametern und einem festen Schlüssel
zurück, damit der Schlüssel nicht in jedem Bundle neu erfunden wird:

| Schlüssel | `alter_von` | `alter_bis` | `geschlecht` |
| --- | --- | --- | --- |
| `alle` | 0 | 0 | `''` |
| `w` | 0 | 0 | `FEMALE` |
| `u20` | 0 | 20 | `''` |
| `u20w` | 0 | 20 | `FEMALE` |
| `50+` | 50 | 0 | `''` |
| `50w+` | 50 | 0 | `FEMALE` |
| `65+` | 65 | 0 | `''` |
| `65w+` | 65 | 0 | `FEMALE` |
| `75+` | 75 | 0 | `''` |
| `75w+` | 75 | 0 | `FEMALE` |

Die Altersgrenzen sind bewusst so und nicht anders: U20 heißt „hat das
20. Lebensjahr noch nicht vollendet" und wurde im Topwertungszahlen-Bundle
zweimal korrigiert (1.5.4 von 19 auf 20, 1.5.6 die Rückfrage dazu). Bitte die
Grenze nicht „aufräumen".

### Rückgabe

```php
[
    'stand'  => 1756...,   // Zeitstempel der ältesten beteiligten Daten
    'quelle' => 'api',     // 'api' oder 'lokal' (Notbetrieb)
    'liste'  => [
        [
            'platz'          => 1,        // Platzziffer, bei Gleichstand geteilt
            'rang'           => 1,        // laufende Nummer, immer eindeutig
            'pkz'            => '...',    // nuLigaPersonId
            'vorname'        => '...',
            'nachname'       => '...',
            'titel'          => '',       // nationaler Titel, max. 3 Zeichen
            'geschlecht'     => 'MALE',
            'geburtsjahr'    => '1985',   // nur das Jahr, siehe unten
            'dwz'            => 2345,
            'dwz_index'      => 42,
            'dwz_formatiert' => '2345-42',
            'fide_id'        => 1234567,
            'elo'            => 2301,
            'elo_partien'    => 12,
            'fide_titel'     => 'IM',
            'fide_titel_w'   => 'WGM',
            'nation'         => 'GER',
            'vkz'            => '30123',
            'verein'         => '...',
            'verbandskuerzel'=> 'BER',    // BAD, BAY, BER, …
        ],
        // …
    ],
]
```

### Regeln, die nicht offensichtlich sind

1. **Platzziffer mit Gleichstand.** Bei gleicher Wertungszahl bekommen alle
   Beteiligten dieselbe Platzziffer; die nächste abweichende Zahl bekommt die
   Platzziffer der laufenden Nummer (1, 2, 2, 4 — nicht 1, 2, 2, 3). Das
   Topwertungszahlen-Bundle führt diese Logik heute selbst mit `$altplatz` /
   `$altrating` und schneidet die Ausgabe bei `platz <= 10` ab; deshalb muss
   `platz` mitkommen, nicht nur `rang`.
2. **Sortierung bei Gleichstand.** Zweiter Schlüssel ist der höhere
   Wertungsindex, dann Nachname, Vorname. `Lokal::verbandsliste()` sortiert
   diesen zweiten Schlüssel bewusst in PHP nach, weil ein zweites `ORDER BY`
   den Index `(published, rating)` unbrauchbar macht (gemessen 972 ms statt
   40 ms). Bitte genauso lassen.
3. **Keine Doppelten.** Ein Spieler mit mehreren Mitgliedschaften darf genau
   einmal in der Liste stehen. Im Topwertungszahlen-Bundle war das ein echter
   Fehler (Version 1.5.0: „Spieler mit gleicher ID wurden in dwz.json
   geschrieben"). Die erste laufende Mitgliedschaft nach VKZ gewinnt, wie es
   `Classes/Bestenliste.php` schon macht.
4. **Geburtsjahr.** `tl_wertungsportal_persons.birthyear` führt entweder
   `JJJJ` oder `TT.MM.JJJJ`. Die vorhandene Lösung in `Lokal::verbandsliste()`
   (`RIGHT(p.birthyear, 4)`) ist die richtige und soll wiederverwendet
   werden. Der Filter arbeitet auf Jahren, nicht auf Stichtagen — das
   entspricht dem bisherigen Verhalten der Elo-Auswertung.
5. **Nationenfilter.** Die Schnittstelle kennt keinen Nationenparameter.
   Prüfe `nation` beziehungsweise `fideNation` der Personentabelle und lasse
   — wie `Classes/Bestenliste.php` — leere Werte als deutsch durchgehen
   (`nation = 'GER' OR nation = ''`), sonst verschwinden Spieler, deren Satz
   noch nie vollständig abgeglichen wurde. **Bitte dokumentieren**, dass ein
   Aufruf über die Schnittstelle deshalb überziehen muss: Wer 50 Deutsche
   will, fragt mehr Zeilen an und filtert nach. Ein sinnvoller Faktor und
   eine Obergrenze gehören in die Funktion, nicht in den Aufrufer.
6. **Verbandskürzel.** BAD, BAY, BER … kamen bisher aus dem ersten Zeichen
   der DeWIS-ID. Künftig aus der VKZ der laufenden Mitgliedschaft
   (`Helper::vkzPraefix()` / `Helper::getVerband()`), damit es nur eine
   Regel gibt.
7. **Verstorbene, Gesperrte, Unveröffentlichte** bleiben draußen
   (`verstorben != '1'`, `blocked != '1'`, `published = '1'`).
8. **Notbetrieb.** Die Funktion muss über `API::autoQuery` laufen, damit die
   dreistufige Auslieferung (Schnittstelle → Zwischenspeicher → lokaler
   Spiegel) und `Helper::cacheHinweis()` greifen. `quelle` und `stand` in der
   Rückgabe bilden das ab.

### Zwischenspeicher

Eine Rangliste ändert sich höchstens täglich. Bitte einen Cacheschlüssel aus
allen Parametern bilden und die vorhandene Cacheverwaltung des Bundles nutzen
(`Classes/Cacheverwaltung.php`). Der Aufrufer erzeugt zehn DWZ- und zehn
Elo-Listen hintereinander; ohne Zwischenspeicher trifft das die Schnittstelle
zwanzigmal.

### Rahmenbedingungen

* PHP `^7.4 || ^8.0`, `declare(strict_types=1);`
* Lauffähig unter Contao 4.13 **und** 5.7
* Deutsche Kommentarblöcke über jeder Methode nach Hausvorgabe: was sie tut,
  wozu jeder Parameter dient, was zurückkommt, was bei leerem Ergebnis
  passiert, und die Begründung dort, wo der naheliegende Weg nicht
  funktioniert
* Unit-Tests für Platzziffernvergabe bei Gleichstand, Altersfilter mit beiden
  `birthyear`-Formaten und Entdopplung
* `CHANGELOG.md` pflegen, Version ohne `v`-Präfix
* Dokumentation der Funktion in `docs/`, mit einem vollständigen
  Aufrufbeispiel

### Abnahme

Ein Aufruf

```php
$liste = \Schachbulle\ContaoWertungsportalBundle\Helper\Ranglisten::dwz([
    'limit'      => 50,
    'alter_bis'  => 20,
    'geschlecht' => 'FEMALE',
]);
```

liefert die 50 besten deutschen Spielerinnen unter 20 Jahren mit geteilten
Platzziffern, ohne Doppelte, mit Verein und Verbandskürzel — und zwar auch
dann, wenn die Schnittstelle des DSB gerade nicht antwortet.

Bitte melde am Ende zurück, unter welcher **Version** des Bundles die
Funktion verfügbar ist, damit das Topwertungszahlen-Bundle die Abhängigkeit
`"schachbulle/contao-wertungsportal-bundle": "^X.Y"` sauber setzen kann.

## Prompt (bis hier kopieren)

---

## Was danach im eigenen Bundle passiert

Sobald die Funktion steht, wird `src/Resources/public/Rangliste.php`
umgebaut. Dabei fallen weg:

* der komplette `SOAPClient`-Aufbau samt `stream_context`-Notlösung,
* `bestOfFederation()` und der Einzelabruf `tournamentCardForId()` je Spieler,
* `searchByName()` für die Elo-Zuordnung,
* `Verband()` und `Verbandskuerzel()` als eigene DeWIS-Abrufe.

Nicht ersetzt wird die Speicherung in `tl_topwertungszahlen` und
`tl_topwertungszahlen_ratings` — die bleibt hier, weil sie die Historie der
Listen führt und die Spielerfotos daran hängen.
