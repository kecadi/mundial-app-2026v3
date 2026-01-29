<?php
// index.php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. SEGURIDAD: Si no hay usuario logueado, mandar al login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['nombre'];
$rol_usuario = $_SESSION['role'];

require_once 'includes/index_logic.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'includes/header_meta.php'; ?>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php 
    date_default_timezone_set('Europe/Madrid'); // FIX: Aseguramos la zona horaria del servidor
    $current_page = 'home'; 
    include 'includes/navbar.php'; 
?>
<div class="container">
<?php include 'includes/alerts.php'; ?>
<?php include 'includes/index_ranking_cards.php'; ?>
<?php include 'includes/index_quiz_section.php'; ?>
<?php include 'includes/index_lock_alert.php'; ?>
<?php include 'includes/index_progress_bar.php'; ?>
<?php include 'includes/index_ultimas_actualizaciones.php'; ?>    
<?php include 'includes/index_matches_list.php'; ?>
</div>
<?php include 'includes/modal_predict.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/index_logic.js'; ?>
<?php include 'includes/footer.php'; ?>
</body>
</html>