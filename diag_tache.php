=<?php
// Fichier de diagnostic pour la tâche #6
// À placer dans /public_html/diag_tache.php
// Accès : https://e-pnmls.cd/diag_tache.php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Models\Tache;
use App\Models\Agent;
use App\Models\User;

// Forcer le mode maintenance off
header('Content-Type: text/html; charset=utf-8');

echo '<h1>🔍 Diagnostic Tâche #6</h1>';

// 1. Vérifier l'existence de la tâche
$tache = Tache::find(6);
if (!$tache) {
    echo '<p style="color:red">❌ Tâche #6 introuvable !</p>';
    exit;
}

echo '<h2>📋 Données brutes de la tâche</h2>';
echo '<table border="1" cellpadding="5" style="border-collapse:collapse">';
echo '<tr><th>Champ</th><th>Valeur</th></tr>';
foreach (['id', 'titre', 'description', 'source_type', 'source_emetteur', 'priorite', 'statut', 'pourcentage', 'createur_id', 'agent_id', 'date_echeance', 'date_tache', 'created_at'] as $field) {
    echo '<tr>';
    echo '<td>' . $field . '</td>';
    echo '<td>' . var_export($tache->$field, true) . '</td>';
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
        echo '<p style="color:red">❌ Agent createur_id=' . $tache->createur_id . ' introuvable !</p>';
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
        echo '<p style="color:red">❌ Agent agent_id=' . $tache->agent_id . ' introuvable !</p>';
    }
} else {
    echo '<p style="color:red">❌ agent_id est NULL !</p>';
}

// 4. Tester la réponse API formatée
echo '<h2>📦 Format de la réponse API</h2>';
$tache->load(['createur', 'agent', 'activitePlan']);
$resource = new App\Http\Resources\TacheResource($tache);
$resolved = $resource->resolve();

echo '<pre>';
echo json_encode($resolved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo '</pre>';

echo '<hr>';
echo '<p>Fait le : ' . date('Y-m-d H:i:s') . '</p>';
