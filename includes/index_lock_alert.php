<?php if (!empty($next_matches_data)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden rounded-4" style="background: #1a1a1a;">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    
                    <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                            <div class="flex-shrink-0">
                                <div class="spinner-grow text-danger spinner-grow-sm me-2" role="status"></div>
                            </div>
                            <div>
                                <h6 class="text-white-50 mb-0 small fw-bold text-uppercase letter-spacing-1">Cierre de Pronósticos</h6>
                                <div class="text-white fw-black fs-5">LÍMITE INMINENTE</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3 mb-md-0 border-start border-end border-secondary border-opacity-25">
                        <div class="px-2">
                            <ul class="list-unstyled mb-0">
                                <?php foreach(array_slice($next_matches_data, 0, 2) as $match): ?>
                                    <li class="small text-white text-center d-flex align-items-center justify-content-center mb-1">
                                        <img src="assets/img/banderas/<?php echo $match['home_flag']; ?>.png" class="me-2 shadow-sm" style="width: 18px; border-radius: 2px;">
                                        <span class="opacity-75" style="font-size: 0.75rem;"><?php echo htmlspecialchars($match['home']); ?> vs <?php echo htmlspecialchars($match['away']); ?></span>
                                        <img src="assets/img/banderas/<?php echo $match['away_flag']; ?>.png" class="ms-2 shadow-sm" style="width: 18px; border-radius: 2px;">
                                    </li>
                                <?php endforeach; ?>
                                <?php if(count($next_matches_data) > 2): ?>
                                    <li class="text-center text-warning" style="font-size: 0.65rem;">+<?php echo count($next_matches_data) - 2; ?> partidos más</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-4 text-center">
                        <div class="d-inline-block">
                            <h2 id="countdown-timer" class="display-6 fw-black text-warning mb-0" style="font-family: 'Courier New', Courier, monospace; letter-spacing: -1px;">00:00:00</h2>
                            <div class="text-white-50" style="font-size: 0.6rem;">Cierre: <?php echo date('H:i:s', $next_lock_timestamp); ?></div>
                        </div>
                    </div>

                </div>
            </div>
            <input type="hidden" id="lock-target-ms" value="<?php echo $time_until_lock_ms; ?>">
        </div>
    </div>
</div>

<style>
.fw-black { font-weight: 900; }
.letter-spacing-1 { letter-spacing: 1px; }
#countdown-timer.panic {
    color: #ff4d4d !important;
    animation: blinker 1s linear infinite;
}
@keyframes blinker {
    50% { opacity: 0.5; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timerDisplay = document.getElementById('countdown-timer');
    let targetMs = parseInt(document.getElementById('lock-target-ms').value);
    
    const updateTimer = setInterval(() => {
        if (targetMs <= 0) {
            clearInterval(updateTimer);
            timerDisplay.textContent = "CERRADO";
            timerDisplay.classList.remove('text-warning');
            timerDisplay.classList.add('text-danger');
            return;
        }

        targetMs -= 1000;
        
        let hours = Math.floor(targetMs / 3600000);
        let minutes = Math.floor((targetMs % 3600000) / 60000);
        let seconds = Math.floor((targetMs % 60000) / 1000);

        timerDisplay.textContent = 
            (hours < 10 ? "0" + hours : hours) + ":" + 
            (minutes < 10 ? "0" + minutes : minutes) + ":" + 
            (seconds < 10 ? "0" + seconds : seconds);

        // Modo pánico: menos de 5 minutos
        if (targetMs < 300000) {
            timerDisplay.classList.add('panic');
        }
    }, 1000);
});
</script>
<?php endif; ?>