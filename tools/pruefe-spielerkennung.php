<?php

declare(strict_types=1);

/**
 * Prüfstand: Passen die Spielerkennungen von DeWIS und Wertungsportal zusammen?
 * =============================================================================
 *
 * Das Bundle führt seine Spieler in tl_topwertungszahlen über das Feld
 * `dewis_id` — die Personenkennziffer aus der abgeschalteten
 * DeWIS-SOAP-Schnittstelle. Das Wertungsportal liefert stattdessen die PKZ im
 * Feld `nuLigaPersonId` der Tabelle tl_wertungsportal_persons.
 *
 * Vor der Umstellung von Rangliste.php muß feststehen, ob das dieselbe Nummer
 * ist. Steht sie, kann `dewis_id` weiterverwendet werden und der ganze
 * bisherige Bestand samt Ranglistenverlauf und Spielerfotos bleibt zugeordnet.
 * Steht sie nicht, legt der erste Lauf für jeden Spieler einen zweiten
 * Datensatz an — der Verlauf reißt ab, und die Fotos hängen am alten Satz.
 *
 * Die Datei greift NUR LESEND zu. Sie ändert nichts und legt nichts an.
 *
 * Aufruf
 * ------
 * Die Datei in das Wurzelverzeichnis der Contao-Installation legen (dorthin,
 * wo auch `composer.json` und `vendor/` liegen) und entweder
 *
 *   php pruefe-spielerkennung.php
 *
 * auf der Kommandozeile ausführen, oder sie in das Web-Verzeichnis legen und
 * im Browser aufrufen. Danach wieder entfernen.
 *
 * Contao selbst wird bewußt NICHT hochgefahren: `system/initialize.php` gibt
 * es in Contao 5 nicht mehr, und ein Kernel-Start bräuchte einen gültigen
 * Cache. Die Zugangsdaten kommen deshalb direkt aus `.env` beziehungsweise
 * `app/config/parameters.yml`, die Verbindung über PDO. So läuft die Prüfung
 * unter Contao 4.13 und 5.x gleichermaßen.
 */

// Anzahl der Beispielzeilen je Abschnitt
const BEISPIELE = 15;

$cli = (PHP_SAPI === 'cli');

if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

/**
 * Gibt eine Zeile aus.
 *
 * Kapselt nur den Zeilenumbruch, damit die Ausgabe auf der Kommandozeile und
 * im Browser gleich aussieht (im Browser sorgt der text/plain-Kopf dafür).
 *
 * @param string $text Auszugebender Text, ohne Zeilenumbruch am Ende
 */
function zeile(string $text = ''): void
{
    echo $text, "\n";
}

/**
 * Gibt eine Abschnittsüberschrift mit Unterstreichung aus.
 *
 * @param string $text Überschrift
 */
function abschnitt(string $text): void
{
    zeile();
    zeile($text);
    zeile(str_repeat('=', mb_strlen($text)));
}

/**
 * Bricht die Prüfung mit einer Meldung ab.
 *
 * Wird bei allen Voraussetzungen benutzt, die sich nicht umgehen lassen —
 * fehlende Zugangsdaten, fehlende Tabellen. Der Rückgabewert an die Umgebung
 * ist 1, damit ein Aufruf im Skript den Fehlschlag bemerkt.
 *
 * @param string $meldung Grund des Abbruchs, in ganzen Sätzen
 */
function abbruch(string $meldung): void
{
    zeile();
    zeile('ABBRUCH: '.$meldung);
    exit(1);
}

/**
 * Sucht das Wurzelverzeichnis der Contao-Installation.
 *
 * Ausgangspunkt ist das Verzeichnis dieser Datei. Von dort wird aufwärts
 * gesucht, bis ein Verzeichnis gefunden ist, das sowohl `vendor/` als auch
 * `composer.json` enthält. Damit funktioniert der Aufruf auch dann, wenn die
 * Datei im Web-Verzeichnis (`web/`, `public/`) oder in einem Unterordner
 * liegt.
 *
 * @return string|null Absoluter Pfad ohne abschließenden Trenner, oder null,
 *                     wenn oberhalb kein passendes Verzeichnis liegt
 */
