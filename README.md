inwx-fritzbox-dyndns
====================

Forked from Florian-t
<https://github.com/florian-t/inwx-fritzbox-dyndns>

Based on a Script by Thomas Klumpp: <http://www.thomas-klumpp.de/2013/02/02/dyndns-bei-inwx-de-via-xml-rpc-api-mit-php/>
but this side is not available any more see it in archive: <https://web.archive.org/web/20150916190907/http://www.thomas-klumpp.de/2013/02/02/dyndns-bei-inwx-de-via-xml-rpc-api-mit-php/>

uses the Inwx api. The necessary Domrobot.php is included in the repository

Usage
----
replace everything marked with `@@@` in the file `config.inc.php` and in the update URL for the Fritzbox
`https://@@@url_of_my_php_xmlrpc_able_host@@@/update-inwx-get.php?user=<username>&password=<pass>&ip4addr=<ipaddr>&ip6addr=<ip6addr>`

Requirements
----

- Domain registered at [inwx.de]
- [PHP API from INWX][1] (currently using v2.4)
- Webspace with php and xmlrpc for php
- Fritzbox or similar router

[inwx.de]:http://inwx.de
[1]:https://github.com/inwx/php-client