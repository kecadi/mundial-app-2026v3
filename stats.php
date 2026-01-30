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
        :root { --primary-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
        .fw-black { font-weight: 900; }
        .stat-card { border: none; border-radius: 20px; transition: all 0.3s ease; position: relative; overflow: hidden; color: white; }
        .stat-card:hover { transform: translateY(-7px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .icon-bg { font-size: 5rem; opacity: 0.1; position: absolute; right: -10px; bottom: -10px; transform: rotate(-15deg); }
        .award-badge { background: #fff; border-radius: 18px; padding: 15px; border: 1px solid #f0f0f0; transition: all 0.3s; margin-bottom: 12px; }
        .award-badge:hover { border-color: #3b82f6; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .chart-container { position: relative; height: 300px; }
        .stat-pill { padding: 8px 15px; border-radius: 50px; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 5px; }
    </style>
</head>
<body class="bg-light">

<?php $current_page = 'stats'; include 'includes/navbar.php'; ?>

<div class="container my-5">
    <div class="p-5 mb-5 rounded-5 shadow-sm text-white" style="background: var(--primary-gradient);">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h6 class="text-uppercase fw-bold opacity-75 letter-spacing-1 mb-2">Análisis de Rendimiento</h6>
                <h1 class="display-4 fw-black mb-0"><?php echo htmlspecialchars($user_name); ?></h1>
                <p class="lead opacity-75 mt-2">Descubre tus fortalezas y debilidades en esta Copa del Mundo.</p>
            </div>
            <div class="col-md-5 text-md-end mt-4 mt-md-0">
                <div class="d-inline-block p-4 bg-white bg-opacity-10 rounded-4 backdrop-blur">
                    <span class="small d-block opacity-75">DISTANCIA AL LÍDER</span>
                    <span class="h2 fw-black mb-0">-<?php echo $diff_leader; ?> PTS</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100 p-3 shadow-sm" style="background: #0d6efd;">
                <div class="small fw-bold opacity-75">PUNTOS</div>
                <div class="display-6 fw-black"><?php echo $total_acumulado; ?></div>
                <div class="small mt-auto">#<?php echo $finished_matches; ?> partidos</div>
                <i class="bi bi-trophy-fill icon-bg"></i>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100 p-3 shadow-sm" style="background: #198754;">
                <div class="small fw-bold opacity-75">EFECTIVIDAD</div>
                <div class="display-6 fw-black"><?php echo $eff; ?>%</div>
                <div class="small mt-auto"><?php echo $activity['exact']; ?> Plenos</div>
                <i class="bi bi-bullseye icon-bg"></i>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100 p-3 shadow-sm" style="background: #6f42c1;">
                <div class="small fw-bold opacity-75">RIESGO KO</div>
                <div class="display-6 fw-black"><?php echo $risk_ratio; ?>%</div>
                <div class="small mt-auto"><?php echo $knockout_pts; ?> pts finales</div>
                <i class="bi bi-lightning-charge-fill icon-bg"></i>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100 p-3 shadow-sm" style="background: #1a1a1a;">
                <div class="small fw-bold opacity-75">TOTAL GOLES</div>
                <div class="display-6 fw-black"><?php echo $total_goals_real; ?></div>
                <div class="small mt-auto">Prom: <?php echo ($finished_matches > 0) ? round($total_goals_real/$finished_matches, 2) : 0; ?>/p</div>
                <i class="bi bi-soccer-ball icon-bg"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-4 px-4 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Distribución de Puntos</h5>
                </div>
                <div class="card-body px-4 pb-4 text-center">
                    <div class="chart-container">
                        <canvas id="pointsChart"></canvas>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        <div class="stat-pill bg-primary-subtle text-primary"><i class="bi bi-p-circle-fill"></i> Partidos: <?php echo $pts['m_pts']; ?></div>
                        <div class="stat-pill bg-success-subtle text-success"><i class="bi bi-grid-3x3-gap-fill"></i> Grupos: <?php echo $pts['b_pts']; ?></div>
                        <div class="stat-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-brain"></i> Quiz: <?php echo $pts['q_pts']; ?></div>
                        <div class="stat-pill bg-info-subtle text-info"><i class="bi bi-star-fill"></i> Bonus: <?php echo $pts['s_pts']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-4 px-4 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-shield-check text-primary me-2"></i>Tus Predicciones Master</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php 
                    $stmt_ua = $pdo->prepare("SELECT * FROM user_bonus_predictions WHERE user_id = ?");
                    $stmt_ua->execute([$user_id]);
                    $ua = $stmt_ua->fetch(PDO::FETCH_ASSOC) ?: [];
                    ?>
                    
                    <div class="award-badge d-flex align-items-center">
                        <div class="h3 mb-0 me-3">🏆</div>
                        <div>
                            <span class="d-block small text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Campeón Elegido</span>
                            <span class="fw-bold">
                                <?php if(isset($ua['champion_team_id']) && $ua['champion_team_id']): ?>
                                    <img src="assets/img/banderas/<?php echo strtolower($team_map[$ua['champion_team_id']]['f']); ?>.png" width="20" class="me-1 rounded-1">
                                    <?php echo $team_map[$ua['champion_team_id']]['n']; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Sin definir</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="award-badge d-flex align-items-center">
                        <div class="h3 mb-0 me-3">👟</div>
                        <div>
                            <span class="d-block small text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Bota de Oro</span>
                            <span class="fw-bold"><?php echo $cand_map[$ua['scorer_candidate_id'] ?? 0] ?? '<span class="text-muted small">Sin definir</span>'; ?></span>
                        </div>
                    </div>

                    <div class="award-badge d-flex align-items-center">
                        <div class="h3 mb-0 me-3">🛡️</div>
                        <div>
                            <span class="d-block small text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Guante de Oro</span>
                            <span class="fw-bold"><?php echo $cand_map[$ua['keeper_candidate_id'] ?? 0] ?? '<span class="text-muted small">Sin definir</span>'; ?></span>
                        </div>
                    </div>

                    <div class="award-badge d-flex align-items-center bg-light border-0">
                        <div class="h3 mb-0 me-3 text-primary">⚽</div>
                        <div>
                            <span class="d-block small text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Pronóstico Goles Totales</span>
                            <span class="h5 fw-black mb-0">
                                <?php echo $ua['total_goals_prediction'] ?? '--'; ?>
                                <small class="text-muted fw-normal" style="font-size:0.75rem;">(Real: <?php echo $total_goals_real; ?>)</small>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 p-4 rounded-5 shadow-lg bg-dark text-white border-0 position-relative overflow-hidden">
        <div class="row text-center position-relative" style="z-index: 2;">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="h1 fw-black text-warning mb-0"><?php echo round(($finished_matches/104)*100); ?>%</div>
                <div class="small opacity-50 text-uppercase fw-bold">Competición Completada</div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0 border-start border-secondary">
                <div class="h1 fw-black text-warning mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(); ?></div>
                <div class="small opacity-50 text-uppercase fw-bold">Jugadores Activos</div>
            </div>
            <div class="col-md-4 border-start border-secondary">
                <div class="h1 fw-black text-warning mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM predictions")->fetchColumn(); ?></div>
                <div class="small opacity-50 text-uppercase fw-bold">Predicciones Totales</div>
            </div>
        </div>
        <i class="bi bi-globe-americas position-absolute end-0 top-50 translate-middle-y opacity-10" style="font-size: 15rem; right: -2rem !important;"></i>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('pointsChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Partidos', 'Grupos', 'Quiz', 'Bonus'],
            datasets: [{
                data: [<?php echo $pts['m_pts'].','.$pts['b_pts'].','.$pts['q_pts'].','.$pts['s_pts']; ?>],
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#6f42c1'],
                hoverOffset: 20,
                borderWidth: 0,
                borderRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>