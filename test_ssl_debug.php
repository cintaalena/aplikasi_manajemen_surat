<?php
// Test SSL dengan context dibuat SEBELUM socket dibuka (cara yang benar)
echo "=== Test A: Context dengan cafile SEBELUM stream dibuat ===\n";
$ctx = stream_context_create([
    'ssl' => [
        'cafile'           => 'C:/xampp/php/cacert.pem',
        'verify_peer'      => true,
        'verify_peer_name' => true,
        'peer_name'        => 'smtp.gmail.com',
        'SNI_enabled'      => true,
    ]
]);
$s = stream_socket_client("tcp://smtp.gmail.com:587", $e, $es, 15, STREAM_CLIENT_CONNECT, $ctx);
if (!$s) { echo "Connect GAGAL: $es\n"; exit(1); }
fgets($s); fwrite($s, "EHLO test.local\r\n");
while(($l=fgets($s))!==false){ if($l[3]===' ') break; }
fwrite($s,"STARTTLS\r\n"); fgets($s);
$ok = @stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
echo "TLS (cafile eksplisit): " . ($ok===true ? "BERHASIL" : "GAGAL") . "\n";
fclose($s);

echo "\n=== Test B: Cek cacert.pem berisi GTS Root ===\n";
$pem = file_get_contents('C:/xampp/php/cacert.pem');
echo "GTS Root R1: " . (str_contains($pem, 'GTS Root R1') ? "ADA" : "TIDAK ADA") . "\n";
echo "GTS Root R2: " . (str_contains($pem, 'GTS Root R2') ? "ADA" : "TIDAK ADA") . "\n";
echo "Google Trust: " . (str_contains($pem, 'Google Trust') ? "ADA" : "TIDAK ADA") . "\n";
echo "GlobalSign: " . (str_contains($pem, 'GlobalSign') ? "ADA" : "TIDAK ADA") . "\n";
$certs = substr_count($pem, '-----BEGIN CERTIFICATE-----');
echo "Jumlah sertifikat: $certs\n";
echo "Ukuran file: " . number_format(filesize('C:/xampp/php/cacert.pem')) . " bytes\n";

echo "\n=== Test C: openssl_get_cert_locations() ===\n";
$locs = openssl_get_cert_locations();
foreach($locs as $k=>$v) echo "$k: $v\n";
