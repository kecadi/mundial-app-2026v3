<?php
// match_consensus.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || !isset($_GET['match_id'])) {
    header('Location: index.php');
    exit;
}

$match_id = (int)$_GET['match_id'];

// 1. Obtener información básica del partido
$stmt_match = $pdo->prepare("SELECT 
    t1.name AS home, t2.name AS away, m.match_date, m.stadium 
FROM matches m
JOIN teams t1 ON m.team_home_id = t1.id
JOIN teams t2 ON m.team_away_id = t2.id
WHERE m.id = ?");
$stmt_match->execute([$match_id]);
$match_info = $stmt_match->fetch(PDO::FETCH_ASSOC);

if (!$match_info) {
    die("Partido no encontrado.");
}

// 2. Calcular el consenso
$stmt_consensus = $pdo->prepare("SELECT 
    COUNT(CASE WHEN predicted_home_score > predicted_away_score THEN 1 END) AS count_home,
    COUNT(CASE WHEN predicted_home_score = predicted_away_score THEN 1 END) AS count_draw,
    COUNT(CASE WHEN predicted_home_score < predicted_away_score THEN 1 END) AS count_away
FROM predictions
WHERE match_id = ?");
$stmt_consensus->execute([$match_id]);
$counts = $stmt_consensus->fetch(PDO::FETCH_ASSOC);

$total_preds = $counts['count_home'] + $counts['count_draw'] + $counts['count_away'];

// 3. Calcular Porcentajes
$percent = [
    'home' => ($total_preds > 0) ? round(($counts['count_home'] / $total_preds) * 100) : 0,
    'draw' => ($total_preds > 0) ? round(($counts['count_draw'] / $total_preds) * 100) : 0,
    'away' => ($total_preds > 0) ? round(($counts['count_away'] / $total_preds) * 100) : 0,
];

// Ajuste visual para que sume 100%
$visual_total = $percent['home'] + $percent['draw'] + $percent['away'];
if ($total_preds > 0 && $visual_total != 100) {
    $percent['home'] += (100 - $visual_total); 
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consenso - <?php echo htmlspecialchars($match_info['home']); ?> vs <?php echo htmlspecialchars($match_info['away']); ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .consensus-bar { height: 45px; display: flex; border-radius: 12px; overflow: hidden; }
        .bar-segment { height: 100%; text-align: center; color: white; display: flex; flex-direction: column; justify-content: center; font-weight: bold; transition: width 0.5s; font-size: 0.9rem; }
        .bar-label { font-size: 0.7rem; opacity: 0.9; text-transform: uppercase; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'match'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Consenso Comunidad</li>
              </ol>
            </nav>

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-primary text-white p-4">
                    <h2 class="mb-1 fw-bold">Consenso de Predicciones</h2>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-calendar3 me-1"></i> <?php echo date('d M, H:i', strtotime($match_info['match_date'])); ?> 
                        <span class="mx-2">|</span> 
                        <i class="bi bi-geo-alt-fill me-1"></i> <?php echo htmlspecialchars($match_info['stadium']); ?>
                    </p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-5">
                        <h3 class="fw-bold display-6">
                            <?php echo htmlspecialchars($match_info['home']); ?> 
                            <span class="text-muted mx-3">vs</span> 
                            <?php echo htmlspecialchars($match_info['away']); ?>
                        </h3>
                    </div>

                    <h5 class="mb-4 fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Balance General (<?php echo $total_preds; ?> votos)</h5>
                    
                    <?php if($total_preds > 0): ?>
                    <div class="consensus-bar shadow-sm mb-3">
                        <div class="bar-segment bg-primary" style="width: <?php echo $percent['home']; ?>%;">
                            <span><?php echo $percent['home']; ?>%</span>
                            <span class="bar-label">Local</span>
                        </div>
                        <div class="bar-segment bg-secondary" style="width: <?php echo $percent['draw']; ?>%;">
                            <span><?php echo $percent['draw']; ?>%</span>
                            <span class="bar-label">Empate</span>
                        </div>
                        <div class="bar-segment bg-danger" style="width: <?php echo $percent['away']; ?>%;">
                            <span><?php echo $percent['away']; ?>%</span>
                            <span class="bar-label">Visitante</span>
                        </div>
                    </div>
                    
                    <div class="row text-center mt-4">
                        <div class="col-4">
                            <div class="p-2 rounded-3 bg-primary bg-opacity-10">
                                <h4 class="fw-bold text-primary mb-0"><?php echo $counts['count_home']; ?></h4>
                                <small class="text-muted">Apuestan al Local</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 bg-secondary bg-opacity-10">
                                <h4 class="fw-bold text-secondary mb-0"><?php echo $counts['count_draw']; ?></h4>
                                <small class="text-muted">Apuestan al Empate</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 bg-danger bg-opacity-10">
                                <h4 class="fw-bold text-danger mb-0"><?php echo $counts['count_away']; ?></h4>
                                <small class="text-muted">Apuestan al Visitante</small>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted opacity-50"></i>
                            <p class="mt-3 mb-0">Aún no hay predicciones para este partido.<br>¡Sé el primero en apostar!</p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-5 text-center">
                        <a href="index.php" class="btn btn-outline-secondary px-4 rounded-pill">
                            <i class="bi bi-arrow-left me-2"></i>Volver al Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>