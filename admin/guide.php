<?php
// admin/guide.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Guía de Reinicio</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
    $current_page = 'guide'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4 text-danger"><i class="bi bi-tools"></i> Guía de Mantenimiento y Reinicio</h2>
    <p class="text-muted">Este panel contiene las instrucciones esenciales para reiniciar la quiniela antes de un nuevo torneo o para depurar datos de prueba.</p>

    <div class="card shadow-sm mb-5 border-danger">
        <div class="card-header bg-danger text-white fw-bold fs-5">
            1. Borrado Completo de Datos de Participación (Reinicio Total)
        </div>
        <div class="card-body">
            <p>Para iniciar una nueva Quiniela, debes **vaciar todas las tablas que contienen pronósticos, logs de actividad y puntuaciones**. El orden es crucial debido a las claves foráneas.</p>
            <p class="text-danger fw-bold">⚠️ ADVERTENCIA: Esta acción es irreversible y elimina TODO el progreso de los usuarios.</p>
            
            <h6 class="fw-bold mt-3">Comandos SQL para Copiar/Pegar:</h6>
            <div class="alert alert-light p-3">
                <pre class="mb-0 small">
                SET FOREIGN_KEY_CHECKS = 0;

                -- 1. LIMPIEZA DE DATOS DE USUARIOS (Pronósticos, Puntos, Logs, Desafíos)
                TRUNCATE TABLE predictions;
                TRUNCATE TABLE group_ranking_points;
                TRUNCATE TABLE user_bonus_predictions;
                TRUNCATE TABLE daily_quiz_responses;
                TRUNCATE TABLE match_comments;
                TRUNCATE TABLE user_read_status;
                TRUNCATE TABLE match_challenges;
                TRUNCATE TABLE admin_activity_log;

                -- 2. RESETEAR COMODÍN X2 (Deja el comodín disponible para todos los usuarios)
                UPDATE users SET wildcard_used_match_id = NULL;

                -- 3. LIMPIEZA DE RESULTADOS DE PARTIDOS (Mantiene el calendario generado)
                UPDATE matches SET 
                    home_score = NULL, 
                    away_score = NULL, 
                    status = 'scheduled', 
                    real_qualifier_id = NULL;

                -- 4. LIMPIEZA DE RESULTADOS FINALES GLOBALES
                UPDATE tournament_results SET 
                    final_total_goals = NULL, 
                    champion_team_id = NULL;

                SET FOREIGN_KEY_CHECKS = 1;</pre>
            </div>
            <p class="small text-muted">Ejecuta esto directamente en phpMyAdmin o en la consola MySQL.</p>
        </div>
    </div>

    <div class="card shadow-sm mb-5 border-success">
        <div class="card-header bg-success text-white fw-bold fs-5">
            2. Checklist de Configuración (Previo al Primer Partido)
        </div>
        <div class="card-body">
            <p class="fw-bold">Para que el juego funcione correctamente, debes asegurarte de que estos paneles contengan datos actualizados:</p>
            
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> 
                    ⚽ **Equipos** (Nombres y Jugadores Clave)
                    <a href="teams.php" class="btn btn-sm btn-outline-success">Gestionar</a>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> 
                    🏆 **Candidatos Bonus** (Goleador/Portero)
                    <a href="bonus_candidates.php" class="btn btn-sm btn-outline-success">Gestionar</a>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> 
                    🧠 **Quiz Diario** (Programar preguntas para cada día)
                    <a href="quiz.php" class="btn btn-sm btn-outline-success">Gestionar</a>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> 
                    🏟️ **Estadios** (Nombres y URLs de imágenes)
                    <a href="stadiums.php" class="btn btn-sm btn-outline-success">Gestionar</a>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> 
                    📊 **Generación de Bracket** (Crear los 72+ partidos de la Fase de Grupos)
                    <a href="knockout.php" class="btn btn-sm btn-outline-success">Generar</a>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> 
                    🥇 **Resultados Finales** (Verificar la fila de resultados globales)
                    <a href="final_results.php" class="btn btn-sm btn-outline-secondary">Revisar</a>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>