<?php
// admin/save_candidate.php
session_start();
require_once '../config/db.php';

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso denegado.");
}

$id = $_POST['id'] ?? null;
$type = $_POST['type'];
$name = $_POST['name'];
$team_name = $_POST['team_name'];
$is_winner = isset($_POST['is_winner']) ? 1 : 0;
$photo_url = $_POST['photo_url'] ?? NULL; 
// CRÍTICO: Aseguramos que el valor vacío del input se convierta a NULL para la base de datos
if ($photo_url === '') $photo_url = NULL; 

try {
    if ($id) {
        // ACTUALIZAR (6 placeholders)
        $sql = "UPDATE bonus_candidates SET name = ?, team_name = ?, type = ?, is_winner = ?, photo_url = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $team_name, $type, $is_winner, $photo_url, $id]);
    } else {
        // CREAR NUEVO (5 placeholders)
        $sql = "INSERT INTO bonus_candidates (name, team_name, type, is_winner, photo_url) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $team_name, $type, $is_winner, $photo_url]);
    }

    header('Location: bonus_candidates.php?msg=saved');
    exit;

} catch (PDOException $e) {
    // Error si el candidato ya existe (clave UNIQUE)
    if ($e->getCode() == 23000) {
        header('Location: bonus_candidates.php?err=duplicate');
    } else {
        die("Error SQL: " . $e->getMessage());
    }
}