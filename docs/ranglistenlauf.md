# Der Ranglistenlauf

Ein Lauf erzeugt zwanzig Ranglisten — zehn nach DWZ und zehn nach FIDE-Elo,
jeweils für alle Spieler, Frauen, U20, U20w, Ü50, Ü50w, Ü65, Ü65w, Ü75 und
Ü75w. Er schreibt die Platzierungen in die Datenbank und legt zwei
Ausgabedateien an.

Ab Version 1.7.0 kommen die Daten aus dem
[contao-wertungsportal-bundle](https://github.com/Samson1964/contao-wertungsportal-bundle)
(`Helper\Ranglisten`, ab dessen Version 1.34.0) und nicht mehr aus der
abgeschalteten DeWIS-Schnittstelle.

## Einen Lauf anstoßen

**Im Backend:** *Inhalte → Topwertungszahlen → Wertungen importieren*. Der Lauf
beginnt sofort beim Aufruf der Maske. Er ist in 21 Schritte zerlegt — zwanzig
Listen und das Schreiben der Dateien —, die nacheinander abgerufen werden;
darüber laufen ein Fortschrittsbalken und ein Protokoll mit einer Zeile je
Schritt. Bricht ein Schritt ab, hält der Lauf an und die Meldung steht rot im
Protokoll.

Die Schrittfolge ist kein Selbstzweck: Bei kaltem Zwischenspeicher des
Wertungsportals kostet jede Liste einen Abruf an die Schnittstelle, und ein
Lauf kann dann eine Minute dauern. Gestreamte Ausgabe wäre eleganter, aber
unzuverlässig — Zwischenspeicher in PHP, im Webserver und im Vorschaltserver
halten die Teilausgabe zurück, und die Anzeige stünde wieder bis zum Schluß
still.

**Auf der Kommandozeile**, etwa aus dem Cron des Servers:

```bash
vendor/bin/contao-console topwertungszahlen:rangliste
```

Beide Wege benutzen dieselbe Klasse `Classes\Ranglistenlauf` und tun genau
dasselbe — nur arbeitet die Kommandozeile die Schritte ohne Rückfrage in einem
Rutsch ab, weil dort niemand auf einen Balken sieht.

## Was ein Lauf schreibt

### In der Datenbank

| Tabelle | Was hineinkommt |
| --- | --- |
| `tl_topwertungszahlen` | Spieler, die noch nicht darin stehen |
| `tl_topwertungszahlen_ratings` | Je Liste die besten **50** Plätze des Tages |

Ein zweiter Lauf am selben Tag läßt bereits festgehaltene Platzierungen
unverändert. Das ist Absicht: Die einmal veröffentlichte Rangliste eines Tages
soll sich nicht nachträglich verschieben.

Die Spalte `rank` führt seit 1.7.0 die **geteilte** Platzziffer — bei gleicher
Wertungszahl steht dieselbe Ziffer mehrfach, und die nächste abweichende Zahl
überspringt die verbrauchten Ziffern (1, 2, 2, 4). Bis 1.6.3 stand dort eine
laufende Nummer, während die Anzeige schon immer die geteilte Ziffer zeigte.

### Als Datei

```
files/topwertungszahlen/dwz.json
files/topwertungszahlen/elo.json
```

Erreichbar unter `/files/topwertungszahlen/dwz.json` beziehungsweise
`/files/topwertungszahlen/elo.json`.

> **Umstellung gegenüber 1.6.3.** Bis dahin lagen die Dateien in
> `web/bundles/contaotopwertungszahlen/`. Das ist ein Symlink in das
> vendor-Verzeichnis — die Dateien waren nach jedem `composer install` weg.
> **Wer sie von außen liest, muß die URL umstellen.**

Der Aufbau der Dateien ist unverändert. Je Datei ein Objekt, dessen Schlüssel
die Listen sind, dessen Werte die besten zehn Plätze:

```json
{
  "dwz_alle": [
    {
      "rang": 1,
      "pkz": "10285765",
      "vorname": "Vincent",
      "nachname": "Keymer",
      "verband": "RLP",
      "link": "spieler/10285765.html",
      "rating": 2701,
      "title": "GM",
      "foto": "files/spielerfotos/keymer.jpg",
      "quelle": "Deutscher Schachbund"
    }
  ],
  "dwz_w": []
}
```

Bei geteilten Platzziffern können mehr als zehn Einträge in einer Liste
stehen. In den Elo-Listen zeigt `link` auf das FIDE-Profil.

Das Feld `pkz` kam mit 1.8.0 dazu. Für die Anzeige wird es nicht gebraucht; es
macht das Auffrischen der Fotos möglich, ohne die Wertungszahlen neu zu
beschaffen. Bei Elo-Einträgen ohne zugeordnete Person bleibt es leer.

Geschrieben wird über eine Zwischendatei und `rename()`. Ein Seitenaufruf, der
genau während des Laufs liest, bekommt dadurch nie einen halben Datenbestand.

## Nur die Fotos auffrischen

Wird zu einem Spieler der Top-10 nachträglich ein Foto hinterlegt, muß dafür
nicht der ganze Lauf wiederholt werden — und das brächte auch nichts: Die
Platzierungen des Tages stehen schon in der Auswertungstabelle und werden von
einem zweiten Lauf bewußt nicht mehr angefaßt.

**Im Backend:** *Inhalte → Topwertungszahlen → Nur Fotos auffrischen*.

**Auf der Kommandozeile:**

```bash
vendor/bin/contao-console topwertungszahlen:rangliste --nur-fotos
```

Was dabei geschieht: Die beiden vorhandenen Ausgabedateien werden gelesen, zu
jedem Eintrag über seine PKZ das jüngste Foto nachgeschlagen und die Dateien
zurückgeschrieben. Maßgeblich ist das heutige Datum, nicht das Datum der Liste
— gesucht ist ja gerade das neu hinzugekommene Bild.

**Nicht angefaßt werden** das Wertungsportal, `tl_topwertungszahlen` und
`tl_topwertungszahlen_ratings`. Es entsteht auch kein Spielerdatensatz: Wer
nicht in der Tabelle steht, behält seinen leeren Fotoeintrag.

Gibt es die Ausgabedateien noch nicht, sagt die Meldung das — dann muß erst
ein vollständiger Lauf laufen.

Nebenbei räumt die Auffrischung auf: Zeigt ein Eintrag auf eine Bilddatei, die
es nicht mehr gibt, wird der Verweis geleert, statt auf der Webseite ein
kaputtes Bild zu erzeugen.

## Warum nur Deutsche in den Listen stehen

Diese Frage beantwortet das Wertungsportal-Bundle, nicht dieses hier. Ab dessen
Version **1.35.2** fragt der Nationenfilter in drei Stufen, und die erste
bekannte Antwort entscheidet:

1. **Die FIDE-Föderation** — `country` aus `tl_wertungsportal_elo`,
   nachgeschlagen über die FIDE-ID der Person.
2. **Die Nation der Mitgliederdatei** — `tl_wertungsportal_persons.nation`.
3. **Ist beides unbekannt, bleibt der Spieler in der Liste.** Ausgeschlossen
   wird nur, wer nachweislich anderswo geführt wird.

Die Föderation steht bewußt vorn. Georg Meier ist laut Mitgliederdatei
Deutscher, tritt aber für Uruguay an — in einer deutschen Rangliste hat er
nichts zu suchen, und andersherum würde sich jeder Leser wundern. Eine
Rangliste beantwortet die Frage „wer spielt für Deutschland?", nicht „wer hat
einen deutschen Paß?".

Das Feld `persons.fideNation` wird seit 1.35.2 **nicht** mehr herangezogen: Es
stammt wie `nation` aus dem CSV-Import und ist kaum gepflegt; ein vereinzelt
gefüllter, veralteter Wert hätte sonst den monatlich frischen Elo-Bestand
geschlagen.

> **Zwischenstand in 1.7.1 bis 1.7.3.** In diesen Fassungen prüfte der Lauf
> selbst nach, weil das Wertungsportal damals nur die Mitgliederdatei heranzog
> und ein leerer Wert dort als deutsch galt — in den Top-10-Listen standen
> dadurch Ausländer. Seit 1.7.4 ist die Nachprüfung wieder draußen: Sie wäre
> nicht nur doppelt, sondern auch strenger als die Staffel gewesen und hätte
> Spieler mit deutscher Föderation verworfen, deren Mitgliederdatei etwas
> anderes sagt. Deshalb setzt die `composer.json` seit 1.7.5
> `schachbulle/contao-wertungsportal-bundle: ^1.35.2` voraus — 1.35.0 fragte
> noch in der falschen Reihenfolge, 1.35.1 ließ sich von einem veralteten
> `fideNation` schlagen.

**Der Filter steht und fällt mit dem FIDE-Elo-Import.** Ohne ihn ist
`tl_wertungsportal_elo` leer, die erste Stufe liefert nie eine Antwort, und es
bleibt die kaum gepflegte Nation der Mitgliederdatei. Der Import läuft im
Backend unter *WP | FIDE-Elo → XML-Import*. Nachsehen, wie viele der Spieler
überhaupt erreichbar sind:

```sql
SELECT COUNT(*) AS personen,
       SUM(fideId > 0) AS mit_fide_id
FROM tl_wertungsportal_persons WHERE published = '1';

SELECT COUNT(*) FROM tl_wertungsportal_elo;
```

## Wie Spieler zugeordnet werden

Die Tabelle `tl_topwertungszahlen` führte ihre Spieler bis 1.6.3 über
`dewis_id` — die Kennziffer der abgeschalteten DeWIS-Schnittstelle. Das
Wertungsportal liefert die PKZ (`nuLigaPersonId`). Ob das dieselbe Nummer ist,
hängt vom Bestand ab.

Deshalb sucht ein Lauf in drei Stufen:

1. über das Feld `pkz` — greift ab dem zweiten Lauf immer,
2. über `dewis_id`, falls beide Nummernkreise identisch sind,
3. über Nachname, Vorname und — wenn beidseits bekannt — das Geburtsjahr.

Wird ein Spieler über Stufe 2 oder 3 gefunden, trägt der Lauf seine PKZ nach.
Erst wenn alle drei Stufen leer ausgehen, entsteht ein neuer Datensatz. So
reißt weder der Ranglistenverlauf ab, noch verliert ein Spieler sein Foto.

Bei **Namensgleichheit** in Stufe 3 wird nicht geraten, sondern ein neuer
Datensatz angelegt: Ein falsch zugeordnetes Foto fällt später kaum auf, ein
doppelter Spieler sofort. Der Bericht am Ende des Laufs nennt beide Zahlen —
wie viele Spieler neu angelegt und wie viele über den Namen zugeordnet wurden.

### Vorher nachsehen

Wie der Bestand liegt, zeigt `tools/pruefe-spielerkennung.php`. Die Datei
greift nur lesend zu, in das Wurzelverzeichnis der Installation legen und
aufrufen:

```bash
php pruefe-spielerkennung.php
```

Sie meldet, wie viele `dewis_id` sich als PKZ wiederfinden, ob dahinter auch
dieselbe Person steht, und welche PKZ die übrigen Spieler bekämen. Danach
wieder entfernen.

## Nach der Aktualisierung auf 1.7.x

1. `contao:migrate` laufen lassen — 1.7.0 bringt das Feld `pkz` mit, 1.7.3 die
   Umschreibung der alten Geschlechtskürzel.
2. Prüfen, daß das Wertungsportal-Bundle mindestens in **1.35.2** vorliegt.
   Composer erzwingt das seit 1.7.5.
3. Prüfen, daß Personen-Import und **FIDE-Elo-Import** des
   Wertungsportal-Bundles gelaufen sind. Der Elo-Import ist der wichtigere:
   Ohne ihn hat der Filter keine Föderation und fällt auf die kaum gepflegte
   Nation der Mitgliederdatei zurück.
4. Einen Lauf anstoßen und den Bericht ansehen.
5. Den äußeren Verbraucher der beiden JSON-Dateien auf die neuen URLs
   umstellen (seit 1.7.0 `files/topwertungszahlen/`).
6. Die alten Dateien in `web/bundles/contaotopwertungszahlen/` können weg.
