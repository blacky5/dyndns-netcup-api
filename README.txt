Netcup DDNS server
----------------------------------------------------------------------------------------
A PHP based DynDNS service for Netcup servers using the Netcup DNS API, by Stefan Onderka,
https://www.onderka.com/computer-und-netzwerk/eigener-dyndns-auf-netcup-vserver-mit-api

Based on "Dynamic DNS client for netcup DNS API" by Lars-Sören Steck,
https://github.com/stecklars/dynamic-dns-netcup-api

Needs a Netcup vServer, Apache, PHP and a Netcup DNS API Key and Password (Netcup Wiki:
https://www.netcup-wiki.de/wiki/DNS_API)


Example setup
----------------------------------------------------------------------------------------
Domain using Netcup DNS servers   example.com
Subdomain for DynDNS              ddns
DynDNS Hosts                      host1.ddns.example.com, host2.ddns.example.com

The DynDNS server authenticates a client and upon successful validation updates the DNS
A record (IPv4 only at the moment) for the client 'hostname.ddns.example.com'.


Files and folders
----------------------------------------------------------------------------------------
File   include/config.php     Set DNS zones, API credentials, logging, folders etc.
File   include/users.php      Set Authentication and hosts
File   include/api.php        PHP functions, Netcup API related, mostly Lars-Sören's code
File   include/functions.php  PHP functions, nothing to configure here.
Folder data/                  Host data
Folder log/                   Logfiles
File   index.php              Main PHP script
File   favicon.ico            Browser icon
File   .htaccess              Apache rewrite rules and file/folder protection


Installation and configuration
----------------------------------------------------------------------------------------
Unpack into the Apache virtual host folder for 'ddns.example.com' and edit the files

* include/users.php
* include/config.php


File users.php
-------------------------------------
Add entries in the format

  $known_clients['hostname1'] = 'password1';
  $known_clients['hostname2'] = 'password2';

to set the password for host 'hostname1.ddns.example.com' to 'password1' etc.


File config.php
-------------------------------------
Get an API key and password: https://www.customercontrolpanel.de -> Stammdaten -> API
Configure as commented. Minimum settings:

CUSTOMERNR     Your Netcup customer number
APIKEY         Your API key
APIPASSWORD    Your API password
DOMAIN         Domain with DNS managed by Netcup, example 'example.com'
DDNS_ZONE      Subdomain of DOMAIN used for DynDNS hosts, example 'ddns'


Testing
----------------------------------------------------------------------------------------
Set DEBUG to true in config.php while testing your installation (Logfile log/ddns.log,
Netcup API log in the "Customer control panel", section "Domains", Link "API Log"):

* Use a browser: http(s)://ddns.example.com
* Use a browser: http(s)://ddns.example.com/?name=hostname1&pass=password1
* Use a browser: http(s)://ddns.example.com/nic/update?name=hostname1&pass=password1
* Use a browser: http(s)://ddns.example.com/nic/update?hostname=hostname1&pass=password1
* Use cURL:      curl -v "http(s)://hostname1:password1@ddns.example.com"
* For FRITZ!Box settings see https://www.onderka.com/computer-und-netzwerk/eigener-dyndns-mit-bind-apache-und-php


Misc
----------------------------------------------------------------------------------------
Apache mod_rewrite needs to be enabled to rewrite DynDNS.org links like '/nic/update?...'
to '/index.php?...'.

* Clients just browsing 'ddns.example.com' will get their IP printed in text/plain format.
* HTTP responses/output is according to the classic DynDNS protocol, see
  https://www.noip.com/integrate/response
* Response for changed & updated IP is 'good [ip_address]',
* Response for unchanged IP is 'nochg [ip_addrress]'
* Response for failed authentication is 'badauth'
* Response for all kinds of internal fails, files and API, is '911'

Stefan Onderka
