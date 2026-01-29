<div class="modal fade" id="predictModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="save_prediction.php" method="POST">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Tu Pronóstico</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-4">
            <input type="hidden" name="match_id" id="modal_match_id">
            
            <h4 class="mb-4 fw-bold text-center text-dark">
                <span id="modal_home_name">Local</span> 
                <span class="text-muted mx-2">vs</span> 
                <span id="modal_away_name">Visitante</span>
            </h4>
            
            <div class="row justify-content-center align-items-center g-2">
                <div class="col-4">
                    <input type="number" name="score_home" id="modal_score_home" 
                           class="form-control form-control-lg text-center fw-bold text-primary" 
                           placeholder="0" min="0" required>
                    <small class="text-muted">Local</small>
                </div>
                <div class="col-1 fw-bold fs-4 text-dark">:</div>
                <div class="col-4">
                    <input type="number" name="score_away" id="modal_score_away" 
                           class="form-control form-control-lg text-center fw-bold text-primary" 
                           placeholder="0" min="0" required>
                    <small class="text-muted">Visitante</small>
                </div>
            </div>
            
            <div class="mb-3 text-center mt-4" id="qualifier_selector" style="display: none;">
                <hr>
                <label class="form-label fw-bold">¿Quién pasa en caso de empate?</label>
                <select name="qualifier_id" id="modal_qualifier_id" class="form-select mx-auto" style="width: 80%;">
                </select>
            </div>

          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success px-4">💾 Guardar Resultado</button>
          </div>
      </form>
    </div>
  </div>
</div>