<?php
// IP-Adresse (aus GET-Request or Client-IP)
// ----------------------------------------------------------------------------
if ( filter_input(INPUT_GET, 'myip', FILTER_SANITIZE_SPECIAL_CHARS) ) {
   // IP aus GET-Request (myqip) nach DynDNS-Protokoll verwenden
   define("CLIENT_IP", filter_input(INPUT_GET, 'myip', FILTER_SANITIZE_SPECIAL_CHARS));
} else {
   // IP des HTTP-Clients verwenden
   define("CLIENT_IP", $_SERVER['REMOTE_ADDR']);
}

// Benutzer und Hostname (aus "Basic Authentication", aus GET oder nichts)
// ----------------------------------------------------------------------------
if ( isset($_SERVER['PHP_AUTH_USER']) ) {
   // Benutzer aus HTTP basic auth
   define("AUTH_USER",   $_SERVER['PHP_AUTH_USER']);
   // Hostname aus GET-Request (hostname) probieren
   $try_client = filter_input(INPUT_GET, 'hostname', FILTER_SANITIZE_SPECIAL_CHARS);
   $try_client = str_replace(".".DDNS_ZONE, "", $try_client);
   if ( $try_client != "") {
      // GET-Request "hostname" nicht leer, diesen verwenden
      define("CLIENT_NAME", $try_client);
   } else {
      // GET-Request "hostname" ist leer, "name" probieren
      $try_client = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
      $try_client = str_replace(".".DDNS_ZONE, "", $try_client);
      if ( $try_client != "" ) {
         // GET-Request "name" nicht leer, diesen verwenden
         define("CLIENT_NAME", $try_client);
      } else {
         // GET-Request "name" auch leer: Hostname = Benutzername setzen
         define("CLIENT_NAME", AUTH_USER);
      }
   }
} else {
   if ( isset($_GET['name']) ) {
      // Benutzer aus GET "name"
      define("AUTH_USER",   filter_input(INPUT_GET, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
      // Hostname aus GET "name"
      define("CLIENT_NAME", filter_input(INPUT_GET, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
   } else {
      // Kein Benutzer, kein Hostname - Ende
      define("AUTH_USER", "");
      define("CLIENT_NAME", "");
   }
}

// Passwort (aus "Basic Authentication", aus GET oder nichts)
// ----------------------------------------------------------------------------
if ( isset($_SERVER['PHP_AUTH_PW']) ) {
   // Passwort aus "Basic Authentication"
   define("AUTH_PASS", $_SERVER['PHP_AUTH_PW']);
} else {
   if (filter_input(INPUT_GET, 'pass', FILTER_SANITIZE_SPECIAL_CHARS)) {
      // Passwort aus GET-Request "pass" probieren
      define("AUTH_PASS", filter_input(INPUT_GET, 'pass', FILTER_SANITIZE_SPECIAL_CHARS));
   } else {
      // Kein Passwort - Ende
      define("AUTH_PASS", "");
   }
}

// Alles da?
// ----------------------------------------------------------------------------
if ( AUTH_USER && AUTH_PASS && CLIENT_NAME && CLIENT_IP ) {
   // Alles da: Benutzer, Passwort, IP und Hostname
   define("DATA_COMPLETE", true);
} else {
   // Mindestens eins fehlt
   define("DATA_COMPLETE", false);
   $MISSING_DATA = array();
   if ( AUTH_USER == "") {
      // Benutzer fehlt
      array_push($MISSING_DATA, "AUTH_USER");
   }
   if ( AUTH_PASS == "" ) {
      // Passwort fehlt
      array_push($MISSING_DATA, "AUTH_PASS");
   }
   if ( CLIENT_NAME == "" ) {
      // Hostname fehlt
      array_push($MISSING_DATA, "CLIENT_NAME");
   }
   if ( CLIENT_IP == "" ) {
      // IP-Adresse fehlt
      array_push($MISSING_DATA, "CLIENT_IP");
   }
}

// Authentifizierung
// ----------------------------------------------------------------------------
function authenticate($user, $pass, $userdb) {
   if ( array_key_exists($user, $userdb) ) {
      // User OK
      write_log("Auth: User '".$user."' found");
      if ( $userdb[$user] == $pass) {
         // Passwort OK
         write_debug("Auth OK for user '".$user."'");
         return true;
      } else {
         // Passwort falsch
         write_debug("Auth failed for user '".$user."'");
         return false;
      }
   } else {
      // Benutzer unbekannt
      write_log("Auth: User '".$user."' unknown or no username supplied");
      return false;
   }
}

// Gültige IPv4-Adresse
// ----------------------------------------------------------------------------
function is_ipv4($ipv4) {
   // OK ist 250-255 oder 200-249 oder 100-199 oder 1-/2-stellige Zahl
   $num = "(25[0-5]|2[0-4]\d|[01]?\d\d|\d)";
   if (preg_match("/^($num)\\.($num)\\.($num)\\.($num)$/", $ipv4)) {
      return true;
   } else {
      return false;
   }
}

// Gültige IPv6-Adresse
// ----------------------------------------------------------------------------
function is_ipv6($ipv6) {
   // Möglichkeiten
   $pattern1 = "([A-Fa-f0-9]{0,4}:){7}[A-Fa-f0-9]{0,4}";
   $pattern2 = "[A-Fa-f0-9]{0,4}::([A-Fa-f0-9]{0,4}:){0,5}[A-Fa-f0-9]{0,4}";
   $pattern3 = "([A-Fa-f0-9]{0,4}:){2}:([A-Fa-f0-9]{0,4}:){0,4}[A-Fa-f0-9]{0,4}";
   $pattern4 = "([A-Fa-f0-9]{0,4}:){3}:([A-Fa-f0-9]{0,4}:){0,3}[A-Fa-f0-9]{0,4}";
   $pattern5 = "([A-Fa-f0-9]{0,4}:){4}:([A-Fa-f0-9]{0,4}:){0,2}[A-Fa-f0-9]{0,4}";
   $pattern6 = "([A-Fa-f0-9]{0,4}:){5}:([A-Fa-f0-9]{0,4}:){0,1}[A-Fa-f0-9]{0,4}";
   $pattern7 = "([A-Fa-f0-9]{0,4}:){6}:[A-Fa-f0-9]{0,4}";
   if (preg_match("/^($pattern1)$|^($pattern2)$|^($pattern3)$|^($pattern4)$|^($pattern5)$|^($pattern6)$|^($pattern7)$/", $ipv6)) {
      return true;
   } else {
      return false;
   }
}

// IP in Daten-Datei schreiben
// ----------------------------------------------------------------------------
function write_data($file, $string) {
   if ( ($file == "") || ($string == "") ) {
      // Kein Dateiname oder Inhalt zum Schreiben
      write_log("Write file: Filename/content missing");
      return false;
   } else {
      // Hostname bei falschem Login korrigieren ("$HOST.DDNS_ZONE" -> "$HOST")
      $file = str_replace(".".DDNS_ZONE, "", $file);

      if ( is_writable(DATA_FOLDER) ) {
         // Ordner beschreibbar
         $write_result = file_put_contents( DATA_FOLDER."/".$file.".ip", $string."\n" );
         if ( $write_result === false ) {
            // Schreiben fehlgeschlagen
            write_log("Write file '".$file.".ip' failed");
            return false;
         } else {
            // Schreiben OK, N Bytes geschrieben
            write_debug("Write file '".$file.".ip' OK");
            return true;
         }
      } else {
         // Ordner nicht beschreibbar
         write_log("Write: Folder '".DATA_FOLDER."' not writable");
         return false;
      }
   }
}

// Timestamp in Daten-Datei schreiben
// ----------------------------------------------------------------------------
function write_timestamp($file) {
   if  ($file == "") {
      // Kein Dateiname oder Inhalt zum Schreiben
      write_log("Write timestamp file: Filename missing");
      return false;
   } else {
      if ( is_writable(DATA_FOLDER) ) {
         // Ordner beschreibbar
         $write_result = file_put_contents( DATA_FOLDER."/".$file.".timestamp", time()."\n" );
         if ( $write_result === false ) {
            // Schreiben fehlgeschlagen
            write_log("Write timestamp file '".$file.".timestamp' failed");
            return false;
         } else {
            // Schreiben OK, N Bytes geschrieben
            write_debug("Write timestamp file '".$file.".timestamp' OK");
            return true;
         }
      } else {
         // Ordner nicht beschreibbar
         write_log("Write: Folder '".DATA_FOLDER."' not writable");
         return false;
      }
   }
}

// Kontakt-Zeit in Daten-Datei schreiben
// ----------------------------------------------------------------------------
function write_contact_timestamp($file) {
   if  ($file == "") {
      // Kein Dateiname oder Inhalt zum Schreiben
      write_log("Contact timestamp file: Filename missing");
      return false;
   } else {
      if ( is_writable(DATA_FOLDER) ) {
         // Ordner beschreibbar
         $write_result = file_put_contents( DATA_FOLDER."/".$file.".contact", time()."\n" );
         if ( $write_result === false ) {
            // Schreiben fehlgeschlagen
            write_log("Contact timestamp file '".$file.".contact': Write failed");
            return false;
         } else {
            // Schreiben OK, N Bytes geschrieben
            write_debug("Contact timestamp file '".$file.".contact': Write OK");
            return true;
         }
      } else {
         // Ordner nicht beschreibbar
         write_log("Contact timestamp file: Write to folder '".DATA_FOLDER."' failed");
         return false;
      }
   }
}

// IP aus Daten-Datei lesen
// ----------------------------------------------------------------------------
function read_data($file) {
   if ( $file == "" ) {
      // Kein Dateiname angegeben
      write_log("Data file: Filename missing");
      return false;
   } else {
      if ( is_readable(DATA_FOLDER."/".$file.".ip") ) {
         // Inhalt lesen
         $read_result = file_get_contents( DATA_FOLDER."/".$file.".ip" );
         write_debug("Data file '".$file.".ip': Read OK");
         return trim(str_replace("\n","", $read_result));
      } else {
         // Ordner nicht lesbar/vorhanden
         write_log("Data file: Read from folder '".DATA_FOLDER."' failed");
         return false;
      }
   }
}

// Zeitstempel
// ----------------------------------------------------------------------------
function simple_timestamp() {
   // 2015-03-04_05:53:20
   $my_timestamp = date("Y-m-d_H:i:s_T");
   return $my_timestamp;
}

// Protokollierung (Ereignisse)
// ----------------------------------------------------------------------------
function write_log($string) {
   $write_result = file_put_contents(LOG_FILE, TIMESTAMP." [LOG] ".$string."\n", FILE_APPEND);
   if ( $write_result === false ) {
      // Schreiben fehlgeschlagen
      return "Error";
   } else {
      // Schreiben OK, N Bytes geschrieben
      return "OK, ".$write_result." bytes written";
   }
}

// Protokollierung (Debugging)
// ----------------------------------------------------------------------------
function write_debug($string) {
   if ( DEBUG === true ) {
      // Wenn Debugging aktiv
      $write_result = file_put_contents(LOG_FILE, TIMESTAMP." [DBG] ".$string."\n", FILE_APPEND);
      if ( $write_result === false ) {
         // Schreiben fehlgeschlagen
         return "Error";
      } else {
         // Schreiben OK, N Bytes geschrieben
         return "OK, ".$write_result." bytes written";
      }
   }
}

// DNS-Aktualisierung
// ----------------------------------------------------------------------------
function update_dns($host, $ip) {
   if ( ( $host == "" ) || ( $ip == "" ) ) {
      // Hostname oder IP fehlt
      write_debug("API: Hostname or IP missing");
      return false;
   } else {
      // Daten OK, DNS-Eintrag aktualisieren
      write_debug("API: Host '".$host."', IP '".$ip."'");

      // DNS-Zonnename an Hostname anhängen
      define('HOST', $host.'.'.DDNS_ZONE);

      // ---------------------------------------------------------------------------------

      // API-Login
      if ($apisessionid = login(CUSTOMERNR, APIKEY, APIPASSWORD)) {
         write_debug("API: Login successful");
      } else {
         write_debug("API: Login error");
         return false;
      }

      // Informationen der Zone abrufen
      if ($infoDnsZone = infoDnsZone(DOMAIN, CUSTOMERNR, APIKEY, $apisessionid)) {
         write_debug("API: DNS zone information retreived");
      } else {
         write_debug("API: Error, failed to get DNS zone information");
         return false;
      }

      // Serial# der Zone
      write_debug("API: Zone '".DOMAIN."' serial is '".$infoDnsZone['responsedata']['serial']."'");

      // TTL-Warnung
      if (CHANGE_TTL !== true && $infoDnsZone['responsedata']['ttl'] > 300) {
         write_debug("API: TTL for zone is longer than 300s");
      }

      // TTL erhöhen?
      if (CHANGE_TTL === true && $infoDnsZone['responsedata']['ttl'] !== "300") {
         $infoDnsZone['responsedata']['ttl'] = 300;
         if (updateDnsZone(DOMAIN, CUSTOMERNR, APIKEY, $apisessionid, $infoDnsZone['responsedata'])) {
            write_debug("API: Note, lowered TTL to 300 seconds");
         } else {
            write_debug("API: Warning, failed to lower TTL");
         }
      }

      // DNS-Einträge abrufen
      if ($infoDnsRecords = infoDnsRecords(DOMAIN, CUSTOMERNR, APIKEY, $apisessionid)) {
         write_debug("API: Retrieved DNS record data");
      } else {
         write_debug("API: Error, failed to retreive DNS record data");
         return false;
      }

      // Liste der Einträge initialisieren
      $foundHostsV4 = array();

      // Einträge der Zone durchgehen
      foreach ($infoDnsRecords['responsedata']['dnsrecords'] as $record) {
         if ($record['hostname'] === HOST && $record['type'] === "A") {
            // Host mit A-Eintrag gefunden
            $foundHostsV4[] = array(
                'id' => $record['id'],
                'hostname' => $record['hostname'],
                'type' => $record['type'],
                'priority' => $record['priority'],
                'destination' => $record['destination'],
                'deleterecord' => $record['deleterecord'],
                'state' => $record['state'],
            );
         }
      }

      // Host nicht in der Zone, erstellen
      if (count($foundHostsV4) === 0) {
         write_debug("API: A record for host '".HOST."' does not exist, creating DNS record");
         $foundHostsV4[] = array(
            'hostname' => HOST,
            'type' => 'A',
            'destination' => 'newly created Record',
         );
      }

      // Mehr als ein A-Eintrag zum Host vorhanden
      if (count($foundHostsV4) > 1) {
         write_debug("API: Error, more than one A record found for host '".HOST."'");
         return false;
      }

      // IP-Adresse aus Environment übernehmen
      $publicIPv4 = $ip;

      // Flag zurücksetzen
      $ipv4change = false;

      // Hat sich die IP geändert?
      foreach ($foundHostsV4 as $record) {
         if ($record['destination'] !== $publicIPv4) {
            // Ja
            $ipv4change = true;
            write_debug("API: IPv4 address changed from '".$record['destination']."' to '".$publicIPv4."'");
         } else {
            // Nein
            write_debug("API: IPv4 address '".$publicIPv4."' not changed");
         }
      }

      // IP geändert
      if ($ipv4change === true) {
         $foundHostsV4[0]['destination'] = $publicIPv4;
         // Eintrag aktualisieren
         if (updateDnsRecords(DOMAIN, CUSTOMERNR, APIKEY, $apisessionid, $foundHostsV4)) {
            write_debug("API: Success, IPv4 address updated");
         } else {
            write_debug("API: Error, failed to update IPv4 address");
            return false;
         }
      }

      // API Logout
      if (logout(CUSTOMERNR, APIKEY, $apisessionid)) {
         write_debug("API: Logout successful");
         return true;
      } else {
         write_debug("API: Logout failed");
         return false;
      }

      // ---------------------------------------------------------------------------------

   }
}

// Zeitstempel in lesbares Format
// ----------------------------------------------------------------------------
function timestamp_to_readable($timestamp) {
   // 20150304-055820
   return date("Ymd-H:i:s", $timestamp);
}

// Fehler-Anzeige
// ----------------------------------------------------------------------------
function print_error_status($errorcode) {
   $errorcode = trim(str_replace("\n", "", $errorcode));
   if ( $errorcode == "0" ) {
      // Erfolg
      return "OK";
   } else {
      // Fehler
      return "Error '#".$errorcode."'";
   }
}

// IPv4-Adresse füllen auf xxx.xxx.xxx.xxx
// ----------------------------------------------------------------------------
function pad_ipv4($address) {
   // Zerschneiden
   $ip_parts = explode(".", $address);
   // Füllen
   $ip_parts[0] = str_pad($ip_parts[0], 3, "0", STR_PAD_LEFT);
   $ip_parts[1] = str_pad($ip_parts[1], 3, "0", STR_PAD_LEFT);
   $ip_parts[2] = str_pad($ip_parts[2], 3, "0", STR_PAD_LEFT);
   $ip_parts[3] = str_pad($ip_parts[3], 3, "0", STR_PAD_LEFT);
   // Zusammenkleben
   $address = implode(".", $ip_parts);
   // Return
   return $address;
}

// Bool zu String
// ----------------------------------------------------------------------------
function strbool($value) {
   return $value ? 'OK' : 'Not OK';
}
