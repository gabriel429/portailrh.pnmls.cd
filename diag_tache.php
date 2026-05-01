<?php
// Fichier de diagnostic pour la tâche #6
// Accès : https://e-pnmls.cd/diag_tache.php

header('Content-Type: text/html; charset=utf-8');

// Boot Laravel via le kernel HTTP (comme le fait index.php)
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Récupérer une requête factice pour initialiser l'application
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Maintenant on peut utiliser Eloquent
use App\Models\Tache;
use App\Models\Agent;

echo '<h1>🔍 Diagnostic Tâche #6</h1>';

// 1. Vérifier l'existence de la tâche
$tache = Tache::find(6);
if (!$tache) {
    echo '<p style="color:red">❌ Tâche #6 introuvable !</p>';
    echo '<p>Liste des tâches disponibles : ';
    $all = Tache::select('id', 'titre')->limit(20)->get();
    foreach ($all as $t) echo '#' . $t->id . ' (' . $t->titre . '), ';
    echo '</p>';
    exit;
}

echo '<h2>📋 Données brutes de la tâche</h2>';
echo '<table border="1" cellpadding="5" style="border-collapse:collapse">';
echo '<tr><th>Champ</th><th>Valeur</th></tr>';
foreach (['id', 'titre', 'description', 'source_type', 'source_emetteur', 'priorite', 'statut', 'pourcentage', 'createur_id', 'agent_id', 'date_echeance', 'date_tache', 'created_at', 'updated_at'] as $field) {
    $val = $tache->$field;
    if ($val instanceof \Carbon\Carbon) $val = $val->toDateTimeString();
    echo '<tr>';
    echo '<td>' . $field . '</td>';
    echo '<td>' . var_export($val, true) . '</td>';
    echo '</tr>';
}
echo '</table>';

// 2. Vérifier le créateur
echo '<h2>👤 Créateur</h2>';
if ($tache->createur_id) {
    $createur = Agent::find($tache->createur_id);
    if ($createur) {
        echo '<p style="color:green">✅ Créateur trouvé : ' . $createur->prenom . ' ' . $createur->nom . ' (ID: ' . $createur->id . ')</p>';
        echo '<p>Organe : ' . ($createur->organe ?? 'NON DÉFINI') . '</p>';
    } else {
        echo '<p style="color:red">❌ Agent createur_id=' . $tache->createur_id . ' introuvable dans la table agents !</p>';
    }
} else {
    echo '<p style="color:red">❌ createur_id est NULL !</p>';
}

// 3. Vérifier l'agent assigné
echo '<h2>👤 Agent assigné</h2>';
if ($tache->agent_id) {
    $agentAssign = Agent::find($tache->agent_id);
    if ($agentAssign) {
        echo '<p style="color:green">✅ Agent assigné trouvé : ' . $agentAssign->prenom . ' ' . $agentAssign->nom . ' (ID: ' . $agentAssign->id . ')</p>';
        echo '<p>Organe : ' . ($agentAssign->organe ?? 'NON DÉFINI') . '</p>';
    } else {
        echo '<p style="color:red">❌ Agent agent_id=' . $tache->agent_id . ' introuvable dans la table agents !</p>';
    }
} else {
    echo '<p style="color:red">❌ agent_id est NULL !</p>';
}

// 4. Tester la réponse API formatée
echo '<h2>📦 Format de la réponse API (TacheResource)</h2>';
$tache->load(['createur', 'agent', 'activitePlan']);
$resource = new App\Http\Resources\TacheResource($tache);
$resolved = $resource->resolve();

echo '<pre>';
echo json_encode($resolved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo '</pre>';

// 5. Info sur l'utilisateur SEN typique
echo '<h2>👤 Infos sur l\'utilisateur SEN</h2>';
$senUser = App\Models\User::whereHas('roles', function($q) { $q->where('nom_role', 'SEN'); })->first();
if ($senUser) {
    echo '<p>Utilisateur SEN trouvé : ' . $senUser->name . ' (email: ' . $senUser->email . ')</p>';
    echo '<p>Agent_id lié : ' . var_export($senUser->agent_id, true) . '</p>';
    if ($senUser->agent_id) {
        $senAgent = Agent::find($senUser->agent_id);
        echo '<p>Agent : ' . ($senAgent ? $senAgent->prenom . ' ' . $senAgent->nom : 'Introuvable') . '</p>';
    }
} else {
    echo '<p style="color:orange">⚠️ Aucun utilisateur avec le rôle SEN trouvé</p>';
}

echo '<hr>';
echo '<p>Fait le : ' . date('Y-m-d H:i:s') . '</p>';

$kernel->terminate($request, $response);
