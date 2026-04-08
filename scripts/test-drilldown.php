<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ctrl = new \App\Http\Controllers\Api\ExecutiveDashboardController();
$req = new \Illuminate\Http\Request();

echo "=== ORGANE SEN ===\n";
$resp = $ctrl->organeDetail($req, 'SEN');
$data = $resp->getData(true);
echo "Keys: " . implode(', ', array_keys($data)) . "\n";
echo "organe: " . ($data['organe'] ?? 'null') . "\n";
echo "nom: " . ($data['nom'] ?? 'null') . "\n";
echo "type_items: " . ($data['type_items'] ?? 'null') . "\n";
echo "items count: " . count($data['items'] ?? []) . "\n";
echo "summary: " . json_encode($data['summary'] ?? []) . "\n";
if (!empty($data['items'])) {
    echo "First item keys: " . implode(', ', array_keys($data['items'][0])) . "\n";
    echo "First item: " . json_encode($data['items'][0], JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== ORGANE SEP ===\n";
$resp2 = $ctrl->organeDetail($req, 'SEP');
$data2 = $resp2->getData(true);
echo "items count: " . count($data2['items'] ?? []) . "\n";
echo "type_items: " . ($data2['type_items'] ?? 'null') . "\n";
if (!empty($data2['items'])) {
    echo "First item keys: " . implode(', ', array_keys($data2['items'][0])) . "\n";
    echo "First item: " . json_encode($data2['items'][0], JSON_PRETTY_PRINT) . "\n";
}

if (!empty($data['items'])) {
    $firstId = $data['items'][0]['id'];
    echo "\n=== PROVINCE DETAIL (id=$firstId) ===\n";
    $resp3 = $ctrl->provinceDetail($req, $firstId);
    $data3 = $resp3->getData(true);
    echo "Keys: " . implode(', ', array_keys($data3)) . "\n";
    echo "Province keys: " . implode(', ', array_keys($data3['province'] ?? [])) . "\n";
    echo "effectifs: " . json_encode($data3['effectifs'] ?? []) . "\n";
    echo "departments count: " . count($data3['departments'] ?? []) . "\n";
    echo "agents count: " . count($data3['agents'] ?? []) . "\n";
}
