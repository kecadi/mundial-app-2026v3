<div class="row mb-5 g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-lg h-100 rounded-4 overflow-hidden text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
            <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                <div class="mb-2 opacity-75 text-uppercase small fw-bold letter-spacing-1">Mi Puntuación</div>
                <h1 class="display-2 fw-black mb-0"><?php echo $mis_puntos; ?></h1>
                <div class="mt-3">
                    <span class="badge rounded-pill bg-white text-primary px-3 py-2 shadow-sm">
                        <i class="bi bi-trophy-fill me-1"></i> Posición #<?php echo $mi_posicion; ?>
                    </span>
                </div>
            </div>
            <i class="bi bi-graph-up-arrow position-absolute bottom-0 start-0 mb-n3 ms-n3 opacity-25" style="font-size: 8rem;"></i>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-lg h-100 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Ranking de la Familia</h5>
                <span class="badge bg-light text-muted border"><?php echo count($ranking); ?> participantes</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr class="text-muted small text-uppercase">
                                <th class="ps-4 border-0">Pos</th>
                                <th class="border-0">Jugador</th>
                                <th class="text-end pe-4 border-0">Puntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($ranking as $jugador): 
                                $es_mi_usuario = ($jugador['nombre'] === $nombre_usuario);
                                $avatar_url = "https://api.dicebear.com/7.x/fun-emoji/svg?seed=" . $jugador['id'];
                                
                                // Estilos especiales para el Top 3
                                $color_rank = '';
                                if($jugador['current_rank'] == 1) $color_rank = 'text-warning'; // Oro
                                elseif($jugador['current_rank'] == 2) $color_rank = 'text-secondary'; // Plata
                                elseif($jugador['current_rank'] == 3) $color_rank = 'text-danger'; // Bronce
                            ?>
                            <tr class="<?php echo $es_mi_usuario ? 'bg-primary bg-opacity-10' : ''; ?>" style="transition: background 0.2s;">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold <?php echo $color_rank; ?> me-2 fs-5" style="min-width: 25px;">
                                            <?php echo $jugador['current_rank']; ?>
                                        </span>
                                        <div class="d-flex flex-column" style="font-size: 0.7rem;">
                                            <?php if ($jugador['movement'] > 0): ?>
                                                <span class="text-success"><i class="bi bi-caret-up-fill"></i>+<?php echo $jugador['movement']; ?></span>
                                            <?php elseif ($jugador['movement'] < 0): ?>
                                                <span class="text-danger"><i class="bi bi-caret-down-fill"></i><?php echo $jugador['movement']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative">
                                            <img src="<?php echo $avatar_url; ?>" class="rounded-circle me-3 bg-white border border-2 shadow-sm" style="width: 40px; height: 40px;" alt="Avatar">
                                            <?php if($jugador['current_rank'] == 1): ?>
                                                <span class="position-absolute top-0 start-0 translate-middle-y fs-6">👑</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold <?php echo $es_mi_usuario ? 'text-primary' : 'text-dark'; ?>">
                                                <?php echo htmlspecialchars($jugador['nombre']); ?>
                                            </span>
                                            <?php if($es_mi_usuario) echo ' <span class="badge bg-primary rounded-pill" style="font-size:0.6rem;">TÚ</span>'; ?>
                                            <?php if($jugador['id'] == $current_rival_id): ?>
                                                <span class="badge bg-danger rounded-pill" style="font-size:0.6rem;">RIVAL 🔥</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="fs-5 fw-black <?php echo $es_mi_usuario ? 'text-primary' : 'text-dark'; ?>">
                                        <?php echo number_format($jugador['total_puntos'], 0); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.fw-black { font-weight: 900; }
.letter-spacing-1 { letter-spacing: 1px; }
.card-match-bg { transition: all 0.3s ease; }
.table > :not(caption) > * > * { border-bottom-width: 1px; border-color: #f0f0f0; }
</style>