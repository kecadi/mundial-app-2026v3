<?php
// includes/index_matches_list.php

// 1. PESTAÑAS DE NAVEGACIÓN POR FASES
?>
<div class="card shadow-sm mb-4 border-0 rounded-4 overflow-hidden">
    <div class="card-header bg-white p-0">
        <ul class="nav nav-tabs nav-fill card-header-tabs m-0 border-bottom-0">
            <?php foreach($nombres_fases as $clave => $nombre): 
                $es_activo = ($fase_activa === $clave);
                $clase_link = $es_activo ? 'active fw-bold border-top-0 border-start-0 border-end-0 border-bottom border-primary border-3' : 'text-muted';
            ?>
                <li class="nav-item">
                    <a class="nav-link py-3 <?php echo $clase_link; ?>" 
                       href="index.php?fase=<?php echo $clave; ?>"
                       style="<?php echo $es_activo ? 'color: #0d6efd !important; background: #f8fbff;' : ''; ?>">
                       <?php echo $nombre; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 text-secondary">
        Partidos: <span class="fw-bold text-primary"><?php echo $nombres_fases[$fase_activa]; ?></span>
    </h4>
    <span class="badge bg-primary rounded-pill px-3"><?php echo count($partidos); ?> partidos</span>
</div>

<?php 
// 2. LÓGICA DE AGRUPACIÓN POR DÍA
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
        <div class="col-12 text-center py-5">
            <i class="bi bi-calendar-x fs-1 text-muted opacity-25"></i>
            <p class="text-muted mt-2">No hay partidos programados en esta fase.</p>
        </div>
    <?php else: ?>
        <?php foreach($partidos_por_dia as $fecha => $partidos_del_dia): ?>
            
            <div class="col-12 mb-4">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1"><hr class="my-0" style="border-top: 2px solid #dee2e6;"></div>
                    <div class="px-4 text-center">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-calendar3 me-2"></i> 
                            <?php 
                                $fecha_obj = new DateTime($fecha);
                                $hoy = new DateTime(); $hoy->setTime(0, 0, 0);
                                $manana = (clone $hoy)->modify('+1 day');
                                
                                if ($fecha_obj->format('Y-m-d') === $hoy->format('Y-m-d')) {
                                    echo 'HOY - ' . $fecha_obj->format('d/m/Y');
                                } elseif ($fecha_obj->format('Y-m-d') === $manana->format('Y-m-d')) {
                                    echo 'MAÑANA - ' . $fecha_obj->format('d/m/Y');
                                } else {
                                    $dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    echo strtoupper($dias_semana[$fecha_obj->format('w')]) . ' ' . $fecha_obj->format('d') . ' de ' . $meses[(int)$fecha_obj->format('m')];
                                }
                            ?>
                        </h5>
                    </div>
                    <div class="flex-grow-1"><hr class="my-0" style="border-top: 2px solid #dee2e6;"></div>
                </div>
            </div>

            <?php foreach($partidos_del_dia as $match): 
                $ya_pronosticado = !is_null($match['predicted_home_score']);
                $partido_terminado = ($match['status'] === 'finished'); 
                $es_eliminatoria = ($match['phase'] !== 'group');

                // Lógica de Bloqueo
                $minutos_bloqueo = 2; // Ajustado a 2 como en tus reglas
                $tiempo_bloqueo_seg = $minutos_bloqueo * 60; 
                $fecha_inicio = strtotime($match['match_date']);
                $partido_bloqueado = (time() >= ($fecha_inicio - $tiempo_bloqueo_seg));
                
                $se_puede_predecir = !$partido_terminado && !$partido_bloqueado; 
                $disable_button = !$se_puede_predecir;
                
                $fecha_formateada = date('H:i', $fecha_inicio); 
                $imagen_fondo = "assets/img/stadiums/" . basename($match['image_url'] ?? 'default.jpg');
                $borde_card = $partido_terminado ? 'border-secondary' : '';
            ?>

            <div class="col-lg-6 mb-4">
                <div class="card card-match shadow-sm <?php echo $borde_card; ?> card-match-bg" 
                     style="background-image: url('<?php echo $imagen_fondo; ?>');">
                    
                    <div class="card-match-content p-3">
                        <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center p-0 mb-3">
                            <span class="badge bg-dark opacity-75">
                                <?php echo ($es_eliminatoria) ? strtoupper($match['phase']) : 'GRUPO ' . $match['group_name']; ?>
                            </span>
                            <small class="text-white fw-bold">
                                <i class="bi bi-clock me-1"></i> <?php echo $fecha_formateada; ?> | <?php echo htmlspecialchars($match['stadium']); ?>
                            </small>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="row align-items-center text-white text-center">
                                <div class="col-4">
                                    <img src="assets/img/banderas/<?php echo $match['home_flag']; ?>.png" 
                                         class="shadow-sm mb-2" style="width: 45px; border-radius: 5px;">
                                    <h6 class="fw-bold mb-1 text-truncate"><?php echo $match['home_name']; ?></h6>
                                    
                                    <div class="d-flex justify-content-center gap-1 mt-1">
                                        <?php 
                                        $home_players = explode(',', $match['home_players'] ?? '');
                                        foreach(array_slice($home_players, 0, 2) as $p_name): 
                                            $p_name = trim($p_name);
                                            if(!empty($p_name)):
                                                $ruta_p = "assets/img/players/" . $p_name . ".png";
                                                $p_img = file_exists($ruta_p) ? $ruta_p : "assets/img/players/default_player.png";
                                        ?>
                                            <img src="<?php echo $p_img; ?>" class="rounded-circle border border-white" style="width: 24px; height: 24px; object-fit: cover;" title="<?php echo htmlspecialchars($p_name); ?>">
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-4">
                                    <?php 
                                        $rival_by_me_name = $user_name_map[$match['rival_id_by_me']] ?? '';
                                        $rival_me_name = $user_name_map[$match['rival_id_me']] ?? '';
                                    ?>

                                    <?php if ($match['challenged_by_me_id']): ?>
                                        <div class="badge bg-primary mb-2 w-100 text-truncate" title="Retaste a <?php echo $rival_by_me_name; ?>">⚔️ vs <?php echo $rival_by_me_name; ?></div>
                                    <?php elseif ($match['challenged_me_id']): ?>
                                        <div class="badge bg-danger mb-2 w-100 text-truncate" title="Retado por <?php echo $rival_me_name; ?>">⚔️ vs <?php echo $rival_me_name; ?></div>
                                    <?php endif; ?>

                                    <?php if($partido_terminado): ?>
                                        <div class="mb-1 small opacity-75">Final</div>
                                        <h3 class="fw-bold mb-0"><?php echo $match['real_home']; ?>-<?php echo $match['real_away']; ?></h3>
                                        <?php if($ya_pronosticado): ?>
                                            <span class="badge bg-success mt-1">+<?php echo $match['points_earned']; ?> pts</span>
                                            <div class="small mt-1">(Tú: <?php echo $match['predicted_home_score']; ?>-<?php echo $match['predicted_away_score']; ?>)</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if($ya_pronosticado): ?>
                                            <div class="badge bg-success mb-1">Tu apuesta</div>
                                            <h4 class="text-warning fw-bold mb-1"><?php echo $match['predicted_home_score']; ?>-<?php echo $match['predicted_away_score']; ?></h4>
                                            <button class="btn btn-sm btn-outline-light btn-predict px-3" 
                                                    data-id="<?php echo $match['match_id']; ?>" 
                                                    data-home="<?php echo $match['home_name']; ?>" 
                                                    data-away="<?php echo $match['away_name']; ?>" 
                                                    data-score-home="<?php echo $match['predicted_home_score']; ?>" 
                                                    data-score-away="<?php echo $match['predicted_away_score']; ?>" 
                                                    data-phase="<?php echo $match['phase']; ?>" 
                                                    data-home-id="<?php echo $match['team_home_id']; ?>" 
                                                    data-away-id="<?php echo $match['team_away_id']; ?>" 
                                                    <?php echo $disable_button ? 'disabled' : ''; ?>>Editar</button>
                                        <?php else: ?>
                                            <h3 class="fw-bold mb-2">VS</h3>
                                            <button class="btn btn-sm btn-primary btn-predict shadow-sm px-3" 
                                                    data-id="<?php echo $match['match_id']; ?>" 
                                                    data-home="<?php echo $match['home_name']; ?>" 
                                                    data-away="<?php echo $match['away_name']; ?>" 
                                                    data-phase="<?php echo $match['phase']; ?>" 
                                                    data-home-id="<?php echo $match['team_home_id']; ?>" 
                                                    data-away-id="<?php echo $match['team_away_id']; ?>" 
                                                    <?php echo $disable_button ? 'disabled' : ''; ?>>Apostar</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="col-4">
                                    <img src="assets/img/banderas/<?php echo $match['away_flag']; ?>.png" 
                                         class="shadow-sm mb-2" style="width: 45px; border-radius: 5px;">
                                    <h6 class="fw-bold mb-1 text-truncate"><?php echo $match['away_name']; ?></h6>
                                    
                                    <div class="d-flex justify-content-center gap-1 mt-1">
                                        <?php 
                                        $away_players = explode(',', $match['away_players'] ?? '');
                                        foreach(array_slice($away_players, 0, 2) as $p_name): 
                                            $p_name = trim($p_name);
                                            if(!empty($p_name)):
                                                $ruta_v = "assets/img/players/" . $p_name . ".png";
                                                $p_img = file_exists($ruta_v) ? $ruta_v : "assets/img/players/default_player.png";
                                        ?>
                                            <img src="<?php echo $p_img; ?>" class="rounded-circle border border-white" style="width: 24px; height: 24px; object-fit: cover;" title="<?php echo htmlspecialchars($p_name); ?>">
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php 
                            $match_comment_count = $comment_counts[$match['match_id']] ?? 0;
                            $puede_usar_comodin = $wildcard_available && !$partido_terminado && !$partido_bloqueado && $ya_pronosticado;
                            $mostrar_badge_activo = (!$wildcard_available && $wildcard_match_id == $match['match_id']);
                        ?>
                        <div class="mt-3 d-flex justify-content-center flex-wrap gap-2">
                            <?php if ($puede_usar_comodin): ?>
                                <form method="POST" action="save_wildcard.php" class="d-inline">
                                    <input type="hidden" name="match_id" value="<?php echo $match['match_id']; ?>">
                                    <button type="submit" class="btn btn-xs btn-danger fw-bold py-1" onclick="return confirm('¿Usar comodín x2?')">
                                        <i class="bi bi-award-fill"></i> USAR X2
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($mostrar_badge_activo): ?>
                                <span class="badge bg-danger p-2"><i class="bi bi-award-fill"></i> x2 ACTIVADO</span>
                            <?php endif; ?>

                            <?php if ($partido_bloqueado || $ya_pronosticado): ?>
                                <a href="match_consensus.php?match_id=<?php echo $match['match_id']; ?>" class="btn btn-xs btn-info py-1">
                                    <i class="bi bi-bar-chart-fill"></i>
                                </a>
                                <a href="match_comments.php?match_id=<?php echo $match['match_id']; ?>" class="btn btn-xs btn-warning py-1 position-relative">
                                    <i class="bi bi-chat-text-fill"></i>
                                    <?php if ($match_comment_count > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;"><?php echo $match_comment_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div> 
                </div>
            </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; border-radius: 0.2rem; }
.card-match-bg { background-size: cover; background-position: center; border: none; border-radius: 15px; overflow: hidden; position: relative; }
.card-match-content { background: rgba(0, 0, 0, 0.20); height: 100%; width: 100%; transition: background 0.2s; }
.card-match:hover .card-match-content { background: rgba(0, 0, 0, 0.60); }
.player-avatar { transition: transform 0.2s; }
.player-avatar:hover { transform: scale(1.2); z-index: 10; }
</style>