<?php
// bonus.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['nombre'];

// --- LÓGICA DE BLOQUEO (Antes del primer partido) ---
$first_match = $pdo->query("SELECT match_date FROM matches ORDER BY match_date ASC LIMIT 1")->fetchColumn();
$tournament_start_time = $first_match ? strtotime($first_match) : time() + 3600; // Si no hay partidos, 1 hora más
$is_locked = (time() >= $tournament_start_time); 

// 1. Obtener CANDIDATOS disponibles
$stmt_candidates = $pdo->query("SELECT id, name, team_name, type, photo_url FROM bonus_candidates ORDER BY type DESC, name ASC");
$candidates = $stmt_candidates->fetchAll(PDO::FETCH_ASSOC);
$scorers = array_filter($candidates, fn($c) => $c['type'] === 'scorer');
$keepers = array_filter($candidates, fn($c) => $c['type'] === 'keeper');

// 2. Obtener TODOS los equipos para el select de Campeón
$teams_list = $pdo->query("SELECT id, name, flag FROM teams ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener la ELECCIÓN ACTUAL del usuario
$current_selection = $pdo->prepare("SELECT scorer_candidate_id, keeper_candidate_id, total_goals_prediction, champion_team_id
                                     FROM user_bonus_predictions WHERE user_id = ?");
$current_selection->execute([$user_id]);
$current_selection = $current_selection->fetch(PDO::FETCH_ASSOC);
$current_scorer_id = $current_selection['scorer_candidate_id'] ?? null;
$current_keeper_id = $current_selection['keeper_candidate_id'] ?? null;
$current_goals = $current_selection['total_goals_prediction'] ?? null;
$current_champion = $current_selection['champion_team_id'] ?? null;

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elecciones Bonus - Mundial 2026</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .candidate-photo { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; margin-right: 15px; }
        .candidate-card { transition: all 0.2s; cursor: pointer; }
        .candidate-card:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .candidate-selected { border: 3px solid #0d6efd; box-shadow: 0 0 10px rgba(13, 110, 253, 0.5); }
        .flag-img-select {
            width: 24px;
            height: 16px;
            object-fit: cover;
            margin-right: 8px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'bonus'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="text-center mb-4">⭐ Elecciones de Alto Valor</h2>
    
    <?php if($is_locked): ?>
        <div class="alert alert-danger text-center fw-bold">
            🔒 ¡El torneo ha comenzado! Estas predicciones están bloqueadas y no se pueden modificar.
        </div>
    <?php elseif($msg === 'saved'): ?>
        <div class="alert alert-success alert-dismissible fade show text-center fw-bold">
            ✅ ¡Tus elecciones bonus han sido guardadas!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="bonusForm" action="save_bonus_election.php" method="POST">
        
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-success text-white fw-bold fs-5">
                <i class="bi bi-star-fill"></i> Máximos Premios (¡150 Pts Campeón!)
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="champion_id" class="form-label fw-bold">¿Quién ganará el Mundial?</label>
                        <select name="champion_id" id="champion_id" class="form-select" <?php echo $is_locked ? 'disabled' : ''; ?> required>
                            <option value="">-- Elige un Campeón --</option>
                            <?php foreach($teams_list as $team): ?>
                                <option value="<?php echo $team['id']; ?>" 
                                        data-flag="<?php echo htmlspecialchars($team['flag']); ?>"
                                        <?php echo ($current_champion === $team['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="champion_preview" class="mt-2">
                            <?php if($current_champion): 
                                $selected_team = array_filter($teams_list, fn($t) => $t['id'] === $current_champion);
                                $selected_team = reset($selected_team);
                                if($selected_team):
                            ?>
                                <img src="assets/img/banderas/<?php echo strtolower($selected_team['flag']); ?>.png" 
                                     class="flag-img-select" 
                                     onerror="this.style.display='none'">
                                <span class="text-muted small"><?php echo htmlspecialchars($selected_team['name']); ?></span>
                            <?php endif; endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="total_goals" class="form-label fw-bold">Total Goles del Torneo (± 5) - 25 Pts</label>
                        <input type="number" name="total_goals" id="total_goals" class="form-control" 
                               value="<?php echo htmlspecialchars($current_goals); ?>" 
                               placeholder="Ej: 168" min="0" <?php echo $is_locked ? 'disabled' : ''; ?> required>
                        <small class="text-muted">Aproximación para 25 puntos.</small>
                    </div>
                </div>
            </div>
        </div>


        <div class="card shadow-sm mb-5">
            <div class="card-header bg-danger text-white fw-bold fs-5">
                <i class="bi bi-person-bounding-box"></i> Máximo Goleador (100 Pts)
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach($scorers as $c): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card candidate-card h-100 <?php echo ($current_scorer_id === $c['id']) ? 'candidate-selected' : ''; ?>" 
                                 data-id="<?php echo $c['id']; ?>" data-type="scorer" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                <div class="card-body d-flex align-items-center">
                                    <input type="radio" name="scorer_id" value="<?php echo $c['id']; ?>" class="d-none" id="scorer_<?php echo $c['id']; ?>"
                                           <?php echo ($current_scorer_id === $c['id']) ? 'checked' : ''; ?> <?php echo $is_locked ? 'disabled' : ''; ?> required>
                                    
                                    <?php if($c['photo_url']): ?>
                                        <img src="<?php echo htmlspecialchars($c['photo_url']); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" class="candidate-photo">
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($c['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($c['team_name']); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-info text-white fw-bold fs-5">
                <i class="bi bi-shield-lock-fill"></i> Mejor Portero del Mundial (100 Pts)
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach($keepers as $c): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card candidate-card h-100 <?php echo ($current_keeper_id === $c['id']) ? 'candidate-selected' : ''; ?>" 
                                 data-id="<?php echo $c['id']; ?>" data-type="keeper" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                <div class="card-body d-flex align-items-center">
                                    <input type="radio" name="keeper_id" value="<?php echo $c['id']; ?>" class="d-none" id="keeper_<?php echo $c['id']; ?>"
                                           <?php echo ($current_keeper_id === $c['id']) ? 'checked' : ''; ?> <?php echo $is_locked ? 'disabled' : ''; ?> required>
                                    
                                    <?php if($c['photo_url']): ?>
                                        <img src="<?php echo htmlspecialchars($c['photo_url']); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" class="candidate-photo">
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($c['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($c['team_name']); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5 shadow-lg" <?php echo $is_locked ? 'disabled' : ''; ?>>
                Guardar Mis Elecciones
            </button>
            <?php if($is_locked): ?>
                 <p class="text-danger mt-2 fw-bold">Bloqueado desde el inicio del torneo.</p>
            <?php endif; ?>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Lógica para que al hacer clic en la tarjeta, se seleccione el radio button y cambie el estilo
    document.querySelectorAll('.candidate-card').forEach(card => {
        // Solo añadir el evento si no está bloqueado
        if (!card.hasAttribute('disabled')) {
            card.addEventListener('click', function() {
                const radioId = this.querySelector('input[type="radio"]').id;
                const radio = document.getElementById(radioId);
                const type = this.getAttribute('data-type');
                
                // 1. Limpiar la selección previa del mismo tipo
                document.querySelectorAll(`.candidate-card[data-type="${type}"]`).forEach(c => {
                    c.classList.remove('candidate-selected');
                });

                // 2. Seleccionar la nueva tarjeta y el radio
                this.classList.add('candidate-selected');
                radio.checked = true;
            });
        }
    });

    // Mostrar preview con bandera cuando se selecciona un campeón
    document.getElementById('champion_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const preview = document.getElementById('champion_preview');
        
        if (this.value) {
            const flag = selectedOption.getAttribute('data-flag');
            const text = selectedOption.textContent.trim();
            
            preview.innerHTML = `
                <img src="assets/img/banderas/${flag.toLowerCase()}.png" class="flag-img-select" onerror="this.style.display='none'">
                <span class="text-muted small">${text}</span>
            `;
        } else {
            preview.innerHTML = '';
        }
    });
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>