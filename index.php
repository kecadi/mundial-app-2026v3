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

require_once 'includes/index_logic.php';
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
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php 
    date_default_timezone_set('Europe/Madrid'); // FIX: Aseguramos la zona horaria del servidor
    $current_page = 'home'; 
    include 'includes/navbar.php'; 
?>

<div class="container">
    <?php include 'includes/alerts.php'; ?>
    <?php include 'includes/index_ranking_cards.php'; ?>
    <?php include 'includes/index_quiz_section.php'; ?>
    <?php include 'includes/index_lock_alert.php'; ?>
    <?php include 'includes/index_progress_bar.php'; ?>

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
<?php include 'includes/modal_predict.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/index_logic.js'; ?>
<?php include 'includes/footer.php'; ?>
</body>
</html>