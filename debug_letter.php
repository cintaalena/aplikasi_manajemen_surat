<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$l = App\Models\Letter::first();
if ($l) {
    echo "ID: " . $l->id . "\n";
    echo "Slug: " . ($l->template_slug ?? 'NULL') . "\n";
    echo "Payload: " . (is_array($l->payload) ? 'array(' . count($l->payload) . ' keys)' : 'null/empty') . "\n";
    echo "printed_by col: " . ($l->getAttributes()['printed_by'] ?? 'NULL') . "\n";
    echo "is_manual: " . ($l->is_manual ? 'true' : 'false') . "\n";
    echo "no_surat: " . ($l->no_surat ?? 'NULL') . "\n";
} else {
    echo "No letters found in database\n";
}
