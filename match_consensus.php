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

// 2. Calcular el consenso (Contar 1, X, 2 usando CASE WHEN)
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

// Ajustar el porcentaje de forma visual (para que sume 100 en la barra)
$visual_total = $percent['home'] + $percent['draw'] + $percent['away'];
if ($visual_total < 100) {
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
        .consensus-bar { height: 35px; display: flex; border-radius: 0.5rem; overflow: hidden; }
        .bar-segment { height: 100%; text-align: center; color: white; line-height: 35px; font-weight: bold; transition: width 0.5s; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'match'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4">Consenso de Predicciones</h2>
    <h3 class="text-primary mb-3">
        <?php echo htmlspecialchars($match_info['home']); ?> vs <?php echo htmlspecialchars($match_info['away']); ?>
    </h3>
    <p class="text-muted"><?php echo date('D d M H:i', strtotime($match_info['match_date'])); ?> | <?php echo htmlspecialchars($match_info['stadium']); ?></p>

    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <h5 class="mb-4">Balance General (Total Predicciones: <?php echo $total_preds; ?>)</h5>
            <div class="consensus-bar shadow-sm">
                <div class="bar-segment bg-danger" style="width: <?php echo $percent['home']; ?>%;">
                    <?php echo $percent['home']; ?>% (Local)
                </div>
                <div class="bar-segment bg-secondary" style="width: <?php echo $percent['draw']; ?>%;">
                    <?php echo $percent['draw']; ?>% (Empate)
                </div>
                <div class="bar-segment bg-success" style="width: <?php echo $percent['away']; ?>%;">
                    <?php echo $percent['away']; ?>% (Visitante)
                </div>
            </div>
            
            <?php if($total_preds === 0): ?>
                <div class="alert alert-warning mt-3">Aún no hay predicciones para este partido.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="index.php" class="btn btn-secondary">Volver al Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>