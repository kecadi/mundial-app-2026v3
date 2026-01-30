<?php
// admin/calculate.php
session_start();
require_once '../config/db.php'; 
require_once '../includes/check_achievements.php'; // IMPORTANTE: Cargamos el motor de logros complejos

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
        $pdo->beginTransaction(); 

        // 1. ACTUALIZAR EL RESULTADO DEL PARTIDO
        $sql_update = "UPDATE matches SET 
                       home_score = :rh, away_score = :ra, status = 'finished',
                       real_qualifier_id = :rqid WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'rh' => $real_home, 'ra' => $real_away, 
            'rqid' => $real_qualifier_id, 'id' => $match_id
        ]);

        // 2. CONFIGURACIÓN DE PUNTOS (Según tus reglas: 25, 15, 5)
        $is_knockout = ($match_phase !== 'group');
        $exact_pts  = $is_knockout ? 30 : 25;   // 25 en grupos, 30 en eliminatorias
        $winner_pts = $is_knockout ? 20 : 15;   // 15 en grupos, 20 en eliminatorias
        $goal_pts   = 5;                        // 5 puntos por acierto de goles de un equipo
        $qualifier_bonus = 10;                  // Bonus por acertar quién pasa (solo eliminatorias)

        // 3. OBTENER PREDICCIONES
        $stmt_preds = $pdo->prepare("SELECT user_id, predicted_home_score, predicted_away_score, predicted_qualifier_id FROM predictions WHERE match_id = ?");
        $stmt_preds->execute([$match_id]);
        $predictions = $stmt_preds->fetchAll(PDO::FETCH_ASSOC);

        $user_points_map = []; 

        foreach ($predictions as $p) {
            $pts = 0;
            $p_home = (int)$p['predicted_home_score'];
            $p_away = (int)$p['predicted_away_score'];

            $real_diff = $real_home - $real_away;
            $pred_diff = $p_home - $p_away;

            // LÓGICA NO ACUMULABLE
            if ($p_home === $real_home && $p_away === $real_away) {
                $pts = $exact_pts;
            } elseif (($real_diff > 0 && $pred_diff > 0) || ($real_diff < 0 && $pred_diff < 0) || ($real_diff === 0 && $pred_diff === 0)) {
                $pts = $winner_pts;
            } elseif ($p_home === $real_home || $p_away === $real_away) {
                $pts = $goal_pts;
            }

            if ($is_knockout && $real_qualifier_id && (int)$p['predicted_qualifier_id'] === (int)$real_qualifier_id) {
                $pts += $qualifier_bonus;
            }

            // Aplicación Comodín x2
            $stmt_w = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND wildcard_used_match_id = ?");
            $stmt_w->execute([$p['user_id'], $match_id]);
            if ($stmt_w->fetchColumn() > 0) {
                $pts *= 2;
            }

            $user_points_map[$p['user_id']] = $pts;
        }

        // 4. PROCESAR DUELOS (CHALLENGES)
        $updated_user_points = $user_points_map;
        $stmt_challenges = $pdo->prepare("SELECT * FROM match_challenges WHERE match_id = ? AND wager_status = 'PENDING'");
        $stmt_challenges->execute([$match_id]);
        $challenges = $stmt_challenges->fetchAll(PDO::FETCH_ASSOC);

        foreach ($challenges as $ch) {
            $u1 = $ch['challenger_user_id'];
            $u2 = $ch['challenged_user_id'];
            if (isset($user_points_map[$u1]) && isset($user_points_map[$u2])) {
                $pts1 = $user_points_map[$u1]; $pts2 = $user_points_map[$u2];
                $seized = 0;
                if ($pts1 > $pts2) {
                    $seized = $pts2;
                    $updated_user_points[$u1] += $seized;
                    $updated_user_points[$u2] = 0;
                } elseif ($pts2 > $pts1) {
                    $seized = $pts1;
                    $updated_user_points[$u2] += $seized;
                    $updated_user_points[$u1] = 0;
                }
                $upd_ch = $pdo->prepare("UPDATE match_challenges SET wager_status = 'PROCESSED', points_seized = ? WHERE id = ?");
                $upd_ch->execute([$seized, $ch['id']]);
            }
        }

        // 5. GUARDADO DE PUNTOS EN PREDICTIONS
        $upd_pred = $pdo->prepare("UPDATE predictions SET points_earned = ? WHERE user_id = ? AND match_id = ?");
        foreach ($updated_user_points as $uid => $total_p_partido) {
            $upd_pred->execute([$total_p_partido, $uid, $match_id]);
        }

        // 6. GUARDAR HISTORIAL PARA EL GRÁFICO DEL PERFIL
        $sql_ranking_snap = "SELECT u.id, (COALESCE(T_MATCH.match_points, 0) + COALESCE(T_BONUS.bonus_points, 0) + COALESCE(T_QUIZ.quiz_points, 0)) AS total_actual
            FROM users u
            LEFT JOIN (SELECT user_id, SUM(points_earned) AS match_points FROM predictions GROUP BY user_id) T_MATCH ON u.id = T_MATCH.user_id
            LEFT JOIN (SELECT user_id, SUM(points_awarded) AS bonus_points FROM group_ranking_points GROUP BY user_id) T_BONUS ON u.id = T_BONUS.user_id
            LEFT JOIN (SELECT user_id, SUM(points_awarded) AS quiz_points FROM daily_quiz_responses GROUP BY user_id) T_QUIZ ON u.id = T_QUIZ.user_id
            WHERE u.role != 'admin' ORDER BY total_actual DESC";

        $ranking_data = $pdo->query($sql_ranking_snap)->fetchAll(PDO::FETCH_ASSOC);
        $stmt_ins_history = $pdo->prepare("INSERT INTO ranking_history (user_id, match_id, points_at_moment, rank_at_moment) VALUES (?, ?, ?, ?)");
        $current_pos = 1;
        foreach ($ranking_data as $row) {
            $stmt_ins_history->execute([$row['id'], $match_id, $row['total_actual'], $current_pos]);
            $current_pos++;
        }

        // =====================================================================
        // 7. FASE NUEVA: REPARTIR LOGROS AUTOMÁTICOS
        // =====================================================================
        
        // Logro Inmediato: Ojo de Halcón (Resultado exacto)
        $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_key) 
                       SELECT user_id, 'hawk_eye' FROM predictions 
                       WHERE match_id = ? AND points_earned >= 25")->execute([$match_id]);

        // Logro Inmediato: Estratega (Uso de comodín con éxito)
        // Se otorga si el usuario tiene ese match_id en wildcard_used_match_id y ganó más de 0 puntos
        $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_key) 
                       SELECT user_id, 'strategist' FROM predictions 
                       WHERE match_id = ? AND points_earned > 0 
                       AND user_id IN (SELECT id FROM users WHERE wildcard_used_match_id = ?)")
            ->execute([$match_id, $match_id]);

        // Escanear logros complejos para todos los usuarios involucrados
        $all_users = $pdo->query("SELECT id FROM users WHERE role = 'user'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($all_users as $uid) {
            checkUserAchievements($pdo, $uid);
        }
        // =====================================================================

        $pdo->commit();
        
        // Log de actividad y redirección
        $pdo->prepare("INSERT INTO admin_activity_log (action_type, description) VALUES ('match_close', ?)")
            ->execute(["Cerrado partido ID $match_id. Puntos, Ranking y Logros procesados."]);

        header("Location: index.php?msg=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error crítico: " . $e->getMessage());
    }
}