<?php
// save_bonus_election.php
session_start();
require_once 'config/db.php'; 

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bonus.php');
    exit;
}

// --- VERIFICACIÓN DE BLOQUEO ---
$first_match = $pdo->query("SELECT match_date FROM matches ORDER BY match_date ASC LIMIT 1")->fetchColumn();
$tournament_start_time = $first_match ? strtotime($first_match) : time() + 3600; 

if (time() >= $tournament_start_time) {
    header('Location: bonus.php?err=locked');
    exit;
}

$scorer_id = $_POST['scorer_id'] ?? null;
$keeper_id = $_POST['keeper_id'] ?? null;
$total_goals = $_POST['total_goals'] ?? null;
$champion_id = $_POST['champion_id'] ?? null;

// Validación de campos
if (!$scorer_id || !$keeper_id || !$total_goals || !$champion_id) {
    header('Location: bonus.php?err=missing_selection');
    exit;
}

try {
    // INSERT O UPDATE: Sólo se permite una fila por usuario
    $sql = "INSERT INTO user_bonus_predictions 
            (user_id, scorer_candidate_id, keeper_candidate_id, total_goals_prediction, champion_team_id) 
            VALUES (:uid, :sid, :kid, :tg, :cid)
            ON DUPLICATE KEY UPDATE 
            scorer_candidate_id = :sid_update,
            keeper_candidate_id = :kid_update,
            total_goals_prediction = :tg_update,
            champion_team_id = :cid_update";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'uid' => $user_id,
        'sid' => $scorer_id,
        'kid' => $keeper_id,
        'tg' => $total_goals,
        'cid' => $champion_id,
        'sid_update' => $scorer_id,
        'kid_update' => $keeper_id,
        'tg_update' => $total_goals,
        'cid_update' => $champion_id
    ]);

    header('Location: bonus.php?msg=saved');
    exit;

} catch (PDOException $e) {
    die("Error al guardar elección: " . $e->getMessage());
}