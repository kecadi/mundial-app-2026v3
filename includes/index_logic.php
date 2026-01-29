<?php

// 2. LÓGICA DE RANKING (CON RASTREO DE MOVIMIENTO Y SUMAS AISLADAS)
$sql_ranking = "SELECT 
    u.id, u.nombre, u.last_known_rank, u.rival_id, /* Añadir ID y última posición */
    COALESCE(T_MATCH.match_points, 0) AS match_points,
    COALESCE(T_BONUS.bonus_points, 0) AS bonus_points,
    COALESCE(T_QUIZ.quiz_points, 0) AS quiz_points, 
    (COALESCE(T_MATCH.match_points, 0) + COALESCE(T_BONUS.bonus_points, 0) + COALESCE(T_QUIZ.quiz_points, 0)) AS total_puntos
FROM users u

-- SUBCONSULTA 1: Puntos de Partidos
LEFT JOIN (
    SELECT user_id, SUM(points_earned) AS match_points
    FROM predictions
    GROUP BY user_id
) T_MATCH ON u.id = T_MATCH.user_id

-- SUBCONSULTA 2: Puntos de Bonus (Grupos y Finales)
LEFT JOIN (
    SELECT user_id, SUM(points_awarded) AS bonus_points
    FROM group_ranking_points
    GROUP BY user_id
) T_BONUS ON u.id = T_BONUS.user_id

-- SUBCONSULTA 3: Puntos del QUIZ DIARIO
LEFT JOIN (
    SELECT user_id, SUM(points_awarded) AS quiz_points
    FROM daily_quiz_responses
    GROUP BY user_id
) T_QUIZ ON u.id = T_QUIZ.user_id

WHERE u.role != 'admin'
ORDER BY total_puntos DESC";

$ranking_data = $pdo->query($sql_ranking)->fetchAll(PDO::FETCH_ASSOC);

// 3. CALCULAR MOVIMIENTO Y ACTUALIZAR DB
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

// --- MAPA DE USUARIOS (Para lookup rápido) ---
$stmt_user_map = $pdo->query("SELECT id, nombre FROM users");
$user_name_map = $stmt_user_map->fetchAll(PDO::FETCH_KEY_PAIR);
// ------------------------------------------


// --- LÓGICA DEL QUIZ DIARIO ---
$quiz_data = null;
$quiz_answered = false;

// 1. Verificar si el usuario ya respondió hoy
$stmt_answered = $pdo->prepare("SELECT points_awarded FROM daily_quiz_responses WHERE user_id = ? AND response_date = CURDATE()");
$stmt_answered->execute([$user_id]);
$answered_result = $stmt_answered->fetch(PDO::FETCH_ASSOC);

if ($answered_result) {
    $quiz_answered = true;
    $quiz_points_today = $answered_result['points_awarded'];
}

// 2. Si no ha respondido, buscar la pregunta programada para hoy
if (!$quiz_answered) {
    $stmt_quiz = $pdo->query("SELECT id, question, option_a, option_b, option_c, option_d FROM daily_quiz_questions WHERE date_available = CURDATE()");
    $quiz_data = $stmt_quiz->fetch(PDO::FETCH_ASSOC);
}
// --- FIN LÓGICA DEL QUIZ DIARIO ---

// --- ESTADO DEL COMODÍN X2 ---
$stmt_wildcard = $pdo->prepare("SELECT wildcard_used_match_id FROM users WHERE id = ?");
$stmt_wildcard->execute([$user_id]);
$wildcard_match_id = $stmt_wildcard->fetchColumn();

$wildcard_available = ($wildcard_match_id === NULL);
// ------------------------------

// Buscar mis puntos y posición en el ranking (del array $ranking ya calculado)
$current_user_id = $_SESSION['user_id'];
$mis_puntos = 0;
$mi_posicion = '-';
$current_rival_id = null; // Para mostrar la etiqueta RIVAL

foreach($ranking as $r) {
    if($r['id'] === $current_user_id) { 
        $mis_puntos = $r['total_puntos'];
        $mi_posicion = $r['current_rank'];
    }
    // Asumo que el rival_id se puede obtener del array ranking_data (si el JOIN fue hecho en users)
    // Buscamos el rival del usuario logueado en los datos de ranking_data
    if($r['id'] === $current_user_id) {
         $current_rival_id = $r['rival_id'];
    }
}
if (!$current_rival_id) {
    // Si no se encontró en el array, hacer una consulta directa (seguridad)
    $stmt_rival = $pdo->prepare("SELECT rival_id FROM users WHERE id = ?");
    $stmt_rival->execute([$user_id]);
    $current_rival_id = $stmt_rival->fetchColumn();
}


// --- OBTENER LOGS DE ACTIVIDAD RECIENTE ---
$stmt_log = $pdo->query("SELECT description, created_at FROM admin_activity_log ORDER BY created_at DESC LIMIT 3");
$latest_updates = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
// ------------------------------------------

