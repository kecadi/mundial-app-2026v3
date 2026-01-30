<?php
// index.php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. SEGURIDAD
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
    date_default_timezone_set('Europe/Madrid'); 
    $current_page = 'home'; 
    include 'includes/navbar.php'; 
?>

<div class="container">
    <?php include 'includes/alerts.php'; ?>
    <?php include 'includes/index_ranking_cards.php'; ?>
    <?php include 'includes/index_rival_duel.php'; ?>
    <?php include 'includes/index_quiz_section.php'; ?>
    <?php include 'includes/index_lock_alert.php'; ?>
    <?php include 'includes/index_progress_bar.php'; ?>
    <?php include 'includes/index_ultimas_actualizaciones.php'; ?>    
    <?php include 'includes/index_matches_list.php'; ?>
</div>

<?php include 'includes/modal_predict.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Notificación de Retos Recibidos (si hay alguno pendiente)
    <?php if ($retos_pendientes_count > 0): ?>
        setTimeout(() => {
            showNotification(
                "⚔️ ¡Tienes <?php echo $retos_pendientes_count; ?> duelo(s) pendiente(s)! Revisa la Arena.", 
                "danger", 
                "bi-fire"
            );
        }, 1000); // Aparece al segundo
    <?php endif; ?>

    // 2. Notificación de Partidos Vacíos (si hay partidos en las próximas 24h sin pronosticar)
    <?php if ($pronosticos_faltantes_count > 0): ?>
        setTimeout(() => {
            showNotification(
                "⚠️ ¡Cuidado! Te faltan <?php echo $pronosticos_faltantes_count; ?> pronóstico(s) para los partidos de mañana.", 
                "warning text-dark", 
                "bi-exclamation-triangle-fill"
            );
        }, 4500); // Aparece a los 4.5 segundos para no solaparse con la anterior
    <?php endif; ?>
});
</script>

<?php include 'includes/index_logic.js'; ?>
<?php include 'includes/footer.php'; ?>
</body>
</html>