<?php
// stats.php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['nombre'];

// --- ÍNDICE DE RIESGO POR FASE ---
$stmt_risk = $pdo->prepare("SELECT 
    m.phase, SUM(p.points_earned) AS total_pts
FROM predictions p
JOIN matches m ON p.match_id = m.id
WHERE p.user_id = ? AND m.status = 'finished'
GROUP BY m.phase");
$stmt_risk->execute([$user_id]);
$phase_pts_raw = $stmt_risk->fetchAll(PDO::FETCH_KEY_PAIR);

$phase_pts = ($phase_pts_raw === false) ? [] : $phase_pts_raw;

$knockout_pts = get_phase_points($phase_pts, 'round_32') + 
                get_phase_points($phase_pts, 'round_16') + 
                get_phase_points($phase_pts, 'quarter') + 
                get_phase_points($phase_pts, 'semi') + 
                get_phase_points($phase_pts, 'final');

$group_pts = get_phase_points($phase_pts, 'group');

$risk_ratio = ($knockout_pts + $group_pts) > 0 
    ? round(($knockout_pts / ($knockout_pts + $group_pts)) * 100) 
    : 0;

// --- MAPEO DE NOMBRES PARA DESGLOSE ---
$stmt_teams_map = $pdo->query("SELECT id, name, flag FROM teams");
$team_name_map = [];
foreach($stmt_teams_map->fetchAll(PDO::FETCH_ASSOC) as $t) {
    $team_name_map[$t['id']] = $t['flag'] . ' ' . $t['name'];
}

$stmt_candidates_map = $pdo->query("SELECT id, name FROM bonus_candidates");
$candidate_name_map = $stmt_candidates_map->fetchAll(PDO::FETCH_KEY_PAIR);

// --- CONSULTAS DE DATOS ---

// 1. Desglose de Puntos del Usuario
$sql_breakdown = "SELECT
    (SELECT COALESCE(SUM(points_earned), 0) FROM predictions WHERE user_id = u.id) AS match_points,
    (SELECT COALESCE(SUM(points_awarded), 0) FROM group_ranking_points WHERE user_id = u.id AND group_name != 'Z') AS bonus_points,
    (SELECT COALESCE(SUM(points_awarded), 0) FROM daily_quiz_responses WHERE user_id = u.id) AS quiz_points,
    (SELECT COALESCE(SUM(points_awarded), 0) FROM group_ranking_points WHERE user_id = u.id AND group_name = 'Z') AS awards_total
FROM users u
WHERE u.id = ?";

$stmt_points = $pdo->prepare($sql_breakdown);
$stmt_points->execute([$user_id]);
$user_breakdown = $stmt_points->fetch(PDO::FETCH_ASSOC);

$match_points = $user_breakdown['match_points'] ?? 0;
$bonus_points = $user_breakdown['bonus_points'] ?? 0;
$quiz_points = $user_breakdown['quiz_points'] ?? 0;
$awards_total = $user_breakdown['awards_total'] ?? 0;
$total_acumulado = $match_points + $bonus_points + $quiz_points + $awards_total;

// 2. Desglose de Premios Especiales (FIX DE WARNINGS)
$stmt_user_awards = $pdo->prepare("SELECT scorer_candidate_id, keeper_candidate_id, total_goals_prediction, champion_team_id FROM user_bonus_predictions WHERE user_id = ?");
$stmt_user_awards->execute([$user_id]);
$user_awards_pred_raw = $stmt_user_awards->fetch(PDO::FETCH_ASSOC);

// Si no hay datos, inicializamos el array con valores nulos para evitar errores de índice
if ($user_awards_pred_raw === false) {
    $user_awards_pred = [
        'scorer_candidate_id' => null,
        'keeper_candidate_id' => null,
        'total_goals_prediction' => null,
        'champion_team_id' => null
    ];
} else {
    $user_awards_pred = $user_awards_pred_raw;
}

// 3. Actividad del Usuario
$stmt_pred_count = $pdo->prepare("SELECT COUNT(id) FROM predictions WHERE user_id = ?");
$stmt_pred_count->execute([$user_id]);
$activity['total_predictions'] = $stmt_pred_count->fetchColumn() ?? 0;

$stmt_exact_hits = $pdo->prepare("SELECT COUNT(id) FROM predictions WHERE user_id = ? AND points_earned IN (25, 30, 55)");
$stmt_exact_hits->execute([$user_id]);
$activity['exact_hits'] = $stmt_exact_hits->fetchColumn() ?? 0;

$stmt_groups_comp = $pdo->prepare("SELECT COUNT(group_name) FROM group_ranking_points WHERE user_id = ? AND group_name != 'Z'");
$stmt_groups_comp->execute([$user_id]);
$activity['groups_completed'] = $stmt_groups_comp->fetchColumn() ?? 0;

// 4. Estadísticas Generales del Torneo
$total_matches = $pdo->query("SELECT COUNT(id) FROM matches")->fetchColumn();
$finished_matches = $pdo->query("SELECT COUNT(id) FROM matches WHERE status = 'finished'")->fetchColumn();
$total_goals_scored = $pdo->query("SELECT SUM(home_score + away_score) FROM matches WHERE status = 'finished' AND phase != 'third_place'")->fetchColumn() ?? 0;
$avg_goals = ($finished_matches > 0) ? round($total_goals_scored / $finished_matches, 2) : 0.0;
$total_users = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'user'")->fetchColumn() ?? 0;

// 5. Próxima Predicción Popular
$next_match_id = $pdo->query("SELECT id FROM matches WHERE status = 'scheduled' ORDER BY match_date ASC LIMIT 1")->fetchColumn();
$popular_pred = null;
$next_match_info = null;
if ($next_match_id) {
    $stmt_pop = $pdo->prepare("SELECT 
        CONCAT(predicted_home_score, ' - ', predicted_away_score) AS score, 
        COUNT(p.id) AS count,             
        m.team_home_id, m.team_away_id
    FROM predictions p
    JOIN matches m ON p.match_id = m.id
    WHERE p.match_id = ? 
    GROUP BY predicted_home_score, predicted_away_score
    ORDER BY count DESC LIMIT 1");
    $stmt_pop->execute([$next_match_id]);
    $popular_pred = $stmt_pop->fetch(PDO::FETCH_ASSOC);

    $stmt_match_info = $pdo->prepare("SELECT t1.name AS home, t2.name AS away, m.match_date FROM matches m JOIN teams t1 ON m.team_home_id = t1.id JOIN teams t2 ON m.team_away_id = t2.id WHERE m.id = ?");
    $stmt_match_info->execute([$next_match_id]);
    $next_match_info = $stmt_match_info->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Mundial 2026</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stat-card-text { font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'stats'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4 text-center"><i class="bi bi-graph-up-arrow"></i> Centro de Estadísticas y Análisis</h2>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white fw-bold fs-5">
            Análisis Personal: <?php echo htmlspecialchars($user_name); ?>
        </div>
        <div class="card-body row">
            
            <div class="col-md-6 mb-4">
                <h4 class="text-primary mb-3">Desglose de Puntos</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        ⚽ Puntos por Partidos: <span class="stat-card-text"><?php echo $match_points; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        📊 Puntos por Grupos: <span class="stat-card-text"><?php echo $bonus_points; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        ❓ Puntos Pregunta Diaria: <span class="stat-card-text"><?php echo $quiz_points; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between bg-light fw-bold">
                        🌟 Puntos Especiales: <span class="stat-card-text"><?php echo $awards_total; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between fw-bold fs-4 bg-success text-white">
                        TOTAL ACUMULADO: <span class="stat-card-text"><?php echo $total_acumulado; ?></span>
                    </li>
                </ul>
                <div class="mt-3 small px-3 py-2 bg-light border rounded">
                    <h6 class="fw-bold"><i class="bi bi-trophy"></i> Tus Elecciones de Bonus:</h6>
                    <ul class="list-unstyled mb-0">
                        <li>
                            🏆 <b>Campeón:</b> 
                            <?php echo $team_name_map[$user_awards_pred['champion_team_id']] ?? '<span class="text-muted">Pendiente</span>'; ?>
                        </li>
                        <li>
                            👟 <b>Goleador:</b> 
                            <?php echo $candidate_name_map[$user_awards_pred['scorer_candidate_id']] ?? '<span class="text-muted">Pendiente</span>'; ?>
                        </li>
                        <li>
                            🛡️ <b>Portero:</b> 
                            <?php echo $candidate_name_map[$user_awards_pred['keeper_candidate_id']] ?? '<span class="text-muted">Pendiente</span>'; ?>
                        </li>
                        <li>
                            ⚽ <b>Goles Totales:</b> 
                            <span class="fw-bold">
                                <?php echo $user_awards_pred['total_goals_prediction'] ?? 'N/A'; ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <h4 class="text-primary mb-3">Actividad</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        📝 Pronósticos Realizados: <span class="stat-card-text"><?php echo $activity['total_predictions']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        🎯 Aciertos Exactos: <span class="stat-card-text"><?php echo $activity['exact_hits']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        📈 Grupos Completados: <span class="stat-card-text"><?php echo $activity['groups_completed']; ?>/12</span>
                    </li>
                </ul>
                
                <div class="mt-4 text-center">
                    <h6 class="fw-bold mb-2">Índice de Rendimiento en Eliminatorias:</h6>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-info text-dark fw-bold" role="progressbar" style="width: <?php echo $risk_ratio; ?>%;">
                            <?php echo $risk_ratio; ?>%
                        </div>
                    </div>
                    <small class="text-muted">Porcentaje de tus puntos totales obtenidos en fases eliminatorias.</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white fw-bold fs-5">
            Estadísticas Globales del Campeonato
        </div>
        <div class="card-body row">
            <div class="col-md-6 mb-4">
                <h4 class="text-secondary mb-3">Datos del Torneo</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        Partidos Finalizados: <span class="stat-card-text"><?php echo $finished_matches; ?> / <?php echo $total_matches; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Total Goles Marcados: <span class="stat-card-text"><?php echo $total_goals_scored; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Promedio Goles / Partido: <span class="stat-card-text"><?php echo $avg_goals; ?></span>
                    </li>
                </ul>
            </div>
            <div class="col-md-6 mb-4">
                <h4 class="text-secondary mb-3">Datos de la Quiniela</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        Usuarios Participantes: <span class="stat-card-text"><?php echo $total_users; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Total Predicciones: <span class="stat-card-text"><?php echo $pdo->query("SELECT COUNT(id) FROM predictions")->fetchColumn() ?? 0; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>