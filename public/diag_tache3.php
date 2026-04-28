<?php
// Diagnostic tache 3 - à supprimer après usage
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$tache = \App\Models\Tache::with(['createur', 'agent'])->find(3);

header('Content-Type: application/json');
echo json_encode([
    'tache_exists' => (bool) $tache,
    'id'           => $tache?->id,
    'titre'        => $tache?->titre,
    'statut'       => $tache?->statut,
    'source_type'  => $tache?->source_type,
    'source_emetteur' => $tache?->source_emetteur,
    'createur_id'  => $tache?->createur_id,
    'agent_id'     => $tache?->agent_id,
    'createur'     => $tache?->createur?->nom_complet,
    'agent'        => $tache?->agent?->nom_complet,
    'created_at'   => $tache?->created_at?->toIso8601String(),
], JSON_PRETTY_PRINT);
