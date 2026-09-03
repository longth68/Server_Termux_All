<?php
$path = 'f:/SRC NRO/NRO-LOCAL/Server/src/nro/server/WebAdminAPI.java';
$content = file_get_contents($path);
$fixed = mb_convert_encoding($content, 'cp1252', 'utf-8');
file_put_contents($path, $fixed);
echo "Fixed Java Mojibake\n";
