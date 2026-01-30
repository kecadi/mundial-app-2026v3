<?php
// includes/index_logic.php

$user_id = $_SESSION['user_id'];

// 1. LÓGICA DE RANKING (CON RASTREO DE MOVIMIENTO)
$sql_ranking = "SELECT 
    u.id, u.nombre, u.last_known_rank, u.rival_id,
    COALESCE(T_MATCH.match_points, 0) AS match_points,
    COALESCE(T_BONUS.bonus_points, 0) AS bonus_points,
    COALESCE(T_QUIZ.quiz_points, 0) AS quiz_points, 
    (COALESCE(T_MATCH.match_points, 0) + COALESCE(T_BONUS.bonus_points, 0) + COALESCE(T_QUIZ.quiz_points, 0)) AS total_puntos
FROM users u
LEFT JOIN (SELECT user_id, SUM(points_earned) AS match_points FROM predictions GROUP BY user_id) T_MATCH ON u.id = T_MATCH.user_id
LEFT JOIN (SELECT user_id, SUM(points_awarded) AS bonus_points FROM group_ranking_points GROUP BY user_id) T_BONUS ON u.id = T_BONUS.user_id
LEFT JOIN (SELECT user_id, SUM(points_awarded) AS quiz_points FROM daily_quiz_responses GROUP BY user_id) T_QUIZ ON u.id = T_QUIZ.user_id
WHERE u.role != 'admin'
ORDER BY total_puntos DESC";

$ranking_data = $pdo->query($sql_ranking)->fetchAll(PDO::FETCH_ASSOC);

// 2. ACTUALIZAR POSICIONES Y CALCULAR MOVIMIENTO
$stmt_update_rank = $pdo->prepare("UPDATE users SET last_known_rank = ? WHERE id = ?");
$ranking = [];
$pos_rank = 1;

foreach ($ranking_data as $jugador) {
    $last_rank = $jugador['last_known_rank'] ?? $pos_rank; 
    $movement = $last_rank - $pos_rank; 
    $jugador['movement'] = $movement;
    $jugador['current_rank'] = $pos_rank;
    $ranking[] = $jugador;
    $stmt_update_rank->execute([$pos_rank, $jugador['id']]);
    $pos_rank++;
}

// --- MAPA DE USUARIOS ---
$user_name_map = $pdo->query("SELECT id, nombre FROM users")->fetchAll(PDO::FETCH_KEY_PAIR);

// --- LÓGICA DEL QUIZ DIARIO ---
$stmt_answered = $pdo->prepare("SELECT points_awarded FROM daily_quiz_responses WHERE user_id = ? AND response_date = CURDATE()");
$stmt_answered->execute([$user_id]);
$answered_result = $stmt_answered->fetch(PDO::FETCH_ASSOC);

$quiz_answered = (bool)$answered_result;
$quiz_points_today = $answered_result['points_awarded'] ?? 0;
$quiz_data = (!$quiz_answered) ? $pdo->query("SELECT id, question, option_a, option_b, option_c, option_d FROM daily_quiz_questions WHERE date_available = CURDATE()")->fetch(PDO::FETCH_ASSOC) : null;

// --- ESTADO DEL COMODÍN X2 ---
$wildcard_match_id = $pdo->query("SELECT wildcard_used_match_id FROM users WHERE id = $user_id")->fetchColumn();
$wildcard_available = ($wildcard_match_id === NULL);

// --- DATOS PERSONALES DEL USUARIO LOGUEADO ---
$mis_puntos = 0;
$mi_posicion = '-';
$current_rival_id = null;
foreach($ranking as $r) {
    if($r['id'] == $user_id) { 
        $mis_puntos = $r['total_puntos'];
        $mi_posicion = $r['current_rank'];
        $current_rival_id = $r['rival_id'];
    }
}

// --- OBTENER LOGS DE ACTIVIDAD RECIENTE (LO QUE FALTABA) ---
$stmt_log = $pdo->query("SELECT description, created_at FROM admin_activity_log ORDER BY created_at DESC LIMIT 3");
$latest_updates = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

