<?php
// Diagnostic tache - à supprimer après usage
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$id = (int) ($_GET['id'] ?? 6);
$tache = \App\Models\Tache::findOrFail($id);

// Charger les mêmes relations que le contrôleur
$loadError = null;
try {
    $tache->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent']);
} catch (\Throwable $e) {
    $loadError = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

// Sérialiser via TacheResource complet
$resourceFull = null;
$resourceError = null;
try {
    $request = \Illuminate\Http\Request::capture();
    $resourceFull = \App\Http\Resources\TacheResource::make($tache)->resolve($request);
} catch (\Throwable $e) {
    $resourceError = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

// Réponse API simulée (même format que ApiController::resource())
$apiResponse = [
    'data'        => $resourceFull,
    'meta'        => ['isCreateur' => false, 'isAssigne' => false],
    'isCreateur'  => false,
    'isAssigne'   => false,
];

header('Content-Type: application/json');
echo json_encode([
    'tache_exists'     => true,
    'db_id'            => $tache->id,
    'db_titre'         => $tache->titre,
    'db_statut'        => $tache->statut,
    'db_priorite'      => $tache->priorite,
    'db_source_type'   => $tache->source_type,
    'db_createur_id'   => $tache->createur_id,
    'db_agent_id'      => $tache->agent_id,
    'load_error'       => $loadError,
    'resource_error'   => $resourceError,
    'resource_titre'   => $resourceFull['titre'] ?? null,
    'resource_statut'  => $resourceFull['statut'] ?? null,
    'resource_priorite'=> $resourceFull['priorite'] ?? null,
    'resource_createur'=> $resourceFull['createur']['nom_complet'] ?? null,
    'resource_agent'   => $resourceFull['agent']['nom_complet'] ?? null,
    'resource_created_at' => $resourceFull['created_at'] ?? null,
    'resource_source_emetteur' => $resourceFull['source_emetteur'] ?? null,
    // Réponse API complète (ce que data.data en Vue doit recevoir)
    'api_data_dot_data_keys' => $resourceFull ? array_keys($resourceFull) : null,
    'api_structure'    => [
        'has_data_key'      => isset($apiResponse['data']),
        'data_titre'        => $apiResponse['data']['titre'] ?? 'ABSENT',
        'data_statut'       => $apiResponse['data']['statut'] ?? 'ABSENT',
        'top_level_keys'    => array_keys($apiResponse),
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
