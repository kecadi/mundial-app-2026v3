<?php
// save_comment.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$match_id = $_POST['match_id'] ?? null;
$comment = $_POST['comment'] ?? '';

// 1. SEGURIDAD ANTI-XSS: Sanitizar y limpiar el comentario antes de guardarlo
$safe_comment = htmlspecialchars(strip_tags(trim($comment)));

if (!$match_id || empty($safe_comment)) {
    header('Location: index.php?err=invalid_comment');
    exit;
}

try {
    // 2. Guardar el comentario seguro en la base de datos
    $sql = "INSERT INTO match_comments (match_id, user_id, comment) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$match_id, $user_id, $safe_comment]);

    // 3. Redirigir de vuelta al timeline
    header('Location: match_comments.php?match_id=' . $match_id . '&msg=sent');
    exit;

} catch (PDOException $e) {
    die("Error al guardar el comentario: " . $e->getMessage());
}