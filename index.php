<?php
// index.php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. SEGURIDAD: Si no hay usuario logueado, mandar al login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['nombre'];
$rol_usuario = $_SESSION['role'];

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
$stmt_log = $pdo->query("SELECT description, created_at FROM admin_activity_log ORDER BY created_at DESC LIMIT 5");
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mundial 2026 - Inicio</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .flag-icon { font-size: 2rem; }
        
        /* Estilos para el Fondo del Estadio */
        .card-match-bg {
            background-size: cover;
            background-position: center;
            position: relative;
            color: white; 
            border: none; 
        }

        /* Capa de atenuación sobre la imagen */
        .card-match-bg::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(10, 10, 10, 0.6); /* Oscurecer la imagen */
            z-index: 1; 
            border-radius: 0.375rem; 
        }

        /* Asegura que el contenido esté sobre la capa atenuada */
        .card-match-content {
            position: relative;
            z-index: 2;
        }
        .card-header, .card-body { 
            background: transparent !important; }
        
            /* Efecto de zoom suave para las caritas de los jugadores */
        .player-avatar {
            transition: transform 0.2s; /* Hace que el movimiento sea fluido */
            cursor: pointer;
        }

        .player-avatar:hover {
            transform: scale(1.5); /* Aumenta el tamaño un 50% */
            z-index: 10;           /* Asegura que la foto ampliada quede por encima de las otras */
            position: relative;
        }
    </style>
</head>
<body class="bg-light">

<?php 
    date_default_timezone_set('Europe/Madrid'); // FIX: Aseguramos la zona horaria del servidor
    $current_page = 'home'; 
    include 'includes/navbar.php'; 
?>

