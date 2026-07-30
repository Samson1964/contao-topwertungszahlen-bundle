# Topwertungszahlen Changelog

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
