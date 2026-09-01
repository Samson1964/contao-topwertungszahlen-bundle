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
beginnt sofort beim Aufruf der Maske; die Meldungen erscheinen, sobald er
fertig ist.

**Auf der Kommandozeile**, etwa aus dem Cron des Servers:

```bash
vendor/bin/contao-console topwertungszahlen:rangliste
```

Beide Wege benutzen dieselbe Klasse `Classes\Ranglistenlauf` und tun genau
dasselbe.

Ein Lauf dauert wenige Sekunden. Über DeWIS brauchte er Minuten je Liste —
daher die frühere Aufteilung in zwanzig Einzelaufrufe, die es nicht mehr gibt.

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

Geschrieben wird über eine Zwischendatei und `rename()`. Ein Seitenaufruf, der
genau während des Laufs liest, bekommt dadurch nie einen halben Datenbestand.

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

## Nach der Aktualisierung auf 1.7.0

1. `contao:migrate` laufen lassen — das Feld `pkz` kommt hinzu.
2. Prüfen, daß der Personen-Import des Wertungsportal-Bundles gelaufen ist.
   Der Nationenfilter arbeitet auf `tl_wertungsportal_persons.nation`, und
   dieses Feld stammt allein aus der Vereinsmitglieder-CSV. Ist es überwiegend
   leer, enthalten die Listen auch Ausländer:

   ```sql
   SELECT nation, COUNT(*) FROM tl_wertungsportal_persons GROUP BY nation ORDER BY 2 DESC;
   ```

3. Einen Lauf anstoßen und den Bericht ansehen.
4. Den äußeren Verbraucher der beiden JSON-Dateien auf die neuen URLs
   umstellen.
5. Die alten Dateien in `web/bundles/contaotopwertungszahlen/` können weg.
