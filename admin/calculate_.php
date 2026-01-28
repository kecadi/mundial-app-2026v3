<?php
// admin/calculate.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capturar datos y sanitizar
    $match_id = $_POST['match_id'];
    $real_home = (int)$_POST['real_home'];
    $real_away = (int)$_POST['real_away'];
    $match_phase = $_POST['match_phase'];
    
    // Capturar Clasificado Real (puede ser NULL)
    $real_qualifier_id = $_POST['real_qualifier_id'] ?? NULL;
    if ($real_qualifier_id === '') $real_qualifier_id = NULL;

    try {
        // 2. ACTUALIZAR EL PARTIDO EN LA BASE DE DATOS
        $sql_update = "UPDATE matches SET 
                       home_score = :rh, 
                       away_score = :ra, 
                       status = 'finished',
                       real_qualifier_id = :rqid 
                       WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'rh' => $real_home, 
            'ra' => $real_away, 
            'rqid' => $real_qualifier_id, 
            'id' => $match_id
        ]);

        // 3. FETCH DE INFO DEL PARTIDO (Necesario para la inferencia de IDs y Log)
        $stmt_info = $pdo->prepare("SELECT team_home_id, team_away_id FROM matches WHERE id = :mid");
        $stmt_info->execute(['mid' => $match_id]);
        $match_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        $home_id = $match_info['team_home_id'];
        $away_id = $match_info['team_away_id'];


        // 4. OBTENER PREDICCIONES Y DEFINIR PUNTOS
        $stmt_preds = $pdo->prepare("SELECT * FROM predictions WHERE match_id = :mid");
        $stmt_preds->execute(['mid' => $match_id]);
        $predictions = $stmt_preds->fetchAll(PDO::FETCH_ASSOC);
        
        $is_knockout = ($match_phase !== 'group');
        $is_real_draw = ($real_home === $real_away);
        
        $exact_pts = $is_knockout ? 30 : 25;
        $winner_pts = $is_knockout ? 25 : 15;
        $goals_pts = $is_knockout ? 10 : 5;

        // Sentencia para buscar el comodín (se ejecuta dentro del bucle)
        $stmt_wc = $pdo->prepare("SELECT wildcard_used_match_id FROM users WHERE id = ?");

        // 5. CALCULAR PUNTOS USUARIO POR USUARIO
        foreach ($predictions as $pred) {
            $user_id = $pred['user_id'];
            $p_home = (int)$pred['predicted_home_score'];
            $p_away = (int)$pred['predicted_away_score'];
            $p_qualifier_id = $pred['predicted_qualifier_id'];
            $points = 0;
            
            // --- CÁLCULO DE ACIERTOS BASE ---
            
            $earned_exact = ($p_home === $real_home && $p_away === $real_away);

            // 5a. INFERENCIA DE CLASIFICADO PREDICHO
            $predicted_winner_id = NULL;
            if ($p_qualifier_id) {
                $predicted_winner_id = $p_qualifier_id;
            } elseif ($p_home > $p_away) {
                $predicted_winner_id = $home_id;
            } elseif ($p_home < $p_away) {
                $predicted_winner_id = $away_id;
            }

            // 5b. Puntos por Clasificado/Ganador (15/25 pts)
            $pts_winner = 0;
            if ($is_knockout) {
                // Knockout: Usamos el ID inferido (o manual)
                if ($predicted_winner_id && $real_qualifier_id && (int)$predicted_winner_id === (int)$real_qualifier_id) {
                    $pts_winner = $winner_pts; 
                }
            } else {
                // Grupos: Acertar Ganador (1X2)
                $winner_real = ($real_home > $real_away) ? 'H' : (($real_home < $real_away) ? 'A' : 'X');
                $winner_pred = ($p_home > $p_away) ? 'H' : (($p_home < $p_away) ? 'A' : 'X');
                if ($winner_real === $winner_pred) {
                    $pts_winner = $winner_pts; 
                }
            }
            
            // 5c. Puntos por Goles (10/5 pts)
            $pts_goals = 0;
            if (($p_home === $real_home) || ($p_away === $real_away)) {
                $pts_goals = $goals_pts;
            }

            
            // --- 6. LÓGICA DE ACUMULACIÓN FINAL ---
            
            if ($earned_exact) {
                if ($is_knockout && $is_real_draw) {
                    // KNOCKOUT + EMPATE: ACUMULABLE (30 + 25)
                    $points = $exact_pts + $pts_winner; 
                } else {
                    // GRUPOS O KNOCKOUT NO-EMPATE: NO ACUMULABLE
                    $points = $exact_pts; 
                }
            } else {
                // NO HAY EXACTO: Se suman Clasificado/Ganador + Goles
                $points = $pts_winner + $pts_goals;
            }

            
            // --- 7. LÓGICA DEL COMODÍN X2 ---
            
            $stmt_wc->execute([$user_id]);
            $wc_match_id = $stmt_wc->fetchColumn();

            // Si el comodín fue usado en ESTE partido Y si obtuvo puntos (> 0)
            if ($wc_match_id == $match_id && $points > 0) {
                $points = $points * 2;
            }
            
            // 8. GUARDAR PUNTOS
            $update_points = $pdo->prepare("UPDATE predictions SET points_earned = :pts WHERE id = :pid");
            $update_points->execute(['pts' => $points, 'pid' => $pred['id']]);
        } // Fin foreach ($predictions as $pred)

        // 9. LÓGICA DE REGISTRO DE ACTIVIDAD (Log)
        
        // Obtener el nombre de los equipos para el log
        $stmt_names = $pdo->prepare("SELECT t1.name as home, t2.name as away FROM teams t1 JOIN teams t2 ON t1.id = ? AND t2.id = ?");
        $stmt_names->execute([$home_id, $away_id]);
        $names = $stmt_names->fetch(PDO::FETCH_ASSOC);

        $log_desc = "Resultado ingresado: " . $names['home'] . " " . $real_home . " - " . $real_away . " " . $names['away'] . ". Puntos recalculados.";

        $stmt_log = $pdo->prepare("INSERT INTO admin_activity_log (action_type, description) VALUES ('match_close', ?)");
        $stmt_log->execute([$log_desc]);
        
        // 10. Redirección final
        header('Location: index.php?msg=ok');
        exit;

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}