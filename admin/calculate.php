<?php
// admin/calculate.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CAPTURAR DATOS Y PREPARAR
    $match_id = $_POST['match_id'];
    $real_home = (int)$_POST['real_home'];
    $real_away = (int)$_POST['real_away'];
    $match_phase = $_POST['match_phase'];
    
    $real_qualifier_id = $_POST['real_qualifier_id'] ?? NULL;
    if ($real_qualifier_id === '') $real_qualifier_id = NULL;

    try {
        // 2. ACTUALIZAR EL PARTIDO EN LA BASE DE DATOS
        $sql_update = "UPDATE matches SET 
                       home_score = :rh, away_score = :ra, status = 'finished',
                       real_qualifier_id = :rqid WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'rh' => $real_home, 'ra' => $real_away, 
            'rqid' => $real_qualifier_id, 'id' => $match_id
        ]);

        // 3. FETCH DE DATOS PARA INFERENCIA Y LOG
        $stmt_info = $pdo->prepare("SELECT team_home_id, team_away_id FROM matches WHERE id = :mid");
        $stmt_info->execute(['mid' => $match_id]);
        $match_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        $home_id = $match_info['team_home_id'];
        $away_id = $match_info['team_away_id'];

        // 4. PREPARAR SENTENCIAS Y DEFINIR PUNTOS BASE
        $stmt_preds = $pdo->prepare("SELECT * FROM predictions WHERE match_id = :mid");
        $stmt_preds->execute(['mid' => $match_id]);
        $predictions = $stmt_preds->fetchAll(PDO::FETCH_ASSOC);
        
        $is_knockout = ($match_phase !== 'group');
        $is_real_draw = ($real_home === $real_away);
        
        $exact_pts = $is_knockout ? 30 : 25;
        $winner_pts = $is_knockout ? 25 : 15;
        $goals_pts = $is_knockout ? 10 : 5;

        $stmt_wc = $pdo->prepare("SELECT wildcard_used_match_id FROM users WHERE id = ?");
        
        // Arrays para almacenar puntos calculados y el ID de predicción
        $user_points_map = []; // [user_id => points]
        $pred_id_map = []; // [user_id => prediction_id]


        // =====================================================================
        // FASE A: CÁLCULO DE PUNTOS INDIVIDUALES Y ALMACENAMIENTO TEMPORAL
        // =====================================================================
        foreach ($predictions as $pred) {
            $user_id = $pred['user_id'];
            $p_home = (int)$pred['predicted_home_score'];
            $p_away = (int)$pred['predicted_away_score'];
            $p_qualifier_id = $pred['predicted_qualifier_id'];
            $points = 0;
            
            // Lógica de Puntuación (Mantenida)
            $earned_exact = ($p_home === $real_home && $p_away === $real_away);
            
            // ... (Calculamos $pts_winner y $pts_goals igual que antes) ...
            $pts_winner = 0;
            if ($is_knockout) {
                if ($predicted_winner_id && $real_qualifier_id && (int)$predicted_winner_id === (int)$real_qualifier_id) { $pts_winner = $winner_pts; }
            } else {
                $winner_real = ($real_home > $real_away) ? 'H' : (($real_home < $real_away) ? 'A' : 'X');
                $winner_pred = ($p_home > $p_away) ? 'H' : (($p_home < $p_away) ? 'A' : 'X');
                if ($winner_real === $winner_pred) { $pts_winner = $winner_pts; }
            }
            $pts_goals = (($p_home === $real_home) || ($p_away === $real_away)) ? $goals_pts : 0;
            
            // Lógica de Acumulación Final
            if ($earned_exact) {
                if ($is_knockout && $is_real_draw) {
                    $points = $exact_pts + $pts_winner; 
                } else {
                    $points = $exact_pts; 
                }
            } else {
                $points = $pts_winner + $pts_goals;
            }

            // Lógica del Comodín x2 (Aplicar si hay puntos > 0)
            $stmt_wc->execute([$user_id]);
            $wc_match_id = $stmt_wc->fetchColumn();
            if ($wc_match_id == $match_id && $points > 0) {
                $points = $points * 2;
            }
            
            // Almacenar el resultado para la siguiente fase
            $user_points_map[$user_id] = $points;
            $pred_id_map[$user_id] = $pred['id'];
        }

        // =====================================================================
        // FASE B: LÓGICA DE DUELOS (Reasignación de Puntos)
        // =====================================================================

        // Traer todos los desafíos pendientes para este partido
        $stmt_challenges = $pdo->prepare("SELECT id, challenger_user_id, challenged_user_id FROM match_challenges WHERE match_id = ? AND wager_status = 'PENDING'");
        $stmt_challenges->execute([$match_id]);
        $challenges = $stmt_challenges->fetchAll(PDO::FETCH_ASSOC);

        $updated_user_points = $user_points_map; // Copia de los puntos para modificar

        if (!empty($challenges)) {
            $stmt_update_challenge = $pdo->prepare("UPDATE match_challenges SET wager_status = 'PROCESSED', points_seized = ? WHERE id = ?");

            foreach ($challenges as $challenge) {
                $uid_a = $challenge['challenger_user_id'];
                $uid_b = $challenge['challenged_user_id'];

                // Solo procesamos si ambos usuarios predijeron el partido
                if (!isset($user_points_map[$uid_a]) || !isset($user_points_map[$uid_b])) {
                    // Marcar desafío como nulo si falta la predicción de alguien
                    continue; 
                }

                $pts_a = $user_points_map[$uid_a];
                $pts_b = $user_points_map[$uid_b];
                $points_seized = 0;

                if ($pts_a > $pts_b) {
                    // El Desafiante A gana más puntos: se lleva los puntos de B.
                    $updated_user_points[$uid_a] = $pts_a + $pts_b;
                    $updated_user_points[$uid_b] = 0;
                    $points_seized = $pts_b;
                } elseif ($pts_b > $pts_a) {
                    // El Desafiado B gana más puntos: se lleva los puntos de A.
                    $updated_user_points[$uid_b] = $pts_b + $pts_a;
                    $updated_user_points[$uid_a] = 0;
                    $points_seized = $pts_a;
                } else {
                    // Empate en puntos: ambos se quedan con sus puntos originales.
                    // El estatus del desafío se marcará como 'PROCESSED' con 0 puntos confiscados.
                }

                // Actualizar estatus del desafío en la DB
                $stmt_update_challenge->execute([$points_seized, $challenge['id']]);
            }
        }


        // =====================================================================
        // FASE C: GUARDAR PUNTOS FINALES EN LA BASE DE DATOS
        // =====================================================================
        $update_points_sql = $pdo->prepare("UPDATE predictions SET points_earned = :pts WHERE id = :pid");
        
        foreach ($updated_user_points as $user_id => $final_points) {
            $pred_id = $pred_id_map[$user_id];
            $update_points_sql->execute(['pts' => $final_points, 'pid' => $pred_id]);
        }


        // 10. REGISTRO DE ACTIVIDAD Y REDIRECCIÓN
        $stmt_names = $pdo->prepare("SELECT t1.name as home, t2.name as away FROM teams t1 JOIN teams t2 ON t1.id = ? AND t2.id = ?");
        $stmt_names->execute([$home_id, $away_id]);
        $names = $stmt_names->fetch(PDO::FETCH_ASSOC);

        $log_desc = "Resultado ingresado: " . $names['home'] . " " . $real_home . " - " . $real_away . " " . $names['away'] . ". Puntos recalculados.";

        $stmt_log = $pdo->prepare("INSERT INTO admin_activity_log (action_type, description) VALUES ('match_close', ?)");
        $stmt_log->execute([$log_desc]);
        
        header('Location: index.php?msg=ok');
        exit;

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}