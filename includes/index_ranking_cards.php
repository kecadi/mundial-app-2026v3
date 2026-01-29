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