<?php
// admin/calculate_bonus_elections.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado.");
}

$output = "<h2>Cálculo de Puntos de Bonus (Final)</h2>";
$points_per_award = 100;
$dummy_group_name = 'Z'; 

try {
    // 1. OBTENER GANADORES OFICIALES (Goleador/Portero)
    $stmt_winners = $pdo->query("SELECT id, type FROM bonus_candidates WHERE is_winner = TRUE");
    $winners = $stmt_winners->fetchAll(PDO::FETCH_KEY_PAIR);
    $scorer_winner_id = array_search('scorer', $winners);
    $keeper_winner_id = array_search('keeper', $winners);
    
    // 2. OBTENER RESULTADOS OFICIALES GLOBALES (Goles/Campeón)
    $stmt_final = $pdo->query("SELECT final_total_goals, champion_team_id FROM tournament_results WHERE id = 1");
    $final_results = $stmt_final->fetch(PDO::FETCH_ASSOC);

    if (!$scorer_winner_id || !$keeper_winner_id || !$final_results['final_total_goals'] || !$final_results['champion_team_id']) {
        $output .= "<p class='alert alert-danger'>Aviso: Faltan resultados oficiales por marcar (Goleador, Portero, Campeón o Goles Totales).</p>";
        throw new Exception("Faltan resultados oficiales.");
    }
    
    $official_total_goals = (int)$final_results['final_total_goals'];
    $official_champion_id = (int)$final_results['champion_team_id'];


    // 3. OBTENER TODAS LAS PREDICCIONES DE LOS USUARIOS
    $stmt_preds = $pdo->query("SELECT user_id, scorer_candidate_id, keeper_candidate_id, total_goals_prediction, champion_team_id FROM user_bonus_predictions");
    $predictions = $stmt_preds->fetchAll(PDO::FETCH_ASSOC);


    // 4. COMPARAR Y CALCULAR PUNTOS
    $users_results = [];
    foreach ($predictions as $pred) {
        $user_id = $pred['user_id'];
        $points_awarded = 0;
        
        // A. Máximo Goleador (100 pts)
        if ($scorer_winner_id && (int)$pred['scorer_candidate_id'] === (int)$scorer_winner_id) {
            $points_awarded += 100;
        }
        
        // B. Mejor Portero (100 pts)
        if ($keeper_winner_id && (int)$pred['keeper_candidate_id'] === (int)$keeper_winner_id) {
            $points_awarded += 100;
        }
        
        // C. Campeón del Torneo (150 pts)
        if ((int)$pred['champion_team_id'] === $official_champion_id) {
            $points_awarded += 150;
        }
        
        // D. Total de Goles (25 pts, margen de +/- 5)
        $pred_goals = (int)$pred['total_goals_prediction'];
        if ($pred_goals >= ($official_total_goals - 5) && $pred_goals <= ($official_total_goals + 5)) {
            $points_awarded += 25;
        }

        if ($points_awarded > 0) {
            $users_results[$user_id] = $points_awarded;
        }
    }


    // 5. GUARDAR PUNTOS EN LA TABLA DE BONUS (Grupo ficticio 'Z')
    $stmt_name = $pdo->prepare("SELECT nombre FROM users WHERE id = ?");
    $sql_save = "INSERT INTO group_ranking_points (user_id, group_name, points_awarded) 
                 VALUES (:uid, :gn, :pts)
                 ON DUPLICATE KEY UPDATE points_awarded = :pts_update";
    $stmt_save = $pdo->prepare($sql_save);

    foreach ($users_results as $user_id => $points_awarded) {
        $stmt_name->execute([$user_id]);
        $user_name = $stmt_name->fetchColumn();

        $stmt_save->execute([
            'uid' => $user_id, 
            'gn' => $dummy_group_name, // 'Z'
            'pts' => $points_awarded, 
            'pts_update' => $points_awarded
        ]);
        $output .= "<p class='text-success'>✅ [".$user_name."] Bonificación Final: **$points_awarded PUNTOS**.</p>";
    }
    
} catch (Exception $e) {
    if ($e->getMessage() !== "Faltan resultados oficiales.") {
        $output .= "<p class='alert alert-danger'>Error: " . $e->getMessage() . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cálculo de Bonus Final</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <a href="index.php" class="btn btn-secondary mb-4">Volver al Dashboard</a>
    <?php echo $output; ?>
    <p class="alert alert-info">Recuerda que debes ejecutar este script una vez marcados los ganadores en el Panel Admin.</p>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>