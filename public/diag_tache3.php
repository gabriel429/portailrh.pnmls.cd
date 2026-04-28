<?php
// Diagnostic tache - à supprimer après usage
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$id = (int) ($_GET['id'] ?? 3);
$tache = \App\Models\Tache::with(['createur', 'agent'])->find($id);

// Test serialization via TacheResource
$resourceData = null;
$resourceError = null;
try {
    $request = \Illuminate\Http\Request::capture();
    $resourceData = \App\Http\Resources\TacheResource::make($tache)->resolve($request);
} catch (\Throwable $e) {
    $resourceError = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

header('Content-Type: application/json');
echo json_encode([
    'tache_exists'    => (bool) $tache,
    'id'              => $tache?->id,
    'titre'           => $tache?->titre,
    'statut'          => $tache?->statut,
    'priorite'        => $tache?->priorite,
    'source_type'     => $tache?->source_type,
    'source_emetteur' => $tache?->source_emetteur,
    'createur_id'     => $tache?->createur_id,
    'agent_id'        => $tache?->agent_id,
    'createur'        => $tache?->createur?->nom_complet,
    'agent'           => $tache?->agent?->nom_complet,
    'created_at'      => $tache?->created_at?->toIso8601String(),
    'resource_ok'     => $resourceError === null,
    'resource_error'  => $resourceError,
    'resource_titre'  => $resourceData['titre'] ?? null,
    'resource_statut' => $resourceData['statut'] ?? null,
], JSON_PRETTY_PRINT);