<div class="container">
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'guardado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ ¡Tu pronóstico se ha guardado correctamente!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-primary h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h6 class="text-muted text-uppercase">Mis Puntos</h6>
                    <h1 class="display-3 fw-bold text-primary mb-0"><?php echo $mis_puntos; ?></h1>
                    <div class="mt-3">
                        <span class="badge bg-info text-dark">Posición #<?php echo $mi_posicion; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold border-bottom-0">
                    <i class="bi bi-trophy-fill text-warning"></i> Ranking Familiar
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Jugador</th>
                                <th class="text-end pe-4">Puntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pos_rank = 1;
                            foreach($ranking as $jugador): 
                                $es_mi_usuario = ($jugador['nombre'] === $nombre_usuario);
                                $clase_fila = $es_mi_usuario ? 'table-primary fw-bold' : '';
                                
                                // --- GENERADOR DE AVATAR AUTOMÁTICO ---
                                // Usamos el ID del jugador para que su cara sea única y persistente
                                $avatar_url = "https://api.dicebear.com/7.x/fun-emoji/svg?seed=" . $jugador['id'];
                            ?>
                            <tr class="<?php echo $clase_fila; ?>">
                                <td class="ps-4">
                                    <?php echo $jugador['current_rank']; ?>
                                    <?php if ($jugador['movement'] > 0): ?>
                                        <i class="bi bi-caret-up-fill text-success" title="Subió <?php echo $jugador['movement']; ?> posiciones"></i>
                                    <?php elseif ($jugador['movement'] < 0): ?>
                                        <i class="bi bi-caret-down-fill text-danger" title="Bajó <?php echo abs($jugador['movement']); ?> posiciones"></i>
                                    <?php else: ?>
                                        <i class="bi bi-dash-lg text-muted" title="Mantuvo posición"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <img src="<?php echo $avatar_url; ?>" 
                                        class="rounded-circle me-2 bg-light border" 
                                        style="width: 32px; height: 32px;" 
                                        alt="Avatar">
                                    
                                    <?php echo htmlspecialchars($jugador['nombre']); ?>
                                    
                                    <?php if($es_mi_usuario) echo ' <span class="badge bg-primary ms-1">Tú</span>'; ?>
                                    <?php if($jugador['id'] == $current_rival_id): ?>
                                        <span class="badge bg-danger ms-1">RIVAL 🔥</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4 fw-bold"><?php echo $jugador['total_puntos']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(count($ranking) == 0): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">Aún no hay jugadores.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark fw-bold">
                    🧠 Quiz Diario de 10 Segundos
                </div>
                <div class="card-body">
                    <?php if ($quiz_answered): ?>
                        <div class="alert alert-success text-center fw-bold">
                            🎉 ¡Ya respondiste hoy! Ganaste <?php echo $quiz_points_today; ?> puntos.
                        </div>
                    <?php elseif ($quiz_data): 
                        // Al iniciar, guardamos la hora de inicio en la sesión para la verificación de 10 segundos
                        $_SESSION['quiz_start_time'] = time();
                        $_SESSION['quiz_question_id'] = $quiz_data['id'];
                    ?>
                        <form id="quizForm" action="save_quiz_response.php" method="POST">
                            <input type="hidden" name="question_id" value="<?php echo $quiz_data['id']; ?>">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-danger mb-0">¡Rápido! Tienes <span id="timer" class="display-6 fw-bold">10</span> segundos.</h5>
                                <button type="submit" id="quizSubmitBtn" class="btn btn-success" disabled>Responder</button>
                            </div>
                            
                            <p class="lead fw-bold text-dark"><?php echo htmlspecialchars($quiz_data['question']); ?></p>
                            
                            <div class="row" id="optionsContainer">
                                <?php $options = ['A', 'B', 'C', 'D']; ?>
                                <?php foreach ($options as $opt): ?>
                                    <div class="col-md-6 mb-2">
                                        <input type="radio" name="answer" id="opt_<?php echo $opt; ?>" value="<?php echo $opt; ?>" class="btn-check" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 text-start" for="opt_<?php echo $opt; ?>">
                                            <?php echo $opt; ?>) <?php echo htmlspecialchars($quiz_data['option_' . strtolower($opt)]); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            La pregunta diaria de hoy aún no está programada.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($next_matches_data)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card text-white shadow-lg" style="background-color: #33a1a9ff !important;"> 
                    <div class="card-body text-center">
                        <h5 class="card-title fw-bold">⚠️ BLOQUEO INMINENTE DEL PRONÓSTICO</h5>
                        
                        <?php if (count($next_matches_data) > 1): ?>
                            <p class="card-text mb-2 fw-bold text-warning">
                                ¡ATENCIÓN! Se cerrarán <?php echo count($next_matches_data); ?> partidos simultáneamente.
                            </p>
                        <?php endif; ?>
                        
                        <ul class="list-unstyled mb-3 small fw-light">
                            <?php foreach($next_matches_data as $match): ?>
                                <li class="mb-2 d-flex align-items-center justify-content-center">
                                    <?php 
                                        // Definimos las rutas de las banderas
                                        $ruta_h = "assets/img/banderas/" . $match['home_flag'] . ".png";
                                        $ruta_a = "assets/img/banderas/" . $match['away_flag'] . ".png";
                                    ?>

                                    <?php if(file_exists($ruta_h)): ?>
                                        <img src="<?php echo $ruta_h; ?>" class="me-2 shadow-sm" style="width: 22px; border-radius: 3px; border: 1px solid rgba(255,255,255,0.2);">
                                    <?php endif; ?>

                                    <span><?php echo htmlspecialchars($match['home']); ?> vs <?php echo htmlspecialchars($match['away']); ?></span>

                                    <?php if(file_exists($ruta_a)): ?>
                                        <img src="<?php echo $ruta_a; ?>" class="ms-2 shadow-sm" style="width: 22px; border-radius: 3px; border: 1px solid rgba(255,255,255,0.2);">
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <p class="card-text mb-1">Queda:</p>
                        <h1 id="countdown-timer" class="display-3 fw-bolder">--:--:--</h1>
                        <small class="small text-white-50">Hora de Cierre: <?php echo date('d/m H:i:s', $next_lock_timestamp); ?></small>
                        
                        <input type="hidden" id="lock-target-ms" value="<?php echo $time_until_lock_ms; ?>">
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h5 class="card-title text-success fw-bold">Tu Progreso de Quiniela</h5>
                    <p class="card-text">
                        Has completado **<?php echo $my_predictions_count; ?>** de **<?php echo $total_matches_generated; ?>** pronósticos totales.
                    </p>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" 
                             style="width: <?php echo $percentage_complete; ?>%;" aria-valuenow="<?php echo $percentage_complete; ?>" aria-valuemin="0" aria-valuemax="100">
                             <?php echo $percentage_complete; ?>% Completado
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-dark fw-bold">
                    📰 Últimas Actualizaciones del Campeonato
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (!empty($latest_updates)): ?>
                        <?php foreach($latest_updates as $log): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center small">
                                <span class="text-dark">
                                    <i class="bi bi-clock-fill text-muted me-2"></i> 
                                    <?php echo htmlspecialchars($log['description']); ?>
                                </span>
                                <small class="badge bg-light text-muted">
                                    <?php echo date('d/m H:i', strtotime($log['created_at'])); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted">No hay actualizaciones recientes.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs">
                <?php foreach($nombres_fases as $clave => $nombre): 
                    $activo = ($fase_activa === $clave) ? 'active fw-bold' : '';
                    $estilo = ($fase_activa === $clave) ? 'border-top: 3px solid #0d6efd;' : '';
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activo; ?> text-dark" 
                           style="<?php echo $estilo; ?>"
                           href="index.php?fase=<?php echo $clave; ?>">
                           <?php echo $nombre; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0 text-secondary">
            Partidos: <?php echo $nombres_fases[$fase_activa]; ?>
        </h4>
        <span class="badge bg-primary"><?php echo count($partidos); ?> partidos</span>
    </div>

    <?php 
    // Agrupar partidos por fecha
    $partidos_por_dia = [];
    foreach($partidos as $match) {
        $fecha_dia = date('Y-m-d', strtotime($match['match_date']));
        if (!isset($partidos_por_dia[$fecha_dia])) {
            $partidos_por_dia[$fecha_dia] = [];
        }
        $partidos_por_dia[$fecha_dia][] = $match;
    }
    ?>

    <div class="row">
        <?php if(count($partidos) === 0): ?>
            <div class="col-12"><p class="text-muted">No hay partidos programados en esta fase.</p></div>
        <?php else: ?>
            <?php foreach($partidos_por_dia as $fecha => $partidos_del_dia): ?>
                
                <!-- Encabezado del día -->
                <div class="col-12 mb-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <hr class="my-0" style="border-top: 2px solid #dee2e6;">
                        </div>
                        <div class="px-4">
                            <h5 class="mb-0 fw-bold" style="color: #0d6efd;">
                                <i class="bi bi-calendar3"></i> 
                                <?php 
                                    $fecha_obj = new DateTime($fecha);
                                    $hoy = new DateTime();
                                    $hoy->setTime(0, 0, 0);
                                    $manana = (clone $hoy)->modify('+1 day');
                                    
                                    if ($fecha_obj->format('Y-m-d') === $hoy->format('Y-m-d')) {
                                        echo 'HOY - ' . $fecha_obj->format('d/m/Y');
                                    } elseif ($fecha_obj->format('Y-m-d') === $manana->format('Y-m-d')) {
                                        echo 'MAÑANA - ' . $fecha_obj->format('d/m/Y');
                                    } else {
                                        // Días de la semana en español
                                        $dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                        
                                        $dia_semana = $dias_semana[$fecha_obj->format('w')];
                                        $dia = $fecha_obj->format('d');
                                        $mes = $meses[(int)$fecha_obj->format('m')];
                                        
                                        echo strtoupper($dia_semana) . ' ' . $dia . ' de ' . $mes;
                                    }
                                ?>
                            </h5>
                        </div>
                        <div class="flex-grow-1">
                            <hr class="my-0" style="border-top: 2px solid #dee2e6;">
                        </div>
                    </div>
                </div>

                <!-- Partidos de ese día -->
                <?php foreach($partidos_del_dia as $match): 
                    // --- OBTENER ESTADOS BÁSICOS (DEBE IR PRIMERO) ---
                    $ya_pronosticado = !is_null($match['predicted_home_score']);
                    $partido_terminado = ($match['status'] === 'finished'); 
                    $es_eliminatoria = ($match['phase'] !== 'group');

                    // --- LÓGICA DE BLOQUEO POR TIEMPO ---
                    $minutos_bloqueo = 1;
                    $tiempo_bloqueo_seg = $minutos_bloqueo * 60; 
                    $fecha_inicio = strtotime($match['match_date']);
                    
                    $partido_bloqueado = (time() >= ($fecha_inicio - $tiempo_bloqueo_seg));
                    
                    // --- LÓGICA FINAL DE ACCIÓN ---
                    $se_puede_predecir = !$partido_terminado && !$partido_bloqueado; 
                    $disable_button = !$se_puede_predecir;
                    
                    // --- VARIABLES RESTANTES ---
                    $fecha = date('d/m H:i', $fecha_inicio); 
                    $home_id = $match['team_home_id'];
                    $away_id = $match['team_away_id'];
                    $qualifier_id = $match['predicted_qualifier_id'] ?? '';
                    
                    // Esto asegura que busque dentro de la carpeta de tu proyecto actual
                    $imagen_fondo = "assets/img/stadiums/" . basename($match['image_url'] ?? 'default.jpg');
                    $borde_card = $partido_terminado ? 'border-secondary' : '';
                ?>

                <div class="col-lg-6 mb-4">
                    <div class="card card-match shadow-sm <?php echo $borde_card; ?> card-match-bg" 
                        style="background-image: url('<?php echo $imagen_fondo; ?>');">
                        
                        <div class="card-match-content">
                            <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center">
                                <span class="badge bg-dark">
                                    Grupo <?php echo $match['group_name']; ?>
                                </span>
                                <small class="text-white">
                                    <i class="bi bi-calendar-event"></i> <?php echo $fecha; ?> | <?php echo $match['stadium']; ?>
                                </small>
                            </div>
                            
                            <div class="card-body text-center text-white">
                                <div class="row align-items-center text-white">
                                    
                                    <div class="col-4 text-start">
                                        <div class="flag-container mb-2">
                                            <?php 
                                            $flag_home = "assets/img/banderas/" . $match['home_flag'] . ".png";
                                            if (file_exists($flag_home)): ?>
                                                <img src="<?php echo $flag_home; ?>" 
                                                    alt="<?php echo $match['home_flag']; ?>" 
                                                    class="shadow"
                                                    style="width: 50px; height: auto; border-radius: 8px; border: 0px solid rgba(255,255,255,0.3); object-fit: cover;">
                                            <?php else: ?>
                                                <span class="fw-bold text-warning" style="font-size: 1.2rem;"><?php echo htmlspecialchars($match['home_flag']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="mt-2 mb-1 fw-bold"><?php echo htmlspecialchars($match['home_name']); ?></h5>
                                        
                                        <div class="d-flex justify-content-start gap-1 mt-2">
                                            <?php 
                                            $home_players = explode(',', $match['home_players'] ?? '');
                                            foreach(array_slice($home_players, 0, 2) as $p_name): 
                                                $p_name = trim($p_name);
                                                if(!empty($p_name)):
                                                    $ruta_p = "assets/img/players/" . $p_name . ".png";
                                                    $p_img = file_exists($ruta_p) ? $ruta_p : "assets/img/players/default_player.png";
                                            ?>
                                                <img src="<?php echo $p_img; ?>" 
                                                    class="rounded-circle border border-1 border-white shadow-sm player-avatar" 
                                                    style="width: 30px; height: 30px; object-fit: cover;" 
                                                    title="<?php echo htmlspecialchars($p_name); ?>">
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-4">
                                        <?php 
                                            $rival_by_me_id = $match['rival_id_by_me'];
                                            $rival_me_id = $match['rival_id_me'];
                                            $rival_by_me_name = $user_name_map[$rival_by_me_id] ?? 'Error';
                                            $rival_me_name = $user_name_map[$rival_me_id] ?? 'Error';
                                        ?>

                                        <?php if ($match['challenged_by_me_id']): ?>
                                            <span class="badge bg-light text-dark mb-2 px-3 py-2 fw-bold" title="¡Has retado a <?php echo $rival_by_me_name; ?>!">
                                                ⚔️ Has Retado a <?php echo htmlspecialchars($rival_by_me_name); ?>
                                            </span>
                                        <?php elseif ($match['challenged_me_id']): ?>
                                            <span class="badge bg-dark mb-2 px-3 py-2 fw-bold" title="¡Te han retado!">
                                                ⚔️ Te ha Retado <?php echo htmlspecialchars($rival_me_name); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if($partido_terminado): ?>
                                            <div class="mb-1 badge bg-secondary">Finalizado</div>
                                            <h3 class="fw-bold mb-0"><?php echo $match['real_home']; ?> - <?php echo $match['real_away']; ?></h3>
                                            <?php if($ya_pronosticado): ?>
                                                <small class="d-block mt-1 text-success fw-bold">+<?php echo $match['points_earned']; ?> pts</small>
                                                <small class="text-white" style="font-size: 0.8rem;">(Tú: <?php echo $match['predicted_home_score']; ?>-<?php echo $match['predicted_away_score']; ?>)</small>
                                            <?php else: ?>
                                                <small class="text-danger d-block mt-1">No participaste</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if($ya_pronosticado): ?>
                                                <div class="badge bg-success mb-2">Pronosticado</div>
                                                <h4 class="text-warning"><?php echo $match['predicted_home_score']; ?> - <?php echo $match['predicted_away_score']; ?></h4>
                                                <button class="btn btn-outline-light btn-sm btn-predict mt-1 shadow-sm" 
                                                        data-id="<?php echo $match['match_id']; ?>" 
                                                        data-home="<?php echo $match['home_name']; ?>" 
                                                        data-away="<?php echo $match['away_name']; ?>" 
                                                        data-score-home="<?php echo $match['predicted_home_score']; ?>" 
                                                        data-score-away="<?php echo $match['predicted_away_score']; ?>" 
                                                        data-phase="<?php echo $match['phase']; ?>" 
                                                        data-home-id="<?php echo $match['team_home_id']; ?>" 
                                                        data-away-id="<?php echo $match['team_away_id']; ?>" 
                                                        <?php echo $disable_button ? 'disabled' : ''; ?>>
                                                    ✏️ Editar
                                                </button>
                                            <?php else: ?>
                                                <h3 class="fw-bold text-white mb-2">VS</h3>
                                                <button class="btn btn-primary btn-predict shadow" 
                                                        data-id="<?php echo $match['match_id']; ?>" 
                                                        data-home="<?php echo $match['home_name']; ?>" 
                                                        data-away="<?php echo $match['away_name']; ?>" 
                                                        data-phase="<?php echo $match['phase']; ?>" 
                                                        data-home-id="<?php echo $match['team_home_id']; ?>" 
                                                        data-away-id="<?php echo $match['team_away_id']; ?>" 
                                                        <?php echo $disable_button ? 'disabled' : ''; ?>>
                                                    🎲 Pronosticar
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php 
                                            $match_comment_count = $comment_counts[$match['match_id']] ?? 0;
                                            $puede_usar_comodin = $wildcard_available && !$partido_terminado && !$partido_bloqueado && $ya_pronosticado;
                                            $mostrar_badge_activo = (!$wildcard_available && $wildcard_match_id == $match['match_id']);
                                            
                                            if ($puede_usar_comodin || $mostrar_badge_activo || ($match['status'] !== 'scheduled' || $ya_pronosticado)) {
                                                echo '<div class="mt-3 d-flex justify-content-center flex-wrap gap-1">';
                                                if ($puede_usar_comodin) {
                                                    echo '<form method="POST" action="save_wildcard.php" class="d-inline"><input type="hidden" name="match_id" value="'.$match['match_id'].'"><button type="submit" class="btn btn-sm btn-danger fw-bold" onclick="return confirm(\'¿Usar comodín x2?\')"><i class="bi bi-award-fill"></i> x2</button></form>';
                                                }
                                                if ($mostrar_badge_activo) {
                                                    echo '<span class="badge bg-danger px-2 py-2 fw-bold"><i class="bi bi-award-fill"></i> x2 ACTIVO</span>';
                                                }
                                                if ($match['status'] !== 'scheduled' || $ya_pronosticado) {
                                                    echo '<a href="match_consensus.php?match_id='.$match['match_id'].'" class="btn btn-sm btn-outline-info"><i class="bi bi-bar-chart-fill"></i></a>';
                                                    echo '<a href="match_comments.php?match_id='.$match['match_id'].'" class="btn btn-sm btn-outline-warning position-relative"><i class="bi bi-chat-text-fill"></i>';
                                                    if ($match_comment_count > 0) echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">'.$match_comment_count.'</span>';
                                                    echo '</a>';
                                                }
                                                echo '</div>';
                                            }
                                        ?>
                                    </div>
                                    
                                    <div class="col-4 text-end">
                                        <div class="flag-container mb-2">
                                            <?php 
                                            $flag_away = "assets/img/banderas/" . $match['away_flag'] . ".png";
                                            if (file_exists($flag_away)): ?>
                                                <img src="<?php echo $flag_away; ?>" 
                                                    alt="<?php echo $match['away_flag']; ?>" 
                                                    class="shadow"
                                                    style="width: 50px; height: auto; border-radius: 8px; border: 0px solid rgba(255,255,255,0.3); object-fit: cover;">
                                            <?php else: ?>
                                                <span class="fw-bold text-warning" style="font-size: 1.2rem;"><?php echo htmlspecialchars($match['away_flag']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="mt-2 mb-1 fw-bold"><?php echo htmlspecialchars($match['away_name']); ?></h5>
                                        
                                        <div class="d-flex justify-content-end gap-1 mt-2">
                                            <?php 
                                            $away_players = explode(',', $match['away_players'] ?? '');
                                            foreach(array_slice($away_players, 0, 2) as $p_name): 
                                                $p_name = trim($p_name);
                                                if(!empty($p_name)):
                                                    $ruta_v = "assets/img/players/" . $p_name . ".png";
                                                    $p_img = file_exists($ruta_v) ? $ruta_v : "assets/img/players/default_player.png";
                                            ?>
                                                <img src="<?php echo $p_img; ?>" 
                                                    class="rounded-circle border border-1 border-white shadow-sm player-avatar" 
                                                    style="width: 30px; height: 30px; object-fit: cover;" 
                                                    title="<?php echo htmlspecialchars($p_name); ?>">
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div> 

<div class="modal fade" id="predictModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="save_prediction.php" method="POST">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Tu Pronóstico</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-4">
            <input type="hidden" name="match_id" id="modal_match_id">
            
            <h4 class="mb-4 fw-bold text-center text-dark">
                <span id="modal_home_name">Local</span> 
                <span class="text-muted mx-2">vs</span> 
                <span id="modal_away_name">Visitante</span>
            </h4>
            
            <div class="row justify-content-center align-items-center g-2">
                <div class="col-4">
                    <input type="number" name="score_home" id="modal_score_home" 
                           class="form-control form-control-lg text-center fw-bold text-primary" 
                           placeholder="0" min="0" required>
                    <small class="text-muted">Local</small>
                </div>
                <div class="col-1 fw-bold fs-4 text-dark">:</div>
                <div class="col-4">
                    <input type="number" name="score_away" id="modal_score_away" 
                           class="form-control form-control-lg text-center fw-bold text-primary" 
                           placeholder="0" min="0" required>
                    <small class="text-muted">Visitante</small>
                </div>
            </div>
            
            <div class="mb-3 text-center mt-4" id="qualifier_selector" style="display: none;">
                <hr>
                <label class="form-label fw-bold">¿Quién pasa en caso de empate?</label>
                <select name="qualifier_id" id="modal_qualifier_id" class="form-select mx-auto" style="width: 80%;">
                </select>
            </div>

          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success px-4">💾 Guardar Resultado</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const predictModal = document.getElementById('predictModal');
    const bsModal = new bootstrap.Modal(predictModal);
    
    // Referencias a los campos del modal
    const scoreHomeInput = document.getElementById('modal_score_home');
    const scoreAwayInput = document.getElementById('modal_score_away');
    const qualifierSelectorDiv = document.getElementById('qualifier_selector');
    const qualifierDropdown = document.getElementById('modal_qualifier_id');

    let currentMatchPhase = 'group'; 

    // FUNCIÓN PRINCIPAL PARA CONTROLAR LA VISIBILIDAD (Eliminatoria + Empate)
    function checkQualifierVisibility() {
        const isKnockout = (currentMatchPhase !== 'group');

        if (isKnockout) {
            const scoreHome = scoreHomeInput.value;
            const scoreAway = scoreAwayInput.value;
            const isDraw = (scoreHome !== '' && scoreAway !== '' && scoreHome === scoreAway);

            if (isDraw) {
                qualifierSelectorDiv.style.display = 'block';
                qualifierDropdown.required = true;
            } else {
                qualifierSelectorDiv.style.display = 'none';
                qualifierDropdown.required = false;
            }
        } else {
            qualifierSelectorDiv.style.display = 'none';
            qualifierDropdown.required = false;
        }
    }

    // Escucha eventos en los inputs de score
    scoreHomeInput.addEventListener('input', checkQualifierVisibility);
    scoreAwayInput.addEventListener('input', checkQualifierVisibility);

    // LÓGICA AL ABRIR EL MODAL (rellenar datos)
    document.querySelectorAll('.btn-predict').forEach(button => {
        button.addEventListener('click', function() {
            // Obtener datos del botón
            const phase = this.getAttribute('data-phase'); 
            const homeId = this.getAttribute('data-home-id');
            const awayId = this.getAttribute('data-away-id');
            const qualifierId = this.getAttribute('data-qualifier-id'); // ID del clasificado si está editando
            
            const scoreH = this.getAttribute('data-score-home');
            const scoreA = this.getAttribute('data-score-away');

            // 1. Almacenar fase activa globalmente y llenar campos
            currentMatchPhase = phase; 
            document.getElementById('modal_match_id').value = this.getAttribute('data-id');
            document.getElementById('modal_home_name').textContent = this.getAttribute('data-home');
            document.getElementById('modal_away_name').textContent = this.getAttribute('data-away');
            scoreHomeInput.value = scoreH !== null ? scoreH : '';
            scoreAwayInput.value = scoreA !== null ? scoreA : '';

            // 2. Llenar Dropdown del Clasificado
            qualifierDropdown.innerHTML = `
                <option value="">-- Elige Clasificado --</option>
                <option value="${homeId}">${this.getAttribute('data-home')}</option>
                <option value="${awayId}">${this.getAttribute('data-away')}</option>
            `;
            // Cargar el valor predicho del clasificado si existe
            if (qualifierId) {
                qualifierDropdown.value = qualifierId;
            } else {
                qualifierDropdown.value = "";
            }

            // 3. Ejecutar el chequeo inicial al abrir el modal (para mostrar/ocultar selector)
            checkQualifierVisibility(); 
            bsModal.show();
        });
    });
    // Lógica del Temporizador para el Quiz
    if (document.getElementById('timer')) {
        let timeLeft = 10;
        const timerElement = document.getElementById('timer');
        const submitBtn = document.getElementById('quizSubmitBtn');
        const optionsContainer = document.getElementById('optionsContainer');
        const form = document.getElementById('quizForm');
        
        // Deshabilita el botón de enviar hasta que se elija una opción
        document.querySelectorAll('input[name="answer"]').forEach(radio => {
            radio.addEventListener('change', () => {
                submitBtn.disabled = false;
            });
        });

        const timerInterval = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerElement.textContent = 0;
                submitBtn.disabled = true;
                submitBtn.textContent = '¡Tiempo Agotado!';
                optionsContainer.classList.add('disabled-options'); // Opcional, para estilo
                form.submit(); // Envía la respuesta aunque esté mal (cero puntos)
            }
        }, 1000);
    }

    // index.php (Dentro del bloque <script>)

    // --- Lógica del Contador Regresivo (Countdown) ---
    const countdownElement = document.getElementById('countdown-timer');
    const lockTargetMs = document.getElementById('lock-target-ms');

    if (countdownElement && lockTargetMs) {
        let timeLeftMs = parseInt(lockTargetMs.value);

        const updateTimer = () => {
            timeLeftMs -= 1000;

            if (timeLeftMs <= 0) {
                clearInterval(countdownInterval);
                countdownElement.textContent = "¡CERRADO!";
                // Opcional: Recargar la página para deshabilitar los botones
                setTimeout(() => { window.location.reload(); }, 2000); 
                return;
            }

            const totalSeconds = Math.floor(timeLeftMs / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            const formatTime = (t) => t < 10 ? "0" + t : t;

            countdownElement.textContent = `${formatTime(hours)}:${formatTime(minutes)}:${formatTime(seconds)}`;
        };

        // Actualizar inmediatamente y luego cada segundo
        updateTimer();
        const countdownInterval = setInterval(updateTimer, 1000);
    }
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>