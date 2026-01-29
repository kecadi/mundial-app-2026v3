    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark fw-bold">
                    🧠 Quiz Diario de 10 Segundos
                </div>
                <div class="card-body">
                    <?php if ($quiz_answered): ?>
                        <div class="alert alert-success text-center fw-bold">
                            🎉 ¡Ya respondiste hoy! Ganaste <?php echo $quiz_points_today; ?> puntos.
                        </div>
                    <?php elseif ($quiz_data): 
                        // Al iniciar, guardamos la hora de inicio en la sesión para la verificación de 10 segundos
                        $_SESSION['quiz_start_time'] = time();
                        $_SESSION['quiz_question_id'] = $quiz_data['id'];
                    ?>
                        <form id="quizForm" action="save_quiz_response.php" method="POST">
                            <input type="hidden" name="question_id" value="<?php echo $quiz_data['id']; ?>">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-danger mb-0">¡Rápido! Tienes <span id="timer" class="display-6 fw-bold">10</span> segundos.</h5>
                                <button type="submit" id="quizSubmitBtn" class="btn btn-success" disabled>Responder</button>
                            </div>
                            
                            <p class="lead fw-bold text-dark"><?php echo htmlspecialchars($quiz_data['question']); ?></p>
                            
                            <div class="row" id="optionsContainer">
                                <?php $options = ['A', 'B', 'C', 'D']; ?>
                                <?php foreach ($options as $opt): ?>
                                    <div class="col-md-6 mb-2">
                                        <input type="radio" name="answer" id="opt_<?php echo $opt; ?>" value="<?php echo $opt; ?>" class="btn-check" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 text-start" for="opt_<?php echo $opt; ?>">
                                            <?php echo $opt; ?>) <?php echo htmlspecialchars($quiz_data['option_' . strtolower($opt)]); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            La pregunta diaria de hoy aún no está programada.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>