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

// --- LÓGICA DE RANKING (Para saber distancia con el líder) ---
$sql_ranking = "SELECT id, (COALESCE(T_MATCH.match_points, 0) + COALESCE(T_BONUS.bonus_points, 0) + COALESCE(T_QUIZ.quiz_points, 0)) AS total 
                FROM users u
                LEFT JOIN (SELECT user_id, SUM(points_earned) AS match_points FROM predictions GROUP BY user_id) T_MATCH ON u.id = T_MATCH.user_id
                LEFT JOIN (SELECT user_id, SUM(points_awarded) AS bonus_points FROM group_ranking_points GROUP BY user_id) T_BONUS ON u.id = T_BONUS.user_id
                LEFT JOIN (SELECT user_id, SUM(points_awarded) AS quiz_points FROM daily_quiz_responses GROUP BY user_id) T_QUIZ ON u.id = T_QUIZ.user_id
                WHERE u.role != 'admin' ORDER BY total DESC";
$ranking_all = $pdo->query($sql_ranking)->fetchAll(PDO::FETCH_ASSOC);
$max_points = $ranking_all[0]['total'] ?? 0;

// --- ÍNDICE DE RIESGO POR FASE ---
$stmt_risk = $pdo->prepare("SELECT m.phase, SUM(p.points_earned) AS total_pts FROM predictions p JOIN matches m ON p.match_id = m.id WHERE p.user_id = ? AND m.status = 'finished' GROUP BY m.phase");
$stmt_risk->execute([$user_id]);
$phase_pts = $stmt_risk->fetchAll(PDO::FETCH_KEY_PAIR);

$knockout_pts = get_phase_points($phase_pts, 'round_32') + get_phase_points($phase_pts, 'round_16') + get_phase_points($phase_pts, 'quarter') + get_phase_points($phase_pts, 'semi') + get_phase_points($phase_pts, 'final');
$group_pts = get_phase_points($phase_pts, 'group');
$risk_ratio = ($knockout_pts + $group_pts) > 0 ? round(($knockout_pts / ($knockout_pts + $group_pts)) * 100) : 0;

// --- MAPEO DE EQUIPOS Y CANDIDATOS ---
$stmt_teams = $pdo->query("SELECT id, name, flag FROM teams");
$team_map = []; foreach($stmt_teams->fetchAll(PDO::FETCH_ASSOC) as $t) { $team_map[$t['id']] = ['n' => $t['name'], 'f' => $t['flag']]; }

$stmt_cand = $pdo->query("SELECT id, name FROM bonus_candidates");
$cand_map = $stmt_cand->fetchAll(PDO::FETCH_KEY_PAIR);

