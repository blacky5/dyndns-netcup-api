<?php
// Include: Benutzer
require("./include/users.php");
// Include: Konfiguration
require("./include/config.php");
// Include: Netcup API
require("./include/api.php");
// Include: Funktionen
require("./include/functions.php");

// Debug
write_log("Connect from ".CLIENT_IP);
write_debug("Includes complete");

// HTTP-Header senden
// ----------------------------------------------------------------------------
header("Content-Type: text/plain; charset=UTF-8");
header("Expires: 0");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Debug
write_debug("Headers complete");

if ( (AUTH_PASS == "") && (AUTH_USER == "") ) {
   // Keine Authentifizierung, einfache URL: Nur IP anzeigen
   write_log("Anonymous client, no AUTH_USER/AUTH_PASS");
   echo CLIENT_IP;
   exit;
}

// Debug
if ( QUERYSTRING != "" ) {
   write_debug("Full HTTP query: ".QUERYSTRING);
}
if ( DATA_COMPLETE ) {
   // Alles da
   write_debug("Required data complete");
} else {
   // Es Fehlt etwas
   write_debug("Required data missing: ".implode(", ", $MISSING_DATA));
}

// Authentifizierung
// ----------------------------------------------------------------------------
if ( !authenticate(AUTH_USER, AUTH_PASS, $known_clients) ) {
   // Benutzer/Passwort ungültig
   define("IS_AUTH", false);
   // Respond und exit
   echo RESP_NOAUTH;
   exit;
} else {
   // Benutzer/Passwort gültig
   define("IS_AUTH", true);

   if ( is_ipv4(CLIENT_IP) ) {
      // IPv4 Client
      write_debug("Client address is IPv4 (".CLIENT_IP.")");
      define("DNS_RTYPE", "A");
   } else {
      if ( is_ipv6(CLIENT_IP) ) {
         // IPv6 Client
         write_debug("Client address is IPv6 (".CLIENT_IP.")");
         define("DNS_RTYPE", "AAAA");
      } else {
         // Keine IPv4/IPv6 Adresse.
         write_log("Client address '".CLIENT_IP."' is not a IPv4/IPv6 pattern");
         // 911
         exit (RESP_OUCH);
      }
   }
   // Inhalt und Zeitstempel der Datei mit letzter/aktueller IP
   if ( is_readable(DATA_FOLDER."/".CLIENT_NAME.".ip") ) {
      // Datei vorhanden und lesbar
      define("CLIENT_IP_LAST", read_data(CLIENT_NAME));
      define("CLIENT_IP_UPDATETIME", timestamp_to_readable(filemtime( DATA_FOLDER."/".CLIENT_NAME.".ip" )));
      write_debug("Data file '".CLIENT_NAME.".ip': Address '".CLIENT_IP_LAST."', date '".CLIENT_IP_UPDATETIME."'");
   } else {
      // Datei nicht vorhanden oder nicht lesbar
      define("CLIENT_IP_LAST", "");
      define("CLIENT_IP_UPDATETIME", "00000000-000000");
      write_log("Data file '".CLIENT_NAME.".ip' not found or not readable");
   }

   // Zeitstempel des Kontakts loggen
   write_contact_timestamp(CLIENT_NAME);

   if ( CLIENT_IP_LAST == CLIENT_IP ) {
      // IP nicht geändert
      write_log("IP unchanged for '".CLIENT_NAME."' since ".CLIENT_IP_UPDATETIME);
      echo RESP_NOCHG." ".CLIENT_IP;
   } else {
      // DNS-Eintrag aktualisieren
      if ( update_dns(CLIENT_NAME, CLIENT_IP) === false ) {
         write_log("DNS update for '".CLIENT_NAME."': Error");
         // DNS-Aktualisierung fehlgeschlagen
         exit (RESP_OUCH);
      } else {
         // DNS-Aktualisierung OK
         write_log("DNS update for '".CLIENT_NAME."': OK");
      }
      // Cache-Datei Aktualisieren
      if ( write_data(CLIENT_NAME, CLIENT_IP) === false ) {
         write_log("File update for '".CLIENT_NAME."': Error");
         // Datei-Aktualisierung fehlgeschlagen
         exit (RESP_OUCH);
      } else {
         // Datei-Aktualisierung OK
         write_log("File update for '".CLIENT_NAME."': OK");
      }
      // Timestamp-Datei aktualisieren
      write_timestamp(CLIENT_NAME);

      // HTTP-Antwort
      echo RESP_OK." ".CLIENT_IP;
   }
}
