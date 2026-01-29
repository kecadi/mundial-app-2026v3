<script>
    const predictModal = document.getElementById('predictModal');
    const bsModal = new bootstrap.Modal(predictModal);
    
    // Referencias a los campos del modal
    const scoreHomeInput = document.getElementById('modal_score_home');
    const scoreAwayInput = document.getElementById('modal_score_away');
    const qualifierSelectorDiv = document.getElementById('qualifier_selector');
    const qualifierDropdown = document.getElementById('modal_qualifier_id');

    let currentMatchPhase = 'group'; 

    // FUNCIÓN PRINCIPAL PARA CONTROLAR LA VISIBILIDAD (Eliminatoria + Empate)
    function checkQualifierVisibility() {
        const isKnockout = (currentMatchPhase !== 'group');

        if (isKnockout) {
            const scoreHome = scoreHomeInput.value;
            const scoreAway = scoreAwayInput.value;
            const isDraw = (scoreHome !== '' && scoreAway !== '' && scoreHome === scoreAway);

            if (isDraw) {
                qualifierSelectorDiv.style.display = 'block';
                qualifierDropdown.required = true;
            } else {
                qualifierSelectorDiv.style.display = 'none';
                qualifierDropdown.required = false;
            }
        } else {
            qualifierSelectorDiv.style.display = 'none';
            qualifierDropdown.required = false;
        }
    }

    // Escucha eventos en los inputs de score
    scoreHomeInput.addEventListener('input', checkQualifierVisibility);
    scoreAwayInput.addEventListener('input', checkQualifierVisibility);

    // LÓGICA AL ABRIR EL MODAL (rellenar datos)
    document.querySelectorAll('.btn-predict').forEach(button => {
        button.addEventListener('click', function() {
            // Obtener datos del botón
            const phase = this.getAttribute('data-phase'); 
            const homeId = this.getAttribute('data-home-id');
            const awayId = this.getAttribute('data-away-id');
            const qualifierId = this.getAttribute('data-qualifier-id'); // ID del clasificado si está editando
            
            const scoreH = this.getAttribute('data-score-home');
            const scoreA = this.getAttribute('data-score-away');

            // 1. Almacenar fase activa globalmente y llenar campos
            currentMatchPhase = phase; 
            document.getElementById('modal_match_id').value = this.getAttribute('data-id');
            document.getElementById('modal_home_name').textContent = this.getAttribute('data-home');
            document.getElementById('modal_away_name').textContent = this.getAttribute('data-away');
            scoreHomeInput.value = scoreH !== null ? scoreH : '';
            scoreAwayInput.value = scoreA !== null ? scoreA : '';

            // 2. Llenar Dropdown del Clasificado
            qualifierDropdown.innerHTML = `
                <option value="">-- Elige Clasificado --</option>
                <option value="${homeId}">${this.getAttribute('data-home')}</option>
                <option value="${awayId}">${this.getAttribute('data-away')}</option>
            `;
            // Cargar el valor predicho del clasificado si existe
            if (qualifierId) {
                qualifierDropdown.value = qualifierId;
            } else {
                qualifierDropdown.value = "";
            }

            // 3. Ejecutar el chequeo inicial al abrir el modal (para mostrar/ocultar selector)
            checkQualifierVisibility(); 
            bsModal.show();
        });
    });
    // Lógica del Temporizador para el Quiz
    if (document.getElementById('timer')) {
        let timeLeft = 10;
        const timerElement = document.getElementById('timer');
        const submitBtn = document.getElementById('quizSubmitBtn');
        const optionsContainer = document.getElementById('optionsContainer');
        const form = document.getElementById('quizForm');
        
        // Deshabilita el botón de enviar hasta que se elija una opción
        document.querySelectorAll('input[name="answer"]').forEach(radio => {
            radio.addEventListener('change', () => {
                submitBtn.disabled = false;
            });
        });

        const timerInterval = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerElement.textContent = 0;
                submitBtn.disabled = true;
                submitBtn.textContent = '¡Tiempo Agotado!';
                optionsContainer.classList.add('disabled-options'); // Opcional, para estilo
                form.submit(); // Envía la respuesta aunque esté mal (cero puntos)
            }
        }, 1000);
    }

    // index.php (Dentro del bloque <script>)

    // --- Lógica del Contador Regresivo (Countdown) ---
    const countdownElement = document.getElementById('countdown-timer');
    const lockTargetMs = document.getElementById('lock-target-ms');

    if (countdownElement && lockTargetMs) {
        let timeLeftMs = parseInt(lockTargetMs.value);

        const updateTimer = () => {
            timeLeftMs -= 1000;

            if (timeLeftMs <= 0) {
                clearInterval(countdownInterval);
                countdownElement.textContent = "¡CERRADO!";
                // Opcional: Recargar la página para deshabilitar los botones
                setTimeout(() => { window.location.reload(); }, 2000); 
                return;
            }

            const totalSeconds = Math.floor(timeLeftMs / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            const formatTime = (t) => t < 10 ? "0" + t : t;

            countdownElement.textContent = `${formatTime(hours)}:${formatTime(minutes)}:${formatTime(seconds)}`;
        };

        // Actualizar inmediatamente y luego cada segundo
        updateTimer();
        const countdownInterval = setInterval(updateTimer, 1000);
    }
</script>