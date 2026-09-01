# Topwertungszahlen Changelog

## Version 1.7.3 (2026-09-01)

* Fix: **Das Geschlecht wurde im Backend nicht erkannt.** Bis 1.6.3 übernahm
  der Ranglistenlauf den Wert so, wie DeWIS ihn lieferte — als einzelnen
  Buchstaben. Das Auswahlfeld kennt aber `male`, `female` und `other`, ein `m`
  oder `f` traf also keine Möglichkeit: Das Feld stand leer, und beim Speichern
  wäre der Wert verschwunden. Eine Migration schreibt die Kürzel einmalig um
  (`m`, `f`, `w`, `d`); **`contao:migrate` ist erforderlich**
* Fix: **In den Schaltflächen der Spielerliste stand ein rohes `%s`** statt des
  Namens — bei allen sieben, nicht nur beim Info-Knopf. Contao ersetzt den
  Platzhalter nur im Titel und nur durch die Datensatz-ID; der Text am Symbol
  bleibt unangetastet. Ein `button_callback` setzt jetzt den Spielernamen in
  beide Texte ein („Spielerdetails von Keymer, Vincent anzeigen"). Ein
  Datensatz ohne Namen zeigt seine ID. Die Umschaltfläche erzeugt Haste selbst
  und läuft nicht über den Callback — dort steht der Name nicht mehr in der
  Beschriftung, dafür auch kein Platzhalter mehr
* Add: **Hinweis auf die Anforderungen an ein Spielerfoto** über der
  Dateiauswahl: quadratischer Ausschnitt, Kopf von der Bildmitte bis kurz vor
  den oberen Rand, Oberkörper bis zur Brust, Blick in die Kamera, Arme nicht
  angeschnitten. Daneben eine schematische Zeichnung des Bildausschnitts mit
  den beiden Hilfslinien und ein Verweis auf ein Beispielfoto. Die Zeichnung
  ist ein eingebettetes SVG — kein `contao:assets:install` nötig
* Fix: `dewis_id` ist kein Pflichtfeld mehr. DeWIS ist abgeschaltet, neu
  angelegte Spieler haben nur eine PKZ; als Pflichtfeld hätte es jede
  Bearbeitung im Backend blockiert
* Change: In `tl_topwertungszahlen` stand der Block `global_operations`
  **zweimal**. PHP behält bei doppeltem Schlüssel den letzten, der erste war
  also wirkungslos — er ist jetzt weg
* Change: Die Beschreibung des Feldes „Geschlecht" nannte als Beispiel noch
  „M oder F"

## Version 1.7.2 (2026-09-01)

* Fix: **Zwei Sprachdateien waren ISO-8859-1 statt UTF-8**, und zwar die
  beiden mit sichtbarem Text. Die Umlaute erschienen im Backend als
  Fragezeichen im Rhombus — betroffen waren die Rückfrage vor dem Import
  („Der Dienst l?uft einmal t?glich …", `languages/de/default.php`) und die
  Beschreibung des Backend-Moduls („Topranglisten f?r das Frontend
  verwalten", `languages/de/modules.php`). Beide Dateien sind jetzt UTF-8
  ohne BOM, wie alle anderen im Paket

## Version 1.7.1 (2026-09-01)

* Fix: **In den DWZ-Listen standen Ausländer** — in allen zehn, also auch bei
  den Frauen und in den Jugendlisten. `Ranglisten::dwz()` des
  Wertungsportal-Bundles filtert gegen `tl_wertungsportal_persons.nation`, und
  dort zählt ein LEERER Wert als deutsch. Für eine Verbandsrangliste ist das
  richtig, für die Spitzenwertungszahlen des DSB nicht. Der Lauf prüft jetzt
  nach dem Abruf zusätzlich die **FIDE-Föderation** — genau wie es die alte
  DeWIS-Fassung tat (`member->fideNation == 'GER'`) — und verwirft, wer eine
  bekannte andere Föderation trägt. Die Föderation kommt aus
  `tl_wertungsportal_persons.fideNation`, ersatzweise über die FIDE-ID aus
  `tl_wertungsportal_elo.country`. Wer gar keine trägt, bleibt in der Liste;
  die Meldung des Schritts nennt beide Zahlen
* Change: Die geteilten Platzziffern werden nach dieser Prüfung **neu
  vergeben**, sonst entstünden Lücken (1, 2, 4, 7 statt 1, 2, 2, 4)
* Change: Die DWZ-Listen werden doppelt so lang angefordert wie gebraucht,
  damit sie nach dem Verwerfen noch die vollen 50 Plätze hergeben
* Add: **Fortschrittsbalken in der Importmaske.** Der Lauf wird in 21 Schritte
  zerlegt — zwanzig Listen und das Schreiben der Dateien —, die einzeln über
  die Route abgerufen werden. Angezeigt werden der laufende Schritt, ein
  Balken, die verstrichene Zeit und je Schritt eine Zeile mit Anzahl, Quelle
  und verworfenen Spielern. Bricht ein Schritt ab, hält der Lauf an und die
  Meldung steht rot im Protokoll
* Change: Die Route beantwortet jetzt JSON statt HTML und kennt den Parameter
  `schritt` (`0`…`19`, `dateien`, oder gar nichts für einen Lauf am Stück).
  Der Console-Befehl ist unverändert
* Change: Zwischen den Schritten liegen Bruchstücke unter
  `var/topwertungszahlen/`. Sie werden zu Beginn eines Laufs und nach dem
  Schreiben der Ausgabedateien gelöscht

## Version 1.7.0 (2026-09-01)

* Change: **Die Ranglisten kommen aus dem Wertungsportal statt aus DeWIS.**
  Die Beschaffung liegt vollständig in `Helper\Ranglisten` des
  `contao-wertungsportal-bundle` (ab dessen Version 1.34.0), das damit als
  Abhängigkeit dazukommt. Weggefallen sind der SOAP-Aufbau, `bestOfFederation()`,
  `searchByName()` und vor allem der Einzelabruf `tournamentCardForId()` je
  Spieler — der lief bisher **zweimal** pro Spieler, einmal für die Nation und
  einmal für den Landesverband
* Change: **`Rangliste.php` gibt es nicht mehr.** Die Logik steht jetzt in
  `Classes/Ranglistenlauf.php`, angestoßen über die Backend-Route
  `/contao/topwertungszahlen/ranglistenlauf` oder über den neuen Befehl
  `topwertungszahlen:rangliste`. Grund: Die alte Datei fuhr Contao über
  `system/initialize.php` selbst hoch — die Datei gibt es in Contao 5 nicht
  mehr — und lag ohne jede Zugangsprüfung im Web-Verzeichnis
* Change: **Ein Aufruf statt zwanzig.** Die Importmaske legte bisher zwanzig
  ineinander verschachtelte Ajax-Aufrufe an, weil jede einzelne Liste über
  DeWIS Minuten brauchte. Ein Lauf ist jetzt in Sekunden erledigt und braucht
  weder die Aufteilung noch die Zwischendateien `dwz-1.json` … `elo-10.json`
* Change: **Die Ausgabedateien liegen in `files/topwertungszahlen/`.** Bisher
  landeten `dwz.json` und `elo.json` in `web/bundles/contaotopwertungszahlen/`
  — einem Symlink ins vendor-Verzeichnis, aus dem sie jedes `composer install`
  entfernte. **Wer die Dateien von außen liest, muß die URL umstellen** auf
  `/files/topwertungszahlen/dwz.json` bzw. `elo.json`
* Add: Feld `pkz` in `tl_topwertungszahlen` (Personenkennziffer des
  Wertungsportals). Es ist das neue führende Merkmal des Spielers; `dewis_id`
  bleibt als Verlaufsangabe stehen und ist kein Pflichtfeld mehr.
  **`contao:migrate` ist erforderlich**
* Add: Vorhandene Spieler werden beim ersten Lauf automatisch übernommen —
  zuerst über `dewis_id` (falls dieselbe Nummer), sonst über Nachname, Vorname
  und Geburtsjahr; die PKZ wird dabei nachgetragen. Bei Namensgleichheit wird
  bewußt nicht geraten, sondern ein neuer Datensatz angelegt. Wie der Bestand
  liegt, zeigt vorab `tools/pruefe-spielerkennung.php`
* Change: `rank` in `tl_topwertungszahlen_ratings` führt jetzt die **geteilte
  Platzziffer** statt einer laufenden Nummer — denselben Wert also, den die
  Anzeige schon immer zeigte. Ältere Einträge behalten ihre laufende Nummer
* Change: jQuery wird nicht mehr eingebunden; `jquery-3.5.1.min.js` und
  `ajax-loader.gif` sind aus dem Paket entfernt. Die Importmaske kommt mit
  `fetch()` aus
* Change: Mindestanforderung ist PHP 7.4 (bisher 5.6) und Contao 4.13 (bisher
  4.0)
* Fix: Der Link zum FIDE-Profil in `elo.json` benutzt jetzt `https`

## Version 1.6.3 (2026-07-29)

* Fix: Warning: Undefined array key "deleteConfirm", "importRating_confirm" bei contao:migrate -> Lesezugriffe auf $GLOBALS['TL_LANG'] in den DCA-Dateien mit `?? null` bzw. `?? array()` abgesichert, da der DcaLoader die Sprachdateien noch nicht geladen hat
* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 1.6.2 (2025-11-17)

* Fix: Warning: Undefined array key "importRating_confirm" in src/Resources/contao/dca/tl_topwertungszahlen.php (line 76) -> Sprachvariable ausgelagert in default.php

## Version 1.6.1 (2025-10-01)

* Fix: Warning: Undefined array key "associations" in src/Resources/contao/dca/tl_topwertungszahlen_ratings.php (line 236) 
* Fix: Warning: Undefined array key "tl_topwertungszahlenFilter" in src/Resources/contao/dca/tl_topwertungszahlen.php (line 403) 
* Fix: Warning: Undefined variable $content in src/Classes/Import.php (line 26) 
* Fix: An exception occurred while executing a query: SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'titel' at row 1 (beim Erstellen der Wertungslisten)
* Fix: Warning: Undefined variable $anzahl in src/Resources/public/Rangliste.php (line 537) 

## Version 1.6.0 (2024-04-18)

* Add: codefog/contao-haste
* Change: Haste-Toggler statt des normalen Togglers
* Add: Kompatibilität PHP 8

## Version 1.5.6 (2023-02-21)

* Change: U20 wieder von 21 auf 20 Jahre gesetzt -> war eine Fehlinterpretation

## Version 1.5.5 (2023-02-03)

* Change: U20 von 20 auf 21 Jahre gesetzt

## Version 1.5.4 (2022-06-16)

* Fix: public/Rangliste.php - elo-9.json wird nicht erstellt, deshalb Zugriffsfehler in Zeilen 116, 119 und 121

## Version 1.5.3 (2022-04-12)

* Fix: public/Rangliste.php lädt nur den Männer-FIDE-Titel, stattdessen muß der höherwertigere Titel verwendet werden -> konnte für Elo gelöst werden, für DWZ nicht

## Version 1.5.2 (2022-01-03)

* Change: public/Rangliste.php - Altersgrenze U20/U20w von 19 auf 20 geändert (Lara Schulze, Annmarie Mütsch, Jana Schneider wurden nicht mehr angezeigt)

## Version 1.5.1 (2021-08-11)

* Add: tl_topwertungszahlen.php - Ausgabe des Platzes und Sortiermöglichkeiten ergänzt

## Version 1.5.0 (2021-08-10)

* Change: public/Rangliste.php - Senioren 60+ ersetzt durch 50+, 65+ und 75+
* Add: Import.php um Optionen 7 bis 10, Elo/DWZ erweitert
* Add: tl_topwertungszahlen.php - Filter 60+ entfernt, 50+, 65+ und 75+ hinzugefügt

## Version 1.4.2 (2021-03-30)

* Fix: public/Rangliste.php - Spieler mit gleicher ID wurden in dwz.json geschrieben, so daß Spieler doppelt ausgegeben wurden

## Version 1.4.1 (2021-03-23)

* Fix: public/Rangliste.php - SOAPClient-Aufruf ersetzt durch aktuellen Aufruf aus contao-dewis-bundle

## Version 1.4.0 (2020-08-04)

* Debug-Ausgabe in Generierung JSON entfernt
* Ajax-Ausgabe gesplittet, um schneller Informationen anzuzeigen
* Rangliste.php ohne URL-Parameter generiert alle Listen auf einmal

## Version 1.3.2 (2020-08-03)

* Vorschaubilder im Backend quadratisch gemacht: 160x160 statt 120x160

## Version 1.3.1 (2020-07-16)

* Rangliste.php nun U19 statt U18, um auch noch in U18 startberechtigte 19-Jährige anzuzeigen

## Version 1.3.0 (2020-07-15)

* Abhängigkeit schachbulle/contao-helper-bundle hinzugefügt
* Datumsfunktionen verbessert mit Helper-Klasse

## Version 1.2.0 (2020-07-15)

* Ausgabe des aktuellen Fotos in der Spielerliste
* Spezialfilter angelegt um die aktuellen Top-15 einer Kategorie anzuzeigen

## Version 1.1.1 (2020-07-15)

* jQuery-Einbindung von Google auf Lokal geändert
* Ajax Request nicht cachen

## Version 1.1.0 (2020-07-15)

* BE-Liste Fotos: Quelle ergänzt
* Rangliste.php kann jetzt direkt aus dem Backend aufgerufen werden

## Version 1.0.0 (2020-07-15)

* Eingabefeld für Fotoquelle hinzugefügt

## Version 0.0.11 (2020-07-14)

* Ratingliste.php Altersgrenze von 20 auf 18 geändert

## Version 0.0.10 (2020-04-06)

* Fix: Optimierung Rangliste.php - Platzziffer ist nicht mehr der Index

## Version 0.0.9 (2020-04-03)

* Add: Metadaten aus Dateiverwaltung in Rangliste.php
* Fix: Optimierung Rangliste.php

## Version 0.0.8 (2020-04-02)

* Add: Übersetzungen Rating-Formular
* Add: Landesverbände über Rangliste.php

## Version 0.0.7 (2020-04-02)

* Ausbau des Bundles mit Rangliste.php

## Version 0.0.6 (2020-04-01)

* Ausbau des Bundles mit Fotos und Rangliste.php

## Version 0.0.5 (2020-04-01)

* Fix: Änderung Bundle-Name von dwzranglisten in topwertungszahlen

## Version 0.0.4 (2020-02-18)

* Fix: Public-Ordner

## Version 0.0.3 (2020-02-18)

* DCA im Backend

## Version 0.0.2 (2020-02-18)

* Fix wegen falscher Verlinkung in Symphony-Komponente

## Version 0.0.1 (2020-02-17)

* Initiale Version
