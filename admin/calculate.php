<?php
// admin/calculate.php (VERSIÓN FINAL CON JERARQUÍA DE RETOS)
session_start();
require_once '../config/db.php'; 
require_once '../includes/check_achievements.php';

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
        $pdo->prepare("UPDATE matches SET home_score = ?, away_score = ?, status = 'finished', real_qualifier_id = ? WHERE id = ?")
            ->execute([$real_home, $real_away, $real_qualifier_id, $match_id]);

        // 2. CONFIGURACIÓN DE PUNTOS
        $is_knockout = ($match_phase !== 'group');
        $exact_pts   = $is_knockout ? 30 : 25;
        $winner_pts  = $is_knockout ? 20 : 15;
        $goal_pts    = 5;
        $qualifier_bonus = 25;
        $bonus_reto = 15;

        // 3. CALCULAR PUNTOS BASE Y COMODÍN
        $stmt_preds = $pdo->prepare("SELECT user_id, predicted_home_score, predicted_away_score, predicted_qualifier_id FROM predictions WHERE match_id = ?");
        $stmt_preds->execute([$match_id]);
        $predictions = $stmt_preds->fetchAll(PDO::FETCH_ASSOC);

        $user_points_map = []; 
        $user_predictions_data = []; // Guardamos datos para el paso de retos

        foreach ($predictions as $p) {
            $pts = 0;
            $p_h = (int)$p['predicted_home_score'];
            $p_a = (int)$p['predicted_away_score'];

            $real_diff = $real_home - $real_away;
            $pred_diff = $p_h - $p_a;

            // Determinar si acertó el signo (Ganador o Empate)
            $acerto_signo = (($real_diff > 0 && $pred_diff > 0) || ($real_diff < 0 && $pred_diff < 0) || ($real_diff === 0 && $pred_diff === 0));

            if ($p_h === $real_home && $p_a === $real_away) {
                $pts = $exact_pts;
            } elseif ($acerto_signo) {
                $pts = $winner_pts;
            } elseif ($p_h === $real_home || $p_a === $real_away) {
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
            $user_predictions_data[$p['user_id']] = [
                'h' => $p_h, 
                'a' => $p_a, 
                'signo' => $acerto_signo
            ];
        }

        // 4. PROCESAR DUELOS CON JERARQUÍA (Signo > Goles)
        $updated_user_points = $user_points_map;
        $stmt_challenges = $pdo->prepare("SELECT * FROM match_challenges WHERE match_id = ? AND wager_status = 'PENDING'");
        $stmt_challenges->execute([$match_id]);
        $challenges = $stmt_challenges->fetchAll(PDO::FETCH_ASSOC);

        foreach ($challenges as $ch) {
            $u1 = $ch['challenger_user_id'];
            $u2 = $ch['challenged_user_id'];

            if (isset($user_predictions_data[$u1]) && isset($user_predictions_data[$u2])) {
                $d1 = $user_predictions_data[$u1];
                $d2 = $user_predictions_data[$u2];
                $ganador_duelo = null;

                // Lógica de Jerarquía
                if ($d1['signo'] && !$d2['signo']) {
                    $ganador_duelo = $u1; // Solo U1 acertó el ganador
                } elseif (!$d1['signo'] && $d2['signo']) {
                    $ganador_duelo = $u2; // Solo U2 acertó el ganador
                } else {
                    // Ambos acertaron el signo o ambos fallaron: Decidimos por precisión de goles
                    $diff1 = abs($d1['h'] - $real_home) + abs($d1['a'] - $real_away);
                    $diff2 = abs($d2['h'] - $real_home) + abs($d2['a'] - $real_away);

                    if ($diff1 < $diff2) $ganador_duelo = $u1;
                    elseif ($diff2 < $diff1) $ganador_duelo = $u2;
                }

                if ($ganador_duelo) {
                    $updated_user_points[$ganador_duelo] += $bonus_reto;
                }

                $pdo->prepare("UPDATE match_challenges SET wager_status = 'PROCESSED', points_seized = ? WHERE id = ?")
                    ->execute([($ganador_duelo ? $bonus_reto : 0), $ch['id']]);
            }
        }

        // 5. GUARDADO DEFINITIVO
        $upd_pred = $pdo->prepare("UPDATE predictions SET points_earned = ? WHERE user_id = ? AND match_id = ?");
        foreach ($updated_user_points as $uid => $total) {
            $upd_pred->execute([$total, $uid, $match_id]);
        }

        // 6. GUARDAR HISTORIAL PARA EL GRÁFICO DEL PERFIL
        $sql_ranking_snap = "SELECT u.id, (
            COALESCE(T_MATCH.match_points, 0) + 
            COALESCE(T_GROUP.group_points, 0) + 
            COALESCE(T_QUIZ.quiz_points, 0) +
            COALESCE(T_ACHIEV.bonus_points, 0) -- <--- SUMAMOS LA NUEVA TABLA
        ) AS total_actual
        FROM users u
        LEFT JOIN (SELECT user_id, SUM(points_earned) AS match_points FROM predictions GROUP BY user_id) T_MATCH ON u.id = T_MATCH.user_id
        LEFT JOIN (SELECT user_id, SUM(points_awarded) AS group_points FROM group_ranking_points GROUP BY user_id) T_GROUP ON u.id = T_GROUP.user_id
        LEFT JOIN (SELECT user_id, SUM(points_awarded) AS quiz_points FROM daily_quiz_responses GROUP BY user_id) T_QUIZ ON u.id = T_QUIZ.user_id
        LEFT JOIN (SELECT user_id, SUM(points_awarded) AS bonus_points FROM achievement_bonus_points GROUP BY user_id) T_ACHIEV ON u.id = T_ACHIEV.user_id -- <--- JOIN NUEVO
        WHERE u.role != 'admin'
        ORDER BY total_actual DESC";

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