<?php
// ============================================================
// patch_client_jar.php - Doi IP/Port server trong client JAR (J2ME)
//  Sua truc tiep CONSTANT_Utf8 trong .class (KHONG vo cau truc jar)
//  Termux: php patch_client_jar.php NROPGaming.jar NRO-Wifi.jar 192.168.1.50 [14445]
// ============================================================
if ($argc < 4) {
    echo "Dung: php {$argv[0]} <vao.jar> <ra.jar> <ip_moi> [port_moi]\n";
    echo "VD  : php {$argv[0]} NROPGaming.jar NRO-Wifi.jar 192.168.1.50\n";
    exit(1);
}
$inJar  = $argv[1];
$outJar = $argv[2];
$newIp  = $argv[3];
$newPort = $argv[4] ?? null;
$oldIp  = '127.0.0.1';
$oldPort = '14445';

if (!filter_var($newIp, FILTER_VALIDATE_IP)) { echo "LOI: IP moi khong hop le: $newIp\n"; exit(1); }
if ($newPort !== null && !preg_match('/^\d{2,5}$/', $newPort)) { echo "LOI: port moi khong hop le: $newPort\n"; exit(1); }
if (!is_file($inJar)) { echo "LOI: khong tim thay $inJar\n"; exit(1); }
if (!class_exists('ZipArchive')) { echo "LOI: PHP thieu ext-zip. Termux: pkg install php-zip\n"; exit(1); }

$pairs = [[$oldIp, $newIp]];
if ($newPort !== null && $newPort !== $oldPort) $pairs[] = [$oldPort, $newPort];

// Do dai phan du lieu theo tag constant pool (giong JVM spec)
function cpSkip($tag) {
    if ($tag === 7 || $tag === 8 || $tag === 16 || $tag === 19 || $tag === 20) return 2;
    if ($tag === 15) return 3;
    if ($tag === 9 || $tag === 10 || $tag === 11 || $tag === 12 || $tag === 17 || $tag === 18) return 4;
    if ($tag === 3 || $tag === 4) return 4;
    if ($tag === 5 || $tag === 6) return 8;
    return null;
}

function patchClass($data, $pairs, &$stats) {
    if (substr($data, 0, 4) !== "\xCA\xFE\xBA\xBE") return $data;
    $cpCount = unpack('n', substr($data, 8, 2))[1];
    $pos = 10;
    $out = substr($data, 0, 10);
    $i = 1;
    $len = strlen($data);
    while ($i < $cpCount) {
        if ($pos >= $len) break;
        $tag = ord($data[$pos]);
        if ($tag === 1) {
            $slen = unpack('n', substr($data, $pos + 1, 2))[1];
            $raw = substr($data, $pos + 3, $slen);
            $new = $raw;
            foreach ($pairs as $p) {
                if (strpos($new, $p[0]) !== false) {
                    $stats[$p[0]] = ($stats[$p[0]] ?? 0) + substr_count($new, $p[0]);
                    $new = str_replace($p[0], $p[1], $new);
                }
            }
            $out .= "\x01" . pack('n', strlen($new)) . $new;
            $pos += 3 + $slen;
        } else {
            $skip = cpSkip($tag);
            if ($skip === null) { echo "LOI: tag la $tag tai cp#$i\n"; exit(1); }
            $out .= substr($data, $pos, 1 + $skip);
            $pos += 1 + $skip;
            if ($tag === 5 || $tag === 6) $i++; // long/double chiem 2 slot
        }
        $i++;
    }
    $out .= substr($data, $pos);
    return $out;
}

$zin = new ZipArchive();
if ($zin->open($inJar) !== true) { echo "LOI: khong mo duoc $inJar\n"; exit(1); }
$zout = new ZipArchive();
if ($zout->open($outJar, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { echo "LOI: khong tao duoc $outJar\n"; exit(1); }
$stats = [];
for ($k = 0; $k < $zin->numFiles; $k++) {
    $name = $zin->getNameIndex($k);
    $data = $zin->getFromIndex($k);
    if (substr($name, -6) === '.class') $data = patchClass($data, $pairs, $stats);
    $zout->addFromString($name, $data);
}
$zin->close(); $zout->close();

echo "Xong: $outJar\n";
foreach ($stats as $old => $n) echo "  '$old' -> thay $n cho\n";
if (empty($stats)) { echo "CANH BAO: khong tim thay IP/port cu trong jar!\n"; exit(2); }
