<?php
// admin/calculate_group_bonus.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php'; 

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado.");
}

$group_to_process = $_GET['group'] ?? NULL;

if (!$group_to_process) {
    header('Location: index.php');
    exit;
}

$output = "";
$error_message = "";

try {
    // 1. OBTENER TODOS LOS DATOS NECESARIOS (filtrando sólo el grupo solicitado)

    // a) Equipos del grupo
    $stmt = $pdo->prepare("SELECT id, name, flag FROM teams WHERE group_name = :gn ORDER BY id");
    $stmt->execute(['gn' => $group_to_process]);
    $group_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($group_teams) < 4) {
        $error_message = "Grupo incompleto o no existe.";
        throw new Exception($error_message);
    }
    
    // b) Partidos reales
    $stmt = $pdo->prepare("SELECT team_home_id, team_away_id, home_score, away_score, group_name 
                         FROM matches 
                         JOIN teams ON matches.team_home_id = teams.id 
                         WHERE phase = 'group' AND group_name = :gn");
    $stmt->execute(['gn' => $group_to_process]);
    $real_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // c) Obtener todos los usuarios jugadores
    $users = $pdo->query("SELECT id, nombre FROM users WHERE role = 'user'")->fetchAll(PDO::FETCH_ASSOC);

    // d) Obtener TODAS las predicciones
    $stmt = $pdo->prepare("SELECT p.user_id, m.team_home_id, m.team_away_id, 
                               p.predicted_home_score, p.predicted_away_score, t.group_name
                        FROM predictions p
                        JOIN matches m ON p.match_id = m.id
                        JOIN teams t ON m.team_home_id = t.id
                        WHERE m.phase = 'group' AND group_name = :gn");
    $stmt->execute(['gn' => $group_to_process]);
    $all_predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // 2. VERIFICACIÓN: ¿GRUPO FINALIZADO?
    $total_matches_in_group = 6; 
    
    if (count($real_matches) < $total_matches_in_group) {
        $error_message = "Faltan partidos en la tabla 'matches' (Esperados 6, encontrados " . count($real_matches) . ").";
        throw new Exception($error_message);
    } 
    foreach($real_matches as $m) {
        if (is_null($m['home_score'])) {
            $error_message = "Faltan resultados reales para este grupo.";
            throw new Exception($error_message);
        }
    }


    // 3. CALCULAR TABLA REAL (Clasificados oficiales)
    $tabla_real = calcularTablaGrupo($group_teams, $real_matches);
    $real_1st = (int)$tabla_real[0]['id'];
    $real_2nd = (int)$tabla_real[1]['id'];
    $qualifiers_real = [$real_1st, $real_2nd];
    

    // 4. CALCULAR PUNTOS PARA CADA USUARIO
    foreach ($users as $user) {
        $user_id = $user['id'];
        $user_name = $user['nombre'];
        $total_points = 0;

        // Filtrar predicciones del usuario (solo se necesitan para el conteo de partidos)
        $user_predictions = array_filter($all_predictions, function($p) use ($user_id) {
            return $p['user_id'] == $user_id;
        });

        // Si el usuario no predijo todos los partidos (6), no puede tener bonificación
        if (count($user_predictions) < $total_matches_in_group) {
             continue; 
        }

        // CALCULAR TABLA DEL USUARIO (basada en sus predicciones de scores)
        $tabla_predicha = calcularTablaGrupo($group_teams, $user_predictions);
        $pred_1st = (int)$tabla_predicha[0]['id'];
        $pred_2nd = (int)$tabla_predicha[1]['id'];
        $qualifiers_pred = [$pred_1st, $pred_2nd];

        
        // 5. APLICAR REGLAS DE PUNTUACIÓN DE CLASIFICACIÓN (No acumulables)
        
        $aciertos_posibles = [];

        // 5a. Acierto exacto de 1º y 2º (90 puntos)
        if ($pred_1st === $real_1st && $pred_2nd === $real_2nd) {
            $aciertos_posibles[] = 90;
        } 
        
        // 5b. Acierto clasificados sin acertar el orden (40 puntos)
        if (count(array_intersect($qualifiers_real, $qualifiers_pred)) === 2) {
            $aciertos_posibles[] = 40;
        } 
        
        // 5c. Acierto individual (25 pts por el 1º o 2º)
        if ($pred_1st === $real_1st || $pred_2nd === $real_2nd) {
            $aciertos_posibles[] = 25; // Si acierta uno o los dos, el máximo es 25 (o 40/90)
        }
        
        // 5d. Acierto de Un clasificado (sin orden) (20 puntos)
        if (count(array_intersect($qualifiers_real, $qualifiers_pred)) === 1) {
            $aciertos_posibles[] = 20;
        }

        // Seleccionar la puntuación más alta
        $total_points = empty($aciertos_posibles) ? 0 : max($aciertos_posibles);
        
        
        // 6. GUARDAR LOS PUNTOS
        if ($total_points > 0) {
             $sql_save = "INSERT INTO group_ranking_points (user_id, group_name, points_awarded) 
                          VALUES (:uid, :gn, :pts)
                          ON DUPLICATE KEY UPDATE points_awarded = :pts_update";
             $stmt_save = $pdo->prepare($sql_save);
             $stmt_save->execute([
                 'uid' => $user_id, 
                 'gn' => $group_to_process, 
                 'pts' => $total_points, 
                 'pts_update' => $total_points
             ]);
             $output .= "<p class='text-success'>[".$user_name."] Puntos asignados: **$total_points**</p>";
        }
    }
    
    // Redirigir al admin con mensaje de éxito
    header('Location: index.php?group_msg=' . $group_to_process);
    exit;
    
} catch (Exception $e) {
    // Si hay un error, lo mostramos o redirigimos con un error
    if (empty($error_message)) $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cálculo de Bonificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <a href="index.php" class="btn btn-secondary mb-4">Volver al Admin</a>
    <?php if ($output): ?>
        <h2 class="mb-4">Resultados del Cálculo</h2>
        <?php echo $output; ?>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <p class="alert alert-danger">Error al procesar el grupo: <?php echo $error_message; ?></p>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>