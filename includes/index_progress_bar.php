<div class="row mb-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: #fdfdfd;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1 fw-black text-dark">ESTADO DE TU QUINIELA</h5>
                        <p class="small text-muted mb-0">
                            Llevas <span class="fw-bold text-primary"><?php echo $my_predictions_count; ?></span> de <span class="fw-bold"><?php echo $total_matches_generated; ?></span> pronósticos.
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="h2 fw-black text-primary mb-0"><?php echo $percentage_complete; ?><small class="fs-6">%</small></span>
                    </div>
                </div>

                <div class="progress" style="height: 12px; background-color: #e9ecef; border-radius: 50px; overflow: visible;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: <?php echo $percentage_complete; ?>%; border-radius: 50px; background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%); position: relative;" 
                         aria-valuenow="<?php echo $percentage_complete; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                         
                         <div class="position-absolute top-50 start-100 translate-middle rounded-circle bg-white shadow-sm" style="width: 20px; height: 20px; border: 4px solid #0dcaf0;"></div>
                    </div>
                </div>

                <div class="mt-3">
                    <?php if($percentage_complete == 100): ?>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 w-100">
                            <i class="bi bi-check-all me-1"></i> ¡Quiniela completada! Estás listo para el pitido inicial.
                        </span>
                    <?php elseif($percentage_complete > 75): ?>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 w-100">
                            <i class="bi bi-flag-fill me-1"></i> ¡Casi lo tienes! Solo unos pocos más y estarás al 100%.
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-2 w-100">
                            <i class="bi bi-pencil-fill me-1"></i> ¡Sigue así! No dejes que se te escape ningún partido.
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.fw-black { font-weight: 900; }
/* Suavizamos el movimiento de la barra al cargar */
.progress-bar {
    transition: width 1.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
</style>