function projektverzeichnis(): ?string
{
    $verzeichnis = __DIR__;

    for ($i = 0; $i < 6; ++$i) {
        if (is_dir($verzeichnis.'/vendor') && is_file($verzeichnis.'/composer.json')) {
            return $verzeichnis;
        }

        $eltern = \dirname($verzeichnis);

        if ($eltern === $verzeichnis) {
            break;
        }

        $verzeichnis = $eltern;
    }

    return null;
}

/**
 * Liest die Datenbank-Zugangsdaten aus der Installation.
 *
 * Contao 4.13 kennt zwei Schreibweisen nebeneinander: die einzelnen Parameter
 * in `app/config/parameters.yml` und die zusammengesetzte `DATABASE_URL` in
 * `.env` beziehungsweise `.env.local`. Contao 5 kennt nur noch die zweite.
 * Geprüft werden deshalb alle Quellen, die spätere überschreibt die frühere —
 * `.env.local` gewinnt, wie es Symfony auch handhabt.
 *
 * Ein eigener YAML-Leser statt `yaml_parse` oder der Symfony-Komponente: Die
 * Erweiterung ist selten installiert, und die Komponente ließe sich nur mit
 * dem Autoloader laden, den diese Datei bewußt nicht anfaßt. Für die vier
 * gesuchten Zeilen reicht ein Ausdruck.
 *
 * @param string $wurzel Wurzelverzeichnis der Installation
 *
 * @return array<string,string|int>|null Schlüssel host, port, name, user,
 *                                       pass — oder null, wenn sich nichts
 *                                       finden ließ
 */
function zugangsdaten(string $wurzel): ?array
{
    $daten = [];

    // 1. parameters.yml (Contao 4.13)
    $yml = $wurzel.'/app/config/parameters.yml';

    if (is_file($yml)) {
        $inhalt = (string) file_get_contents($yml);

        $felder = [
            'host' => 'database_host',
            'port' => 'database_port',
            'name' => 'database_name',
            'user' => 'database_user',
            'pass' => 'database_password',
        ];

        foreach ($felder as $schluessel => $parameter) {
            // Werte dürfen roh, in einfachen oder in doppelten Anführungszeichen stehen
            if (preg_match('/^\s*'.$parameter.'\s*:\s*(.*?)\s*$/m', $inhalt, $treffer)) {
                $wert = trim($treffer[1], "'\" \t");

                if ('' !== $wert && 'null' !== $wert && '~' !== $wert) {
                    $daten[$schluessel] = $wert;
                }
            }
        }
    }

    // 2. .env und .env.local (Contao 4.13 optional, Contao 5 verbindlich)
    foreach (['/.env', '/.env.local'] as $datei) {
        $pfad = $wurzel.$datei;

        if (!is_file($pfad)) {
            continue;
        }

        $inhalt = (string) file_get_contents($pfad);

        if (!preg_match('/^\s*DATABASE_URL\s*=\s*(.*?)\s*$/m', $inhalt, $treffer)) {
            continue;
        }

        $url = trim($treffer[1], "'\" \t");
        $teile = parse_url($url);

        if (!\is_array($teile) || !isset($teile['host'])) {
            continue;
        }

        $daten['host'] = $teile['host'];
        $daten['port'] = $teile['port'] ?? ($daten['port'] ?? 3306);
        $daten['user'] = isset($teile['user']) ? rawurldecode($teile['user']) : '';
        $daten['pass'] = isset($teile['pass']) ? rawurldecode($teile['pass']) : '';
        $daten['name'] = isset($teile['path']) ? rawurldecode(ltrim($teile['path'], '/')) : '';

        // Der Datenbankname kann eine Serverversion angehängt haben:
        // ?serverVersion=… wurde von parse_url schon abgetrennt, ein
        // nachlaufender Schrägstrich nicht
        $daten['name'] = rtrim($daten['name'], '/');
    }

    if (empty($daten['name'])) {
        return null;
    }

    return [
        'host' => (string) ($daten['host'] ?? 'localhost'),
        'port' => (int) ($daten['port'] ?? 3306),
        'name' => (string) $daten['name'],
        'user' => (string) ($daten['user'] ?? ''),
        'pass' => (string) ($daten['pass'] ?? ''),
    ];
}

