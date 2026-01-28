<?php
// save_wildcard.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$match_id = $_POST['match_id'] ?? null;

if (!$match_id) {
    header('Location: index.php?err=wildcard_no_match');
    exit;
}

try {
    // 1. Verificar si el comodín ya fue usado
    $stmt_check = $pdo->prepare("SELECT wildcard_used_match_id FROM users WHERE id = ?");
    $stmt_check->execute([$user_id]);
    $current_use = $stmt_check->fetchColumn();

    if ($current_use !== NULL) {
        header('Location: index.php?err=wildcard_used');
        exit;
    }
    
    // 2. Ejecutar la acción irreversible
    $sql = "UPDATE users SET wildcard_used_match_id = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$match_id, $user_id]);

    header('Location: index.php?msg=wildcard_activated&match=' . $match_id);
    exit;

} catch (PDOException $e) {
    die("Error al activar comodín: " . $e->getMessage());
}