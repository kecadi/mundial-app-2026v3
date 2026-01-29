<?php
// admin/calculate.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = (int)$_POST['match_id'];
    $real_home = (int)$_POST['real_home'];
    $real_away = (int)$_POST['real_away'];
    $match_phase = $_POST['match_phase'];
    
    $real_qualifier_id = !empty($_POST['real_qualifier_id']) ? (int)$_POST['real_qualifier_id'] : NULL;

    try {
        $pdo->beginTransaction(); // Iniciamos transacción para que todo sea atómico

        // 1. ACTUALIZAR EL RESULTADO DEL PARTIDO
        $sql_update = "UPDATE matches SET 
                       home_score = :rh, away_score = :ra, status = 'finished',
                       real_qualifier_id = :rqid WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'rh' => $real_home, 'ra' => $real_away, 
            'rqid' => $real_qualifier_id, 'id' => $match_id
        ]);

        // 2. OBTENER INFORMACIÓN DE PUNTOS CONFIGURADA
        $is_knockout = ($match_phase !== 'group');
        $exact_pts = $is_knockout ? 30 : 25;
        $winner_pts = $is_knockout ? 25 : 15;
        $goals_pts = $is_knockout ? 10 : 5;

        // 3. PROCESAR CADA PREDICCIÓN
        $stmt_preds = $pdo->prepare("SELECT * FROM predictions WHERE match_id = :mid");
        $stmt_preds->execute(['mid' => $match_id]);
        $predictions = $stmt_preds->fetchAll(PDO::FETCH_ASSOC);

        $user_points_map = []; // Guardamos puntos base de cada usuario aquí

        foreach ($predictions as $pred) {
            $u_id = $pred['user_id'];
            $p_home = (int)$pred['predicted_home_score'];
            $p_away = (int)$pred['predicted_away_score'];
            $p_qualifier = $pred['predicted_qualifier_id'];
            $points = 0;

            // Lógica: Marcador exacto
            $is_exact = ($p_home === $real_home && $p_away === $real_away);
            
            // Lógica: Ganador/Empate
            $real_result = ($real_home > $real_away) ? 'H' : (($real_home < $real_away) ? 'A' : 'X');
            $pred_result = ($p_home > $p_away) ? 'H' : (($p_home < $p_away) ? 'A' : 'X');
            $winner_match = ($real_result === $pred_result);

            if ($is_exact) {
                $points = $exact_pts;
                // En eliminatorias, si hay empate real, se suma plus por acertar quién pasa
                if ($is_knockout && $real_home === $real_away && (int)$p_qualifier === $real_qualifier_id) {
                    $points += $winner_pts;
                }
            } else {
                if ($winner_match) $points += $winner_pts;
                if ($p_home === $real_home || $p_away === $real_away) $points += $goals_pts;
            }

            // Comodín (Wildcard): Duplica puntos si el usuario lo activó en este partido
            $stmt_wc = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND wildcard_used_match_id = ?");
            $stmt_wc->execute([$u_id, $match_id]);
            if ($stmt_wc->fetchColumn() > 0) {
                $points *= 2;
            }

            $user_points_map[$u_id] = $points;
        }

        // 4. LÓGICA DE DUELOS (CHALLENGES)
        $updated_user_points = $user_points_map; // Copia para aplicar robos de puntos
        
        $stmt_ch = $pdo->prepare("SELECT * FROM match_challenges WHERE match_id = ? AND wager_status = 'PENDING'");
        $stmt_ch->execute([$match_id]);
        $challenges = $stmt_ch->fetchAll(PDO::FETCH_ASSOC);

        foreach ($challenges as $ch) {
            $u1 = $ch['challenger_user_id'];
            $u2 = $ch['challenged_user_id'];

            // Solo si ambos tienen predicción
            if (isset($user_points_map[$u1]) && isset($user_points_map[$u2])) {
                $pts1 = $user_points_map[$u1];
                $pts2 = $user_points_map[$u2];
                $seized = 0;

                if ($pts1 > $pts2) {
                    $seized = $pts2;
                    $updated_user_points[$u1] += $seized;
                    $updated_user_points[$u2] = 0; // El perdedor se queda a 0 en este partido
                } elseif ($pts2 > $pts1) {
                    $seized = $pts1;
                    $updated_user_points[$u2] += $seized;
                    $updated_user_points[$u1] = 0;
                }

                // Actualizar el registro del desafío
                $upd_ch = $pdo->prepare("UPDATE match_challenges SET wager_status = 'PROCESSED', points_seized = ? WHERE id = ?");
                $upd_ch->execute([$seized, $ch['id']]);
            }
        }

        // 5. GUARDADO FINAL: TABLA PREDICTIONS Y TABLA USERS
        $upd_pred = $pdo->prepare("UPDATE predictions SET points_earned = ? WHERE user_id = ? AND match_id = ?");
        $upd_user = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");

        foreach ($updated_user_points as $uid => $total_p_partido) {
            // Guardamos lo ganado en la predicción específica
            $upd_pred->execute([$total_p_partido, $uid, $match_id]);
            // SUMAMOS al saldo global del usuario
            $upd_user->execute([$total_p_partido, $uid]);
        }

        // LOG Y FINALIZACIÓN
        $pdo->prepare("INSERT INTO admin_activity_log (action_type, description) VALUES ('match_close', ?)")
            ->execute(["Calculados puntos para partido ID $match_id. Duelos procesados."]);

        $pdo->commit();
        header('Location: index.php?msg=ok');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error crítico: " . $e->getMessage());
    }
}