/**
 * Meldet, ob eine Tabelle in der verbundenen Datenbank vorhanden ist.
 *
 * @param PDO    $pdo     Offene Verbindung
 * @param string $tabelle Tabellenname ohne Anführungszeichen
 *
 * @return bool true, wenn die Tabelle existiert
 */
function tabelleDa(PDO $pdo, string $tabelle): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$tabelle]);

    return false !== $stmt->fetchColumn();
}

/**
 * Formt einen Namen für den Vergleich um.
 *
 * Groß-/Kleinschreibung, Umlaute und Bindestriche dürfen die Zuordnung nicht
 * verhindern: Die FIDE schreibt „Mueller", das Wertungsportal „Müller", und in
 * alten Beständen steht beides. Zurück kommt eine Form aus ausschließlich
 * Kleinbuchstaben a–z.
 *
 * @param string $name Nachname oder Vorname, roh aus der Datenbank
 *
 * @return string Vergleichsform, leer wenn nichts Verwertbares übrig bleibt
 */
function namensform(string $name): string
{
    $name = mb_strtolower(trim($name));

    $ersetzungen = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'å' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'š' => 's', 'ž' => 'z', 'č' => 'c', 'ř' => 'r',
    ];

    $name = strtr($name, $ersetzungen);

    return (string) preg_replace('/[^a-z]/', '', $name);
}

/**
 * Ermittelt das Geburtsjahr aus dem Feld `geburtstag`.
 *
 * Contao speichert Datumsfelder als Unix-Zeitstempel in einem varchar. Der
 * Wert kann leer sein — das Feld ist im Bundle nicht verpflichtend und in den
 * automatisch angelegten Sätzen nie gefüllt.
 *
 * @param string $wert Rohwert aus tl_topwertungszahlen.geburtstag
 *
 * @return int Jahreszahl, oder 0 wenn nicht bestimmbar
 */
function geburtsjahr(string $wert): int
{
    $wert = trim($wert);

    if ('' === $wert || !is_numeric($wert)) {
        return 0;
    }

    $jahr = (int) date('Y', (int) $wert);

    // Zeitstempel 0 (1970) ist in der Praxis „nicht gesetzt" und kein Jahrgang
    return $jahr > 1900 && $jahr < 2100 && '0' !== $wert ? $jahr : 0;
}

/**
 * Zieht das Jahr aus dem Feld `birthyear` des Wertungsportals.
 *
 * Das Feld führt entweder das Jahr (JJJJ) oder ein volles Datum (TT.MM.JJJJ).
 * In beiden Fällen stehen die letzten vier Zeichen für das Jahr — dieselbe
 * Regel wie in Helper\Lokal des Wertungsportal-Bundles.
 *
 * @param string $wert Rohwert aus tl_wertungsportal_persons.birthyear
 *
 * @return int Jahreszahl, oder 0 wenn nicht bestimmbar
 */
function birthyear(string $wert): int
{
    $jahr = (int) substr(trim($wert), -4);

    return $jahr > 1900 && $jahr < 2100 ? $jahr : 0;
}

// ---------------------------------------------------------------------------
// Ab hier läuft die Prüfung
// ---------------------------------------------------------------------------

