<?php
// admin/index.php
session_start();
require_once '../config/db.php'; 

// 1. Seguridad
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// 2. Obtener partidos con todos los datos necesarios
$sql = "SELECT m.*, 
               m.team_home_id, m.team_away_id, 
               t1.name as home_name, t1.flag as home_flag, 
               t2.name as away_name, t2.flag as away_flag,
               t1.group_name 
        FROM matches m
        JOIN teams t1 ON m.team_home_id = t1.id
        JOIN teams t2 ON m.team_away_id = t2.id
        ORDER BY m.match_date ASC";
$stmt = $pdo->query($sql);
$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- OBTENER RESUMEN GLOBAL ---
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'user'")->fetchColumn();
$stats['total_matches'] = $pdo->query("SELECT COUNT(id) FROM matches")->fetchColumn();
$stats['total_predictions'] = $pdo->query("SELECT COUNT(id) FROM predictions")->fetchColumn();

// Calcular % de predicciones completadas
$stats['pred_percent'] = ($stats['total_matches'] * $stats['total_users'] > 0) 
    ? round($stats['total_predictions'] / ($stats['total_matches'] * $stats['total_users']) * 100) 
    : 0;

// --- ESTADÍSTICAS DE RESULTADOS REALES (1X2) ---
$stmt_real_results = $pdo->query("SELECT
    COUNT(CASE WHEN home_score > away_score THEN 1 END) AS count_home,
    COUNT(CASE WHEN home_score = away_score THEN 1 END) AS count_draw,
    COUNT(CASE WHEN home_score < away_score THEN 1 END) AS count_away
FROM matches
WHERE status = 'finished'");
$real_results = $stmt_real_results->fetch(PDO::FETCH_ASSOC);

$total_closed_matches = $real_results['count_home'] + $real_results['count_draw'] + $real_results['count_away'];

// Calcular porcentajes
$stats['pct_home'] = ($total_closed_matches > 0) ? round(($real_results['count_home'] / $total_closed_matches) * 100) : 0;
$stats['pct_draw'] = ($total_closed_matches > 0) ? round(($real_results['count_draw'] / $total_closed_matches) * 100) : 0;
$stats['pct_away'] = ($total_closed_matches > 0) ? round(($real_results['count_away'] / $total_closed_matches) * 100) : 0;

// 3. LÓGICA DE FIJACIÓN DEL ÚLTIMO PARTIDO DEL GRUPO
$last_match_id_by_group = [];
$current_group = null;
$last_match_id = null;

foreach ($partidos as $match) {
    if ($match['group_name'] !== $current_group) {
        if ($current_group !== null) {
            $last_match_id_by_group[$current_group] = $last_match_id;
        }
        $current_group = $match['group_name'];
    }
    $last_match_id = $match['id'];
}
if ($current_group !== null) {
    $last_match_id_by_group[$current_group] = $last_match_id;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Resultados</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .table-group th, .table-group td { font-size: 0.85rem; padding: 0.5rem 0.2rem; }
        .bar-segment { height: 100%; text-align: center; color: white; font-weight: bold; }
        .flag-admin { width: 25px; height: auto; border-radius: 4px; border: 1px solid rgba(0,0,0,0.1); vertical-align: middle; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'matches'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-4">
    <h2 class="mb-4">Gestionar Resultados</h2>
    
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-white shadow-sm text-center">
                <div class="card-body">
                    <p class="mb-1 text-muted small uppercase fw-bold">Participantes</p>
                    <h4 class="fw-bold m-0"><?php echo $stats['total_users']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-white shadow-sm text-center">
                <div class="card-body">
                    <p class="mb-1 text-muted small uppercase fw-bold">Partidos</p>
                    <h4 class="fw-bold m-0"><?php echo $stats['total_matches']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-white shadow-sm text-center">
                <div class="card-body">
                    <p class="mb-1 text-muted small uppercase fw-bold">Predicciones</p>
                    <h4 class="fw-bold text-primary m-0"><?php echo $stats['total_predictions']; ?> <small class="text-muted" style="font-size: 0.8rem;">(<?php echo $stats['pred_percent']; ?>%)</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold">🔮 Predictibilidad Real del Torneo</div>
                <div class="card-body">
                    <div class="d-flex shadow-sm" style="height: 25px; border-radius: 5px; overflow: hidden;">
                        <div class="bar-segment bg-success" style="width: <?php echo $stats['pct_home']; ?>%; line-height: 25px; font-size: 0.75rem;">
                            1 (<?php echo $stats['pct_home']; ?>%)
                        </div>
                        <div class="bar-segment bg-secondary" style="width: <?php echo $stats['pct_draw']; ?>%; line-height: 25px; font-size: 0.75rem;">
                            X (<?php echo $stats['pct_draw']; ?>%)
                        </div>
                        <div class="bar-segment bg-danger" style="width: <?php echo $stats['pct_away']; ?>%; line-height: 25px; font-size: 0.75rem;">
                            2 (<?php echo $stats['pct_away']; ?>%)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">¡Actualizado con éxito!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-group">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Fecha/Gpo</th>
                        <th>Encuentro</th>
                        <th class="text-center">Marcador</th>
                        <th>Clasificado</th>
                        <th class="pe-3">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($partidos as $p): 
                        $es_eliminatoria = $p['phase'] !== 'group';
                        $es_ultimo_del_grupo = ($p['id'] == ($last_match_id_by_group[$p['group_name']] ?? null));
                        
                        // Rutas de banderas
                        $ruta_home = "../assets/img/banderas/" . $p['home_flag'] . ".png";
                        $ruta_away = "../assets/img/banderas/" . $p['away_flag'] . ".png";
                    ?>
                    <form action="calculate.php" method="POST">
                        <input type="hidden" name="match_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="match_phase" value="<?php echo $p['phase']; ?>">
                        <tr>
                            <td class="ps-3">
                                <span class="fw-bold"><?php echo date('d/m', strtotime($p['match_date'])); ?></span>
                                <small class="d-block text-muted">Gpo. <?php echo $p['group_name']; ?></small>
                            </td>
                            <td>
                                <div class="mb-1">
                                    <?php if(file_exists($ruta_home)): ?>
                                        <img src="<?php echo $ruta_home; ?>" class="flag-admin me-1">
                                    <?php endif; ?>
                                    <?php echo $p['home_name']; ?>
                                </div>
                                <div>
                                    <?php if(file_exists($ruta_away)): ?>
                                        <img src="<?php echo $ruta_away; ?>" class="flag-admin me-1">
                                    <?php endif; ?>
                                    <?php echo $p['away_name']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <input type="number" name="real_home" class="form-control form-control-sm text-center fw-bold" style="width: 50px;" value="<?php echo $p['home_score']; ?>" min="0">
                                    <span class="fw-bold">:</span>
                                    <input type="number" name="real_away" class="form-control form-control-sm text-center fw-bold" style="width: 50px;" value="<?php echo $p['away_score']; ?>" min="0">
                                </div>
                            </td>
                            <td>
                                <?php if ($es_eliminatoria): ?>
                                    <select name="real_qualifier_id" class="form-select form-select-sm">
                                        <option value="">-- Ganador --</option>
                                        <option value="<?php echo $p['team_home_id']; ?>" <?php echo ($p['real_qualifier_id'] == $p['team_home_id']) ? 'selected' : ''; ?>><?php echo $p['home_name']; ?></option>
                                        <option value="<?php echo $p['team_away_id']; ?>" <?php echo ($p['real_qualifier_id'] == $p['team_away_id']) ? 'selected' : ''; ?>><?php echo $p['away_name']; ?></option>
                                    </select>
                                <?php else: ?>
                                    <span class="text-muted italic">Fase Grupos</span>
                                    <input type="hidden" name="real_qualifier_id" value="">
                                <?php endif; ?>
                            </td>
                            <td class="pe-3">
                                <button type="submit" class="btn btn-sm w-100 <?php echo ($p['status'] === 'finished') ? 'btn-warning' : 'btn-success'; ?>">
                                    <?php echo ($p['status'] === 'finished') ? 'Recalcular' : 'Cerrar'; ?>
                                </button>

                                <?php if ($es_ultimo_del_grupo && $p['status'] === 'finished' && !$es_eliminatoria): ?>
                                    <a href="calculate_group_bonus.php?group=<?php echo $p['group_name']; ?>" class="btn btn-info btn-sm w-100 mt-1 text-white fw-bold">Bonus Gpo <?php echo $p['group_name']; ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </form>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>