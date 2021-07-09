<?php
// Netcup Kundennummer
define('CUSTOMERNR',    '');
// API-Key
define('APIKEY',        '');
// API-Passwort
define('APIPASSWORD',   '');
// Domain für DDNS
define('DOMAIN',        'example.com');
// DDNS-Zone unter Domain
define('DDNS_ZONE',     'ddns');

// IPv6 noch nicht implementiert.
define('USE_IPV6',      false);
// TTL der Domain auf 300s setzen?
define('CHANGE_TTL',    true);
// Rest-URL der Netcup API
define('APIURL',        'https://ccp.netcup.net/run/webservice/servers/endpoint.php?JSON');
// Debugging im Log und dem Client anzeigen?
define("DEBUG",         true);
// Zeitstampel für DNS TXT-Eintrag und Protokoll
define("TIMESTAMP",     date("Ymd-His"));
// Basis-Ordner (Apache DocumentRoot)
define("BASE_FOLDER",   $_SERVER['DOCUMENT_ROOT']);
// Sende-Port des Clients
define("RMT_PORT",      $_SERVER['REMOTE_PORT']);
// Zeitstempel Anfrage
define("QUERY_TIME",    $_SERVER['REQUEST_TIME']);

// Ordner für Daten
define("DATA_FOLDER",   BASE_FOLDER.'/data/');
// Ordner für Protokolle
define("LOG_FOLDER",    BASE_FOLDER.'/log/');
// Protokoll für Ereignisse
define("LOG_FILE",      LOG_FOLDER.'/ddns.log');

// Pfad zu 'logger'
define("LOGGER",       '/usr/bin/logger');

// Kompletter Querystring aus URL
define("QUERYSTRING",   $_SERVER['QUERY_STRING']);

// Antwort für "Keine Änderung" nach DynDNS-Protokoll
define("RESP_NOCHG",   'nochg');
// Antwort für "Aktualisiert" nach DynDNS-Protokoll
define("RESP_OK",      'good');
// Aua!
define("RESP_OUCH",    '911');
// Auth failed
define("RESP_NOAUTH",  'badauth');
