# Topwertungszahlen Changelog

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