// --- CONTADOR REGRESIVO ---
$next_matches_data = [];
$earliest_match_time = $pdo->query("SELECT match_date FROM matches WHERE status = 'scheduled' ORDER BY match_date ASC LIMIT 1")->fetchColumn();
if ($earliest_match_time) {
    $stmt_next = $pdo->prepare("SELECT m.id, t1.name AS home, t2.name AS away, t1.flag AS home_flag, t2.flag AS away_flag, m.match_date FROM matches m JOIN teams t1 ON m.team_home_id = t1.id JOIN teams t2 ON m.team_away_id = t2.id WHERE m.status = 'scheduled' AND m.match_date = ?");
    $stmt_next->execute([$earliest_match_time]);
    $next_matches_data = $stmt_next->fetchAll(PDO::FETCH_ASSOC);
    $next_lock_timestamp = strtotime($earliest_match_time) - 300;
    $time_until_lock_ms = ($next_lock_timestamp - time()) * 1000;
}

// --- LÓGICA DE PARTIDOS (FILTRO FASES) ---
$fase_activa = $_GET['fase'] ?? 'group';
$nombres_fases = ['group' => 'Fase de Grupos', 'round_32' => 'Dieciseisavos', 'round_16' => 'Octavos', 'quarter' => 'Cuartos', 'semi' => 'Semifinales', 'final' => 'Gran Final'];
if (!array_key_exists($fase_activa, $nombres_fases)) $fase_activa = 'group';

$sql_partidos = "SELECT m.id as match_id, m.match_date, m.stadium, m.phase, m.status, m.home_score as real_home, m.away_score as real_away, m.team_home_id, m.team_away_id, t1.name as home_name, t1.flag as home_flag, t1.key_players AS home_players, t1.group_name, t2.name as away_name, t2.flag as away_flag, t2.key_players AS away_players, p.predicted_home_score, p.predicted_away_score, p.points_earned, p.predicted_qualifier_id, s.image_url, mc_by_me.id AS challenged_by_me_id, mc_challenged_me.id AS challenged_me_id, mc_by_me.challenged_user_id AS rival_id_by_me, mc_challenged_me.challenger_user_id AS rival_id_me 
FROM matches m JOIN teams t1 ON m.team_home_id = t1.id JOIN teams t2 ON m.team_away_id = t2.id LEFT JOIN predictions p ON m.id = p.match_id AND p.user_id = :uid LEFT JOIN stadiums s ON m.stadium = s.name LEFT JOIN match_challenges mc_by_me ON m.id = mc_by_me.match_id AND mc_by_me.challenger_user_id = :uid LEFT JOIN match_challenges mc_challenged_me ON m.id = mc_challenged_me.match_id AND mc_challenged_me.challenged_user_id = :uid 
WHERE m.phase = :fase ORDER BY m.match_date ASC";
$stmt = $pdo->prepare($sql_partidos);
$stmt->execute(['uid' => $user_id, 'fase' => $fase_activa]);
$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$comment_counts = get_comment_counts($pdo, $fase_activa, $user_id);

// --- RASTREADOR DE PROGRESO DE PREDICCIÓN ---
$total_matches_generated = $pdo->query("SELECT COUNT(id) FROM matches WHERE phase != 'third_place'")->fetchColumn();
$stmt_my_preds = $pdo->prepare("SELECT COUNT(id) FROM predictions WHERE user_id = ?");
$stmt_my_preds->execute([$user_id]);
$my_predictions_count = $stmt_my_preds->fetchColumn();
$percentage_complete = ($total_matches_generated > 0) ? round(($my_predictions_count / $total_matches_generated) * 100) : 0;

// =======================================================================
// LÓGICA DE NOTIFICACIONES (TOASTS)
// =======================================================================
$stmt_notif_retos = $pdo->prepare("SELECT COUNT(*) FROM match_challenges WHERE challenged_user_id = ? AND wager_status = 'PENDING'");
$stmt_notif_retos->execute([$user_id]);
$retos_pendientes_count = $stmt_notif_retos->fetchColumn();

$stmt_notif_vacios = $pdo->prepare("
    SELECT COUNT(*) 
    FROM matches m 
    LEFT JOIN predictions p ON m.id = p.match_id AND p.user_id = ? 
    WHERE m.status = 'scheduled' 
    AND p.id IS NULL 
    AND m.match_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
");
$stmt_notif_vacios->execute([$user_id]);
$pronosticos_faltantes_count = $stmt_notif_vacios->fetchColumn();

// --- LÓGICA DE COMPARATIVA CON EL RIVAL ---
$rival_data = null;
if ($current_rival_id) {
    foreach ($ranking as $jugador) {
        if ($jugador['id'] == $current_rival_id) {
            $rival_data = $jugador;
            break;
        }
    }
}
?>