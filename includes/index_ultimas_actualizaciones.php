<?php
// includes/index_ultimas_actualizaciones.php
global $latest_updates; 
?>

<div class="row mb-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-black text-dark text-uppercase letter-spacing-1">
                    <i class="bi bi-megaphone-fill text-info me-2"></i> Boletín de Actividad
                </h6>
                <span class="badge bg-info-subtle text-info rounded-pill px-3">Vivo</span>
            </div>
            
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($latest_updates) && is_array($latest_updates)): ?>
                        <?php foreach($latest_updates as $index => $log): 
                            // Alternar iconos según el contenido (opcional)
                            $icon = "bi-app-indicator";
                            if(strpos(strtolower($log['description']), 'gol') !== false) $icon = "bi-trophy";
                            if(strpos(strtolower($log['description']), 'ret') !== false) $icon = "bi-swords";
                        ?>
                            <div class="list-group-item py-3 px-4 border-start border-4 <?php echo ($index === 0) ? 'border-info bg-light bg-opacity-25' : 'border-light'; ?>" style="transition: all 0.3s;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 mt-1">
                                            <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="bi <?php echo $icon; ?> text-info small"></i>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <p class="mb-1 text-dark fw-medium" style="line-height: 1.4;">
                                                <?php echo htmlspecialchars($log['description']); ?>
                                            </p>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-clock text-muted" style="font-size: 0.75rem;"></i>
                                                <span class="text-muted" style="font-size: 0.75rem;">
                                                    <?php echo date('d/m H:i', strtotime($log['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if($index === 0): ?>
                                        <span class="badge bg-info rounded-pill small" style="font-size: 0.6rem;">NUEVO</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-5 text-center">
                            <i class="bi bi-chat-dots fs-1 text-muted opacity-25"></i>
                            <p class="text-muted mt-2 mb-0 small">Sin actualizaciones en las últimas horas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-light bg-opacity-50 py-2 border-0 text-center">
                <small class="text-muted" style="font-size: 0.65rem;">Actualizado automáticamente</small>
            </div>
        </div>
    </div>
</div>

<style>
.fw-black { font-weight: 900; }
.letter-spacing-1 { letter-spacing: 1px; }
.list-group-item:hover {
    background-color: #f8fbff !important;
    transform: translateX(5px);
}
</style>