zeile('Prüfstand Spielerkennung: dewis_id gegen nuLigaPersonId');
zeile('Erzeugt am '.date('d.m.Y H:i:s'));
zeile(str_repeat('-', 72));

$wurzel = projektverzeichnis();

if (null === $wurzel) {
    abbruch(
        'Oberhalb von '.__DIR__.' liegt kein Verzeichnis mit vendor/ und composer.json. '
        ."\n         Bitte die Datei in das Wurzelverzeichnis der Contao-Installation legen."
    );
}

zeile('Installation: '.$wurzel);

$zugang = zugangsdaten($wurzel);

if (null === $zugang) {
    abbruch(
        'Keine Zugangsdaten gefunden. Erwartet wurde eine DATABASE_URL in .env bzw. '
        ."\n         .env.local oder die database_*-Parameter in app/config/parameters.yml."
    );
}

zeile('Datenbank:    '.$zugang['name'].' auf '.$zugang['host'].':'.$zugang['port']);

try {
    $pdo = new PDO(
        'mysql:host='.$zugang['host'].';port='.$zugang['port'].';dbname='.$zugang['name'].';charset=utf8mb4',
        $zugang['user'],
        $zugang['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    abbruch('Verbindung fehlgeschlagen: '.$e->getMessage());
}

foreach (['tl_topwertungszahlen', 'tl_wertungsportal_persons'] as $tabelle) {
    if (!tabelleDa($pdo, $tabelle)) {
        abbruch(
            'Die Tabelle '.$tabelle.' fehlt. '
            ."\n         Beide Bundles müssen installiert und contao:migrate gelaufen sein."
        );
    }
}

// --- Bestand ---------------------------------------------------------------

abschnitt('1. Bestand');

$gesamt = (int) $pdo->query('SELECT COUNT(*) FROM tl_topwertungszahlen')->fetchColumn();
$mitId = (int) $pdo->query('SELECT COUNT(*) FROM tl_topwertungszahlen WHERE dewis_id > 0')->fetchColumn();
$personen = (int) $pdo->query('SELECT COUNT(*) FROM tl_wertungsportal_persons')->fetchColumn();

zeile('tl_topwertungszahlen           '.$gesamt.' Spieler, davon '.$mitId.' mit dewis_id');
zeile('tl_wertungsportal_persons      '.$personen.' Personen');

if (0 === $mitId) {
    abbruch('Kein einziger Spieler trägt eine dewis_id — es gibt nichts zu vergleichen.');
}

if (0 === $personen) {
    abbruch(
        'tl_wertungsportal_persons ist leer. Erst den Personen-Import des '
        ."\n         Wertungsportal-Bundles laufen lassen, sonst prüft die Datei ins Nichts."
    );
}

// --- Treffer über die Nummer ------------------------------------------------

abschnitt('2. Wieviele dewis_id finden sich als nuLigaPersonId wieder?');

$sql = "SELECT t.id, t.dewis_id, t.nachname, t.vorname, t.geburtstag,
               p.nuLigaPersonId, p.lastname, p.firstname, p.birthyear
        FROM tl_topwertungszahlen t
        LEFT JOIN tl_wertungsportal_persons p ON p.nuLigaPersonId = CAST(t.dewis_id AS CHAR)
        WHERE t.dewis_id > 0
        ORDER BY t.nachname, t.vorname";

$zeilen = $pdo->query($sql)->fetchAll();

$treffer = [];
$ohneTreffer = [];
$namensgleich = 0;
$namensverschieden = [];

foreach ($zeilen as $satz) {
    if (null === $satz['nuLigaPersonId']) {
        $ohneTreffer[] = $satz;

        continue;
    }

    $treffer[] = $satz;

    // Die Nummer allein beweist nichts: Stimmt sie zufällig überein, gehört
    // sie trotzdem zu einer anderen Person. Erst der Name entscheidet
    if (namensform((string) $satz['nachname']) === namensform((string) $satz['lastname'])) {
        ++$namensgleich;
    } else {
        $namensverschieden[] = $satz;
    }
}

$anzahlTreffer = \count($treffer);
$quote = $mitId > 0 ? round(100 * $anzahlTreffer / $mitId, 1) : 0.0;

zeile('Nummer gefunden:      '.$anzahlTreffer.' von '.$mitId.' ('.$quote.' %)');
zeile('Nummer nicht gefunden: '.\count($ohneTreffer));

if ($anzahlTreffer > 0) {
    $namensquote = round(100 * $namensgleich / $anzahlTreffer, 1);
    zeile();
    zeile('Davon mit GLEICHEM Nachnamen: '.$namensgleich.' ('.$namensquote.' %)');
    zeile('Davon mit anderem Nachnamen:  '.\count($namensverschieden));
    zeile();
    zeile('Der zweite Wert ist der entscheidende. Eine übereinstimmende Nummer bei');
    zeile('verschiedenem Namen heißt: Die Nummernkreise überschneiden sich zufällig.');
}

// --- Beispiele --------------------------------------------------------------

if ($anzahlTreffer > 0) {
    abschnitt('3. Beispiele für Treffer');

    zeile(sprintf('%-12s %-24s %-24s %-6s %-6s', 'Nummer', 'hier', 'Wertungsportal', 'Jahr', 'Jahr'));
    zeile(str_repeat('-', 78));

    foreach (\array_slice($treffer, 0, BEISPIELE) as $satz) {
        zeile(sprintf(
            '%-12s %-24s %-24s %-6s %-6s',
            $satz['dewis_id'],
            mb_substr($satz['nachname'].', '.$satz['vorname'], 0, 24),
            mb_substr($satz['lastname'].', '.$satz['firstname'], 0, 24),
            geburtsjahr((string) $satz['geburtstag']) ?: '-',
            birthyear((string) $satz['birthyear']) ?: '-'
        ));
    }
}

if (\count($namensverschieden) > 0) {
    abschnitt('4. Gleiche Nummer, ANDERER Name — das wären falsche Zuordnungen');

    zeile(sprintf('%-12s %-24s %-24s', 'Nummer', 'hier', 'Wertungsportal'));
    zeile(str_repeat('-', 64));

    foreach (\array_slice($namensverschieden, 0, BEISPIELE) as $satz) {
        zeile(sprintf(
            '%-12s %-24s %-24s',
            $satz['dewis_id'],
            mb_substr($satz['nachname'].', '.$satz['vorname'], 0, 24),
            mb_substr($satz['lastname'].', '.$satz['firstname'], 0, 24)
        ));
    }
}

// --- Gegenprobe über den Namen ---------------------------------------------

abschnitt('5. Gegenprobe: Welche PKZ hätte der Spieler wirklich?');

zeile('Für die Sätze ohne Nummerntreffer wird die Person über Nachname und');
zeile('Vorname gesucht. Steht daneben eine ganz andere PKZ, sind es zwei');
zeile('verschiedene Nummernkreise — dann braucht tl_topwertungszahlen ein');
zeile('eigenes Feld für die PKZ und eine einmalige Zuordnung.');
zeile();

if (0 === \count($ohneTreffer)) {
    zeile('Entfällt: Jede dewis_id wurde als nuLigaPersonId gefunden.');
} else {
    // Alle in Frage kommenden Personen einmal holen und in PHP zuordnen. Eine
    // Einzelabfrage je Spieler wäre bei einigen hundert Sätzen zwar auch
    // machbar, aber die Namensform läßt sich in SQL nicht bilden
    $namensliste = [];
    $stmt = $pdo->query(
        "SELECT nuLigaPersonId, lastname, firstname, birthyear
         FROM tl_wertungsportal_persons
         WHERE published = '1'"
    );

    foreach ($stmt as $person) {
        $schluessel = namensform((string) $person['lastname']).'|'.namensform((string) $person['firstname']);

        // Mehrdeutige Namen werden gesammelt, nicht überschrieben
        $namensliste[$schluessel][] = $person;
    }

    $eindeutig = 0;
    $mehrdeutig = 0;
    $unbekannt = 0;
    $beispiele = [];

    foreach ($ohneTreffer as $satz) {
        $schluessel = namensform((string) $satz['nachname']).'|'.namensform((string) $satz['vorname']);
        $kandidaten = $namensliste[$schluessel] ?? [];

        if (0 === \count($kandidaten)) {
            ++$unbekannt;

            continue;
        }

        if (\count($kandidaten) > 1) {
            ++$mehrdeutig;

            continue;
        }

        ++$eindeutig;

        if (\count($beispiele) < BEISPIELE) {
            $beispiele[] = [$satz, $kandidaten[0]];
        }
    }

    zeile('Ohne Nummerntreffer:            '.\count($ohneTreffer));
    zeile('  davon über den Namen eindeutig: '.$eindeutig);
    zeile('  davon mehrdeutig (Namensvetter): '.$mehrdeutig);
    zeile('  davon gar nicht gefunden:        '.$unbekannt);

    if (\count($beispiele) > 0) {
        zeile();
        zeile(sprintf('%-26s %-14s %-14s', 'Spieler', 'dewis_id', 'PKZ'));
        zeile(str_repeat('-', 58));

        foreach ($beispiele as [$satz, $person]) {
            zeile(sprintf(
                '%-26s %-14s %-14s',
                mb_substr($satz['nachname'].', '.$satz['vorname'], 0, 26),
                $satz['dewis_id'],
                $person['nuLigaPersonId']
            ));
        }
    }
}

// --- Befund -----------------------------------------------------------------

abschnitt('6. Befund');

if ($anzahlTreffer > 0 && $namensgleich === $anzahlTreffer && $quote >= 90.0) {
    zeile('DIESELBE NUMMER. Jede gefundene Nummer gehört auch zur selben Person.');
    zeile('Das Feld dewis_id kann weiterverwendet werden; die PKZ wird dort');
    zeile('hineingeschrieben. Der bisherige Bestand samt Ranglistenverlauf und');
    zeile('Spielerfotos bleibt zugeordnet.');
    zeile();
    zeile('Empfehlung: Feld umbenennen (dewis_id -> pkz) ist trotzdem sinnvoll,');
    zeile('aber eine Frage der Lesbarkeit, nicht der Zuordnung.');
} elseif ($anzahlTreffer > 0 && \count($namensverschieden) > 0) {
    zeile('VORSICHT. Die Nummern treffen sich, aber nicht immer bei derselben');
    zeile('Person ('.\count($namensverschieden).' Fälle in Abschnitt 4). Das sind zwei verschiedene');
    zeile('Nummernkreise, die sich zufällig überschneiden — der schlechteste Fall,');
    zeile('weil eine einfache Übernahme fehlerfrei aussieht und trotzdem falsch ist.');
    zeile();
    zeile('Empfehlung: eigenes Feld pkz in tl_topwertungszahlen, einmalige');
    zeile('Zuordnung über Nachname, Vorname und Geburtsjahr.');
} else {
    zeile('VERSCHIEDENE NUMMERN. Die dewis_id findet sich nicht als PKZ wieder.');
    zeile();
    zeile('Empfehlung: eigenes Feld pkz in tl_topwertungszahlen, einmalige');
    zeile('Zuordnung über Nachname, Vorname und Geburtsjahr. dewis_id bleibt als');
    zeile('Verlaufsangabe stehen. Abschnitt 5 nennt die Größenordnung: Was dort');
    zeile('unter "mehrdeutig" und "gar nicht gefunden" steht, muß von Hand');
    zeile('nachgezogen werden.');
}

zeile();
zeile(str_repeat('-', 72));
zeile('Ende. Diese Datei hat nichts geändert und kann gelöscht werden.');
