<?php
// admin/save_stadium.php
session_start();
require_once '../config/db.php';

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso denegado.");
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'];
$city_country = $_POST['city_country'] ?? null;
$image_url = $_POST['image_url'] ?? null;
if ($image_url === '') $image_url = NULL;

try {
    if ($id) {
        // ACTUALIZAR
        $sql = "UPDATE stadiums SET name = ?, city_country = ?, image_url = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $city_country, $image_url, $id]);
    } else {
        // CREAR NUEVO
        $sql = "INSERT INTO stadiums (name, city_country, image_url) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $city_country, $image_url]);
    }

    header('Location: stadiums.php?msg=saved');
    exit;

} catch (PDOException $e) {
    // Error si el nombre del estadio ya existe (clave UNIQUE)
    if ($e->getCode() == 23000) {
        header('Location: stadiums.php?err=duplicate');
    } else {
        die("Error SQL: " . $e->getMessage());
    }
}