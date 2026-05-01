<?php
// Diagnostic tâche #6 - Connexion directe MySQL (sans Laravel)
header('Content-Type: text/html; charset=utf-8');

$host = '194.5.156.2';
$db   = 'portailrh';
$user = 'u605154961_db';
$pass = '3915Mbuyamb@';

echo '<h1>🔍 Diagnostic Tâche #6 (connexion directe)</h1>';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 1. La tâche 6
    echo '<h2>📋 Tâche #6</h2>';
    $stmt = $pdo->query("SELECT id, titre, createur_id, agent_id, statut, priorite, source_type, source_emetteur, pourcentage, date_echeance, date_tache, created_at FROM taches WHERE id = 6");
    $tache = $stmt->fetch();
    if (!$tache) {
        echo '<p style="color:red">❌ Tâche #6 introuvable !</p>';
    } else {
        echo '<table border="1" cellpadding="5">';
        foreach ($tache as $k => $v) {
            $display = ($v === null) ? '<span style="color:red;font-weight:bold">NULL</span>' : htmlspecialchars($v);
            echo "<tr><td>$k</td><td>$display</td></tr>";
        }
        echo '</table>';
    }

    // 2. Le créateur
    echo '<h2>👤 Créateur (createur_id = ' . ($tache['createur_id'] ?? 'NULL') . ')</h2>';
    if (!empty($tache['createur_id'])) {
        $stmt = $pdo->prepare("SELECT id, nom, prenom, organe FROM agents WHERE id = ?");
        $stmt->execute([$tache['createur_id']]);
        $c = $stmt->fetch();
        if ($c) {
            echo '<p style="color:green">✅ ' . $c['prenom'] . ' ' . $c['nom'] . ' (ID: ' . $c['id'] . ', Organe: ' . ($c['organe'] ?? '-') . ')</p>';
        } else {
            echo '<p style="color:red">❌ Agent introuvable !</p>';
        }
    } else {
        echo '<p style="color:red">❌ createur_id est NULL</p>';
    }

    // 3. L'agent assigné
    echo '<h2>👤 Agent assigné (agent_id = ' . ($tache['agent_id'] ?? 'NULL') . ')</h2>';
    if (!empty($tache['agent_id'])) {
        $stmt = $pdo->prepare("SELECT id, nom, prenom, organe FROM agents WHERE id = ?");
        $stmt->execute([$tache['agent_id']]);
        $a = $stmt->fetch();
        if ($a) {
            echo '<p style="color:green">✅ ' . $a['prenom'] . ' ' . $a['nom'] . ' (ID: ' . $a['id'] . ', Organe: ' . ($a['organe'] ?? '-') . ')</p>';
        } else {
            echo '<p style="color:red">❌ Agent introuvable !</p>';
        }
    } else {
        echo '<p style="color:red">❌ agent_id est NULL</p>';
    }

    // 4. Liste des tâches récentes
    echo '<h2>📋 Les 10 dernières tâches</h2>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Titre</th><th>createur_id</th><th>agent_id</th><th>Statut</th></tr>';
    $stmt = $pdo->query("SELECT id, titre, createur_id, agent_id, statut FROM taches ORDER BY id DESC LIMIT 10");
    while ($row = $stmt->fetch()) {
        $cid = $row['createur_id'] ?? '<span style="color:red">NULL</span>';
        $aid = $row['agent_id'] ?? '<span style="color:red">NULL</span>';
        echo "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['titre']) . "</td><td>$cid</td><td>$aid</td><td>{$row['statut']}</td></tr>";
    }
    echo '</table>';

    echo '<hr><p>✅ Diagnostic terminé</p>';

} catch (Exception $e) {
    echo '<p style="color:red">❌ Erreur : ' . $e->getMessage() . '</p>';
}
