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