// --- CONTADOR REGRESIVO ---
$next_matches_data = [];
$next_lock_timestamp = null;
$time_until_lock_ms = 0;
$lock_margin_sec = 300; // 5 minutos antes

// 1. Encontrar la hora de inicio más temprana
$earliest_match_time = $pdo->query("SELECT match_date FROM matches WHERE status = 'scheduled' ORDER BY match_date ASC LIMIT 1")->fetchColumn();

if ($earliest_match_time) {
    // 2. Fetch ALL matches scheduled at that exact time
    $stmt_next = $pdo->prepare("SELECT 
        m.id, t1.name AS home, t2.name AS away, t1.flag AS home_flag, t2.flag AS away_flag, m.match_date
    FROM matches m
    JOIN teams t1 ON m.team_home_id = t1.id
    JOIN teams t2 ON m.team_away_id = t2.id
    WHERE m.status = 'scheduled' AND m.match_date = ?
    ORDER BY m.id ASC");
    
    $stmt_next->execute([$earliest_match_time]);
    $next_matches_data = $stmt_next->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Establecer el tiempo de bloqueo (Tiempo de inicio - 5 minutos)
    $next_lock_timestamp = strtotime($earliest_match_time) - $lock_margin_sec;
    $time_until_lock_ms = ($next_lock_timestamp - time()) * 1000;
}
// --------------------------

// 4. LÓGICA DE PARTIDOS (Filtro por Fases)
$fase_activa = isset($_GET['fase']) ? $_GET['fase'] : 'group';

$nombres_fases = [
    'group' => 'Fase de Grupos',
    'round_32' => 'Dieciseisavos',
    'round_16' => 'Octavos',
    'quarter' => 'Cuartos',
    'semi' => 'Semifinales',
    'final' => 'Gran Final'
];

if (!array_key_exists($fase_activa, $nombres_fases)) {
    $fase_activa = 'group';
}

$sql_partidos = "SELECT 
            m.id as match_id, m.match_date, m.stadium, m.phase, m.status,
            m.home_score as real_home, m.away_score as real_away,
            m.team_home_id, m.team_away_id,
            t1.name as home_name, t1.flag as home_flag, t1.key_players AS home_players, t1.group_name,
            t2.name as away_name, t2.flag as away_flag, t2.key_players AS away_players,
            p.predicted_home_score, p.predicted_away_score, p.points_earned,
            p.predicted_qualifier_id,
            s.image_url,
            
            mc_by_me.id AS challenged_by_me_id,    
            mc_challenged_me.id AS challenged_me_id, 
            
            mc_by_me.challenged_user_id AS rival_id_by_me,     /* NUEVO */
            mc_challenged_me.challenger_user_id AS rival_id_me  /* NUEVO */
            
        FROM matches m
        JOIN teams t1 ON m.team_home_id = t1.id
        JOIN teams t2 ON m.team_away_id = t2.id
        LEFT JOIN predictions p ON m.id = p.match_id AND p.user_id = :uid
        LEFT JOIN stadiums s ON m.stadium = s.name 
        
        /* UNIÓN 1: YO SOY EL DESAFIANTE */
        LEFT JOIN match_challenges mc_by_me ON m.id = mc_by_me.match_id AND mc_by_me.challenger_user_id = :uid
        
        /* UNIÓN 2: YO SOY EL DESAFIADO */
        LEFT JOIN match_challenges mc_challenged_me ON m.id = mc_challenged_me.match_id AND mc_challenged_me.challenged_user_id = :uid
        
        WHERE m.phase = :fase ORDER BY m.match_date ASC";

$stmt = $pdo->prepare($sql_partidos);
$stmt->execute([
    'uid' => $user_id,
    'fase' => $fase_activa
]);
$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// OBTENER CONTEO DE COMENTARIOS (Función de unread)
$comment_counts = get_comment_counts($pdo, $fase_activa, $user_id);

// --- RASTREADOR DE PROGRESO DE PREDICCIÓN ---
$total_matches_generated = $pdo->query("SELECT COUNT(id) FROM matches WHERE phase != 'third_place'")->fetchColumn();
$stmt_my_preds = $pdo->prepare("SELECT COUNT(id) FROM predictions WHERE user_id = ?");
$stmt_my_preds->execute([$user_id]);
$my_predictions_count = $stmt_my_preds->fetchColumn();

$percentage_complete = ($total_matches_generated > 0) ? round(($my_predictions_count / $total_matches_generated) * 100) : 0;
// ------------------------------------------
?>
<?php
// Usamos el ID del usuario como semilla para que su avatar sea siempre el mismo
$avatar_seed = $jugador['id']; // o $_SESSION['user_id'] si es el perfil
$avatar_url = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $avatar_seed;
?>