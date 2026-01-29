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