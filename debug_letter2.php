<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$l = App\Models\Letter::first();
$l->load('printedBy:id,name,nip,jabatan');

$json = json_encode([
    'id' => $l->id,
    'template_slug' => $l->template_slug,
    'no_surat' => $l->no_surat,
    'title' => $l->title,
    'is_manual' => $l->is_manual,
    'printed_by' => $l->printedBy ? ['id' => $l->printedBy->id, 'name' => $l->printedBy->name, 'jabatan' => $l->printedBy->jabatan, 'nip' => $l->printedBy->nip] : null,
    'payload_keys' => is_array($l->payload) ? array_keys($l->payload) : [],
    'payload_noSurat' => $l->payload['noSurat'] ?? 'NOT FOUND',
    'payload_nama' => $l->payload['nama'] ?? 'NOT FOUND',
], JSON_PRETTY_PRINT);

echo $json . "\n";
