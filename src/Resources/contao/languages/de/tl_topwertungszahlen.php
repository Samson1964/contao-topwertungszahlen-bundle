<?php

/**
 * Buttons für globale Operationen
 */
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['importRating'] = array('Wertungszahlen aktualisieren', 'Wertungszahlen laden und JSON-Dateien aktualisieren');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['importPhotos'] = array('Nur Fotos auffrischen', 'Allein die Fotos in den JSON-Dateien erneuern, ohne die Wertungszahlen neu zu laden');

/**
 * Buttons für Operationen
 */
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['new'] = array('Neuer Spieler', 'Neuen Spieler anlegen');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['edit'] = array("Wertungszahlen des Spielers %s bearbeiten", "Wertungszahlen des Spielers %s bearbeiten");
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['editHeader'] = array("Spieler %s bearbeiten", "Spieler %s bearbeiten");
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['editPhoto'] = array("Fotos des Spielers %s bearbeiten", "Fotos des Spielers %s bearbeiten");
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['copy'] = array("Spieler %s kopieren", "Spieler %s kopieren");
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['delete'] = array("Spieler %s löschen", "Spieler %s löschen");
// Ohne %s in der Beschriftung: Diese Schaltfläche erzeugt Haste selbst und
// läuft deshalb nicht über den button_callback, der den Namen einsetzt.
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['toggle'] = array("Spieler aktivieren/deaktivieren", "Spieler %s aktivieren/deaktivieren");
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['show'] = array("Spielerdetails von %s anzeigen", "Details des Spielers %s anzeigen");

/**
 * Backend-Modul: Übersetzungen im Eingabeformular
 */
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['name_legend'] = 'Personendaten';

$GLOBALS['TL_LANG']['tl_topwertungszahlen']['nachname'] = array('Nachname', 'Nachname des Spielers');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['vorname'] = array('Vorname', 'Vorname des Spielers');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['titel'] = array('Titel', 'Titel, z.B. Dr.');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['geschlecht'] = array('Geschlecht', 'Männlich, weiblich oder divers. Die alten Kürzel m und f wurden mit Version 1.7.3 einmalig umgestellt.');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['geburtstag'] = array('Geburtstag', 'Geburtstag des Spielers');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['pkz'] = array('PKZ', 'Personenkennziffer des Spielers im Wertungsportal');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['dewis_id'] = array('DeWIS-ID', 'ID des Spielers im abgeschalteten DeWIS. Wird nicht mehr gefüllt und bleibt nur als Verlaufsangabe stehen.');

$GLOBALS['TL_LANG']['tl_topwertungszahlen']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['published'] = array('Veröffentlicht', 'Spieler aktivieren oder deaktivieren');

$GLOBALS['TL_LANG']['tl_topwertungszahlen']['foto'] = array('Foto', 'Aktuelles Foto des Spielers');
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['platz'] = array('Platz', 'Platzierung in der aktuellen Rangliste');

/**
 * Filter
 */
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter'] = 'Spezialfilter';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_extended'] = 'Spezialfilter';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_alle'] = 'DWZ Top-15 / alle Spieler';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_alle'] = 'DWZ Top-15 / Frauen';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_u20']  = 'DWZ Top-15 / alle Spieler U18';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_u20']  = 'DWZ Top-15 / alle Spieler U18w';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_ab50'] = 'DWZ Top-15 / alle Spieler Ü50';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_ab50'] = 'DWZ Top-15 / alle Spieler Ü50w';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_ab65'] = 'DWZ Top-15 / alle Spieler Ü65';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_ab65'] = 'DWZ Top-15 / alle Spieler Ü65w';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_ab75'] = 'DWZ Top-15 / alle Spieler Ü75';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_ab75'] = 'DWZ Top-15 / alle Spieler Ü75w';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_alle'] = 'Elo Top-15 / alle Spieler';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_alle'] = 'Elo Top-15 / Frauen';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_u20']  = 'Elo Top-15 / alle Spieler U18';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_u20']  = 'Elo Top-15 / alle Spieler U18w';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_ab50'] = 'Elo Top-15 / alle Spieler Ü50';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_ab50'] = 'Elo Top-15 / alle Spieler Ü50w';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_ab65'] = 'Elo Top-15 / alle Spieler Ü65';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_ab65'] = 'Elo Top-15 / alle Spieler Ü65w';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_ab75'] = 'Elo Top-15 / alle Spieler Ü75';
$GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_ab75'] = 'Elo Top-15 / alle Spieler Ü75w';
