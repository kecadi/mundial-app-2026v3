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