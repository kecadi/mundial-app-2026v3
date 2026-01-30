<?php //if (true): $rival_data = $ranking[0]; ?>
<?php if ($rival_data): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden rounded-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-center flex-grow-1">
                        <img src="https://api.dicebear.com/7.x/fun-emoji/svg?seed=<?php echo $user_id; ?>" class="rounded-circle bg-white border mb-2" style="width: 50px;">
                        <div class="fw-bold text-dark">Tú</div>
                        <div class="h3 fw-bold text-primary mb-0"><?php echo $mis_puntos; ?> <small class="fs-6">pts</small></div>
                    </div>

                    <div class="px-4 text-center">
                        <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold shadow-sm">VS</span>
                        <div class="mt-2 small text-muted fw-bold">DUELO PERSONAL</div>
                    </div>

                    <div class="text-center flex-grow-1">
                        <img src="https://api.dicebear.com/7.x/fun-emoji/svg?seed=<?php echo $rival_data['id']; ?>" class="rounded-circle bg-white border mb-2" style="width: 50px;">
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($rival_data['nombre']); ?></div>
                        <div class="h3 fw-bold text-danger mb-0"><?php echo $rival_data['total_puntos']; ?> <small class="fs-6">pts</small></div>
                    </div>
                </div>

                <?php 
                    $diff = $mis_puntos - $rival_data['total_puntos'];
                    $color_diff = ($diff >= 0) ? 'text-success' : 'text-danger';
                ?>
                <div class="text-center mt-3 border-top pt-2">
                    <span class="small fw-bold <?php echo $color_diff; ?>">
                        <?php if($diff > 0): ?>
                            🔥 ¡Vas ganando por <?php echo $diff; ?> puntos!
                        <?php elseif($diff < 0): ?>
                            📉 Vas perdiendo por <?php echo abs($diff); ?> puntos. ¡Remonta!
                        <?php else: ?>
                            🤝 ¡Empate técnico! Está la cosa reñida.
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>