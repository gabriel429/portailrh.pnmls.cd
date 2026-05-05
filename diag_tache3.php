<?php
// Diagnostic tâche #6 - Connexion directe MySQL (locale)
header('Content-Type: text/html; charset=utf-8');

echo '<h1>🔍 Diagnostic Tâche #6</h1>';

// Tentative de connexion locale (socket)  
$hosts_to_try = [
    ['127.0.0.1', 'u605154961_db', '3915Mbuyamb@', 'portailrh'],
    ['localhost', 'u605154961_db', '3915Mbuyamb@', 'portailrh'],
    ['127.0.0.1', 'root', '', 'portailrh_pnmls'],
];

$dbh = null;
foreach ($hosts_to_try as $try) {
    try {
        $dbh = new PDO("mysql:host={$try[0]};dbname={$try[3]};charset=utf8mb4", $try[1], $try[2], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo '<p style="color:green">✅ Connecté avec : host=' . $try[0] . ', user=' . $try[1] . ', db=' . $try[3] . '</p>';
        break;
    } catch (Exception $e) {
        echo '<p style="color:orange">⚠️ Tentative host=' . $try[0] . ', user=' . $try[1] . ' : ' . $e->getMessage() . '</p>';
    }
}

if (!$dbh) {
    echo '<p style="color:red">❌ Aucune connexion réussie</p>';
    exit;
}

// 1. La tâche 6
echo '<h2>📋 Tâche #6</h2>';
$stmt = $dbh->query("SELECT id, titre, createur_id, agent_id, statut, priorite, source_type, source_emetteur, pourcentage, date_echeance, date_tache, created_at FROM taches WHERE id = 6");
$tache = $stmt->fetch();
if (!$tache) {
    echo '<p style="color:red">❌ Tâche #6 introuvable !</p>';
    echo '<p>Liste des tâches : ';
    foreach ($dbh->query("SELECT id, titre FROM taches ORDER BY id DESC LIMIT 10") as $t) {
        echo '#' . $t['id'] . ' (' . htmlspecialchars($t['titre']) . '), ';
    }
    echo '</p>';
    exit;
}

echo '<table border="1" cellpadding="5" style="border-collapse:collapse">';
foreach ($tache as $k => $v) {
    $display = ($v === null) ? '<span style="color:red;font-weight:bold">NULL</span>' : htmlspecialchars($v);
    echo "<tr><td><strong>$k</strong></td><td>$display</td></tr>";
}
echo '</table>';

// 2. Le créateur
echo '<h2>👤 Créateur (createur_id = ' . ($tache['createur_id'] ?? 'NULL') . ')</h2>';
if (!empty($tache['createur_id'])) {
    $stmt = $dbh->prepare("SELECT id, nom, prenom FROM agents WHERE id = ?");
    $stmt->execute([$tache['createur_id']]);
    $c = $stmt->fetch();
    if ($c) {
        echo '<p style="color:green">✅ ' . htmlspecialchars($c['prenom'] . ' ' . $c['nom']) . ' (ID: ' . $c['id'] . ')</p>';
    } else {
        echo '<p style="color:red">❌ Agent ID=' . $tache['createur_id'] . ' introuvable !</p>';
    }
} else {
    echo '<p style="color:red">❌ createur_id = NULL</p>';
}

// 3. L'agent assigné
echo '<h2>👤 Agent assigné (agent_id = ' . ($tache['agent_id'] ?? 'NULL') . ')</h2>';
if (!empty($tache['agent_id'])) {
    $stmt = $dbh->prepare("SELECT id, nom, prenom, organe FROM agents WHERE id = ?");
    $stmt->execute([$tache['agent_id']]);
    $a = $stmt->fetch();
    if ($a) {
        echo '<p style="color:green">✅ ' . htmlspecialchars($a['prenom'] . ' ' . $a['nom']) . ' (ID: ' . $a['id'] . ', Organe: ' . htmlspecialchars($a['organe'] ?? '-') . ')</p>';
    } else {
        echo '<p style="color:red">❌ Agent ID=' . $tache['agent_id'] . ' introuvable !</p>';
    }
} else {
    echo '<p style="color:red">❌ agent_id = NULL</p>';
}

echo '<hr><p>✅ Diagnostic terminé</p>';
