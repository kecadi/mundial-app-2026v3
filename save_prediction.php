<?php
// save_prediction.php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$match_id = $_POST['match_id'];
$score_home = $_POST['score_home'];
$score_away = $_POST['score_away'];

// Obtención de la variable: puede ser un ID o una cadena vacía ""
$qualifier_id = $_POST['qualifier_id'] ?? NULL; 

// 🎯 CORRECCIÓN CRÍTICA: Convertir cadena vacía a NULL para MySQL.
if ($qualifier_id === '') {
    $qualifier_id = NULL;
}

// Validación
if (!is_numeric($score_home) || !is_numeric($score_away)) {
    header('Location: index.php?err=datos_invalidos');
    exit;
}

try {
    // Consulta unificada: Siempre incluimos predicted_qualifier_id
    $sql = "INSERT INTO predictions (user_id, match_id, predicted_home_score, predicted_away_score, predicted_qualifier_id) 
            VALUES (:uid, :mid, :sh, :sa, :qid)
            ON DUPLICATE KEY UPDATE 
            predicted_home_score = :sh, 
            predicted_away_score = :sa,
            predicted_qualifier_id = :qid"; 

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'uid' => $user_id,
        'mid' => $match_id,
        'sh' => $score_home,
        'sa' => $score_away,
        'qid' => $qualifier_id // Ahora será NULL si el input estaba vacío
    ]);

    header('Location: index.php?msg=guardado');
    exit;

} catch (PDOException $e) {
    // Ya que hemos corregido el error 1366, si hay otro error, lo mostramos
    die("Error al guardar: " . $e->getMessage());
}