// --- DESGLOSE DE PUNTOS ---
$stmt_p = $pdo->prepare("SELECT 
    (SELECT COALESCE(SUM(points_earned), 0) FROM predictions WHERE user_id = ?) as m_pts,
    (SELECT COALESCE(SUM(points_awarded), 0) FROM group_ranking_points WHERE user_id = ? AND group_name != 'Z') as b_pts,
    (SELECT COALESCE(SUM(points_awarded), 0) FROM daily_quiz_responses WHERE user_id = ?) as q_pts,
    (SELECT COALESCE(SUM(points_awarded), 0) FROM group_ranking_points WHERE user_id = ? AND group_name = 'Z') as s_pts");
$stmt_p->execute([$user_id, $user_id, $user_id, $user_id]);
$pts = $stmt_p->fetch(PDO::FETCH_ASSOC);
$total_acumulado = array_sum($pts);
$diff_leader = $max_points - $total_acumulado;

// --- ACTIVIDAD Y EFECTIVIDAD ---
$stmt_act = $pdo->prepare("SELECT COUNT(id) as total, SUM(CASE WHEN points_earned IN (25,30,55) THEN 1 ELSE 0 END) as exact FROM predictions WHERE user_id = ?");
$stmt_act->execute([$user_id]);
$activity = $stmt_act->fetch(PDO::FETCH_ASSOC);
$eff = ($activity['total'] > 0) ? round(($activity['exact'] / $activity['total']) * 100) : 0;

// --- STATS GLOBALES ---
$finished_matches = $pdo->query("SELECT COUNT(id) FROM matches WHERE status = 'finished'")->fetchColumn();
$total_goals_real = $pdo->query("SELECT SUM(home_score + away_score) FROM matches WHERE status = 'finished'")->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Mundial 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .stat-card { border: none; border-radius: 15px; transition: transform 0.3s; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-bg { font-size: 2.5rem; opacity: 0.15; position: absolute; right: 15px; bottom: 10px; }
        .award-badge { background: #fff; border-radius: 12px; padding: 12px; border: 1px solid #edf2f7; transition: all 0.2s; }
        .award-badge:hover { border-color: #cbd5e0; background: #f8fafc; }
        .progress-custom { height: 10px; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">

<?php $current_page = 'stats'; include 'includes/navbar.php'; ?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold"><i class="bi bi-bar-chart-line text-primary"></i> Centro de Análisis</h1>
        <p class="text-muted">Desempeño detallado de <?php echo htmlspecialchars($user_name); ?></p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white shadow-sm p-3">
                <div class="small fw-bold opacity-75">PUNTOS TOTALES</div>
                <div class="display-5 fw-bold"><?php echo $total_acumulado; ?></div>
                <div class="small">Líder: <?php echo $max_points; ?> (Dif: -<?php echo $diff_leader; ?>)</div>
                <i class="bi bi-trophy-fill icon-bg"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white shadow-sm p-3">
                <div class="small fw-bold opacity-75">ACIERTOS EXACTOS</div>
                <div class="display-5 fw-bold"><?php echo $activity['exact'] ?? 0; ?></div>
                <div class="small">Efectividad: <?php echo $eff; ?>%</div>
                <i class="bi bi-bullseye icon-bg"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-dark text-white shadow-sm p-3">
                <div class="small fw-bold opacity-75">PRONÓSTICOS</div>
                <div class="display-5 fw-bold"><?php echo $activity['total'] ?? 0; ?></div>
                <div class="small">Partidos jugados: <?php echo $finished_matches; ?></div>
                <i class="bi bi-pencil-square icon-bg"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info text-dark shadow-sm p-3">
                <div class="small fw-bold opacity-75">RENDIMIENTO KO</div>
                <div class="display-5 fw-bold"><?php echo $risk_ratio; ?>%</div>
                <div class="small">Puntos en finales: <?php echo $knockout_pts; ?></div>
                <i class="bi bi-lightning-fill icon-bg"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-pie-chart me-2"></i>Origen de mis Puntos</div>
                <div class="card-body">
                    <canvas id="pointsChart" style="max-height: 280px;"></canvas>
                    <div class="row mt-4 text-center g-2">
                        <div class="col-3"><div class="badge bg-primary d-block py-2">Partidos: <?php echo $pts['m_pts']; ?></div></div>
                        <div class="col-3"><div class="badge bg-success d-block py-2">Grupos: <?php echo $pts['b_pts']; ?></div></div>
                        <div class="col-3"><div class="badge bg-warning text-dark d-block py-2">Quiz: <?php echo $pts['q_pts']; ?></div></div>
                        <div class="col-3"><div class="badge bg-purple d-block py-2" style="background:#6f42c1">Especial: <?php echo $pts['s_pts']; ?></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-award me-2"></i>Predicciones del Torneo</div>
                <div class="card-body">
                    <?php 
                    $stmt_ua = $pdo->prepare("SELECT * FROM user_bonus_predictions WHERE user_id = ?");
                    $stmt_ua->execute([$user_id]);
                    $ua = $stmt_ua->fetch(PDO::FETCH_ASSOC) ?: [];
                    ?>
                    <div class="d-flex flex-column gap-3">
                        <div class="award-badge d-flex align-items-center">
                            <div class="fs-2 me-3">🏆</div>
                            <div>
                                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Mi Campeón</div>
                                <div class="fw-bold">
                                    <?php if(isset($ua['champion_team_id']) && $ua['champion_team_id']): ?>
                                        <img src="assets/img/banderas/<?php echo strtolower($team_map[$ua['champion_team_id']]['f']); ?>.png" width="22" class="me-1 shadow-sm">
                                        <?php echo $team_map[$ua['champion_team_id']]['n']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No seleccionado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="award-badge d-flex align-items-center">
                            <div class="fs-2 me-3">👟</div>
                            <div>
                                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Bota de Oro</div>
                                <div class="fw-bold"><?php echo $cand_map[$ua['scorer_candidate_id'] ?? 0] ?? '<span class="text-muted">No seleccionado</span>'; ?></div>
                            </div>
                        </div>
                        <div class="award-badge d-flex align-items-center">
                            <div class="fs-2 me-3">🛡️</div>
                            <div>
                                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Guante de Oro</div>
                                <div class="fw-bold"><?php echo $cand_map[$ua['keeper_candidate_id'] ?? 0] ?? '<span class="text-muted">No seleccionado</span>'; ?></div>
                            </div>
                        </div>
                        <div class="award-badge d-flex align-items-center border-primary shadow-sm" style="border-left: 4px solid #0d6efd !important;">
                            <div class="fs-2 me-3 text-primary">⚽</div>
                            <div>
                                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Goles Totales Pronosticados</div>
                                <div class="fw-bold fs-5">
                                    <?php echo $ua['total_goals_prediction'] ?? '<span class="text-muted">--</span>'; ?>
                                    <small class="text-muted fw-normal" style="font-size:0.8rem;">(Actual: <?php echo $total_goals_real; ?>)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 p-4 bg-dark text-white rounded-4 shadow-lg">
        <div class="row text-center align-items-center">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="h1 fw-bold text-warning mb-0"><?php echo $total_goals_real; ?></div>
                <div class="small opacity-75 fw-bold">GOLES MARCADOS</div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0 border-start border-secondary">
                <div class="h2 fw-bold text-warning mb-0"><?php echo ($finished_matches > 0) ? round($total_goals_real/$finished_matches, 2) : 0; ?></div>
                <div class="small opacity-75 fw-bold">PROMEDIO GOLES/PARTIDO</div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0 border-start border-secondary">
                <div class="h2 fw-bold mb-0"><?php echo $finished_matches; ?></div>
                <div class="small opacity-75 fw-bold">PARTIDOS FINALIZADOS</div>
            </div>
            <div class="col-md-3">
                <div class="h2 fw-bold mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(); ?></div>
                <div class="small opacity-75 fw-bold">COMPETIDORES ACTIVOS</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('pointsChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Partidos', 'Grupos', 'Quiz', 'Especial'],
            datasets: [{
                data: [<?php echo $pts['m_pts'].','.$pts['b_pts'].','.$pts['q_pts'].','.$pts['s_pts']; ?>],
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#6f42c1'],
                hoverOffset: 15,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
            },
            cutout: '70%'
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>