<div class="row mb-5">
    <div class="col-12">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-warning py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-lightning-fill me-2"></i>DESAFÍO RELÁMPAGO</h5>
                <span class="badge bg-dark rounded-pill">Diario</span>
            </div>

            <div class="card-body p-4">
                <?php if ($quiz_answered): ?>
                    <div class="text-center py-3">
                        <div class="display-1 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
                        <h4 class="fw-bold">¡Misión Cumplida!</h4>
                        <p class="text-muted">Has sumado <span class="badge bg-success fs-5"><?php echo $quiz_points_today; ?></span> puntos a tu marcador hoy.</p>
                    </div>

                <?php elseif ($quiz_data): 
                    $_SESSION['quiz_start_time'] = time();
                    $_SESSION['quiz_question_id'] = $quiz_data['id'];
                ?>
                    <form id="quizForm" action="save_quiz_response.php" method="POST">
                        <input type="hidden" name="question_id" value="<?php echo $quiz_data['id']; ?>">
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <span class="small fw-bold text-uppercase text-muted">Tiempo restante</span>
                                <span id="timer" class="h2 fw-black text-danger mb-0">10</span>
                            </div>
                            <div class="progress" style="height: 10px; background-color: #f0f0f0; border-radius: 5px;">
                                <div id="timerBar" class="progress-bar bg-danger progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>

                        <h3 class="fw-bold text-dark mb-4 text-center">
                            <?php echo htmlspecialchars($quiz_data['question']); ?>
                        </h3>
                        
                        <div class="row g-3" id="optionsContainer">
                            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                                <div class="col-md-6">
                                    <input type="radio" name="answer" id="opt_<?php echo $opt; ?>" value="<?php echo $opt; ?>" class="btn-check" autocomplete="off" required>
                                    <label class="btn btn-outline-primary w-100 p-3 text-start quiz-option-label" for="opt_<?php echo $opt; ?>">
                                        <span class="option-letter me-2"><?php echo $opt; ?></span> 
                                        <?php echo htmlspecialchars($quiz_data['option_' . strtolower($opt)]); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 text-center">
                            <button type="submit" id="quizSubmitBtn" class="btn btn-dark btn-lg px-5 py-3 rounded-pill fw-bold shadow disabled">
                                ENVIAR RESPUESTA <i class="bi bi-send-fill ms-2"></i>
                            </button>
                        </div>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            let timeLeft = 10;
                            const timerDisplay = document.getElementById('timer');
                            const timerBar = document.getElementById('timerBar');
                            const form = document.getElementById('quizForm');
                            const radios = document.querySelectorAll('input[name="answer"]');
                            const submitBtn = document.getElementById('quizSubmitBtn');

                            // Habilitar botón al seleccionar opción
                            radios.forEach(r => r.addEventListener('change', () => submitBtn.classList.remove('disabled')));

                            const countdown = setInterval(() => {
                                timeLeft--;
                                timerDisplay.textContent = timeLeft;
                                
                                // Actualizar barra
                                let width = (timeLeft / 10) * 100;
                                timerBar.style.width = width + '%';

                                if (timeLeft <= 3) {
                                    timerDisplay.classList.add('animate-pulse');
                                    timerBar.classList.remove('bg-danger');
                                    timerBar.style.backgroundColor = '#000'; // Color crítico
                                }

                                if (timeLeft <= 0) {
                                    clearInterval(countdown);
                                    form.submit(); // Auto-envío al terminar el tiempo
                                }
                            }, 1000);
                        });
                    </script>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history fs-1 text-muted opacity-25"></i>
                        <p class="text-muted mt-2">Próxima pregunta disponible mañana. ¡No faltes!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.fw-black { font-weight: 900; }
.quiz-option-label { border-width: 2px; border-radius: 12px; transition: all 0.2s; background: #fff; }
.quiz-option-label:hover { background: #f8fbff; transform: translateY(-2px); }
.btn-check:checked + .quiz-option-label { 
    background-color: #0d6efd !important; 
    color: white !important; 
    border-color: #0d6efd !important;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}
.option-letter { 
    background: rgba(13, 110, 253, 0.1); 
    color: #0d6efd; 
    padding: 2px 8px; 
    border-radius: 6px; 
    font-weight: bold; 
}
.btn-check:checked + .quiz-option-label .option-letter {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); color: #ff0000; }
    100% { transform: scale(1); }
}
.animate-pulse { animation: pulse 0.5s infinite; }
</style>