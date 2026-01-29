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

// --- LÓGICA DE BLOQUEO ---
$first_match = $pdo->query("SELECT match_date FROM matches ORDER BY match_date ASC LIMIT 1")->fetchColumn();
$tournament_start_time = $first_match ? strtotime($first_match) : time() + 3600;
$is_locked = (time() >= $tournament_start_time); 

// 1. Obtener CANDIDATOS
$stmt_candidates = $pdo->query("SELECT id, name, team_name, type, photo_url FROM bonus_candidates ORDER BY type DESC, name ASC");
$candidates = $stmt_candidates->fetchAll(PDO::FETCH_ASSOC);
$scorers = array_filter($candidates, fn($c) => $c['type'] === 'scorer');
$keepers = array_filter($candidates, fn($c) => $c['type'] === 'keeper');

// 2. Obtener EQUIPOS
$teams_list = $pdo->query("SELECT id, name, flag FROM teams ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener ELECCIÓN ACTUAL
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --gold: #c5a059; --silver: #a8a8a8; --bronze: #cd7f32; }
        .bonus-header { background: linear-gradient(135deg, #1a1a1a 0%, #333 100%); color: var(--gold); border-radius: 15px; padding: 30px; margin-bottom: 40px; border: 1px solid var(--gold); }
        .candidate-photo { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #eee; margin-right: 15px; }
        .candidate-card { transition: all 0.3s cubic-bezier(.25,.8,.25,1); cursor: pointer; border: 1px solid #eee; border-radius: 12px; }
        .candidate-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .candidate-selected { border: 2px solid var(--gold) !important; background-color: #fffdf5 !important; }
        .candidate-selected h6 { color: #856404; }
        .flag-img-select { width: 30px; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .section-title { border-left: 5px solid var(--gold); padding-left: 15px; margin-bottom: 25px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .floating-save { position: fixed; bottom: 30px; right: 30px; z-index: 1000; }
    </style>
</head>
<body class="bg-light">

<?php $current_page = 'bonus'; include 'includes/navbar.php'; ?>

<div class="container my-5">
    
    <div class="bonus_header text-center bonus-header shadow">
        <h1 class="display-5 fw-bold mb-2">⭐ PUNTOS DE ORO</h1>
        <p class="lead mb-0">Define tu estrategia final. Estas elecciones pueden darte la victoria.</p>
        <?php if($is_locked): ?>
            <div class="badge bg-danger mt-3 px-4 py-2"><i class="bi bi-lock-fill"></i> MERCADO CERRADO</div>
        <?php else: ?>
            <div class="badge bg-success mt-3 px-4 py-2"><i class="bi bi-unlock-fill"></i> EDICIÓN ABIERTA</div>
        <?php endif; ?>
    </div>

    <?php if($msg === 'saved'): ?>
        <div class="alert alert-success alert-dismissible fade show text-center border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> ¡Tus elecciones han sido blindadas correctamente!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="bonusForm" action="save_bonus_election.php" method="POST">
        
        <h4 class="section-title text-dark">Premios Principales</h4>
        <div class="row g-4 mb-5">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <label class="form-label fw-bold fs-5 mb-3"><i class="bi bi-trophy text-warning"></i> El Próximo Campeón del Mundo</label>
                        <select name="champion_id" id="champion_id" class="form-select form-select-lg border-2" <?php echo $is_locked ? 'disabled' : ''; ?> required style="border-radius: 10px;">
                            <option value="">-- Selecciona una Selección --</option>
                            <?php foreach($teams_list as $team): ?>
                                <option value="<?php echo $team['id']; ?>" 
                                        data-flag="<?php echo htmlspecialchars($team['flag']); ?>"
                                        <?php echo ($current_champion === $team['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="champion_preview" class="mt-4 p-3 bg-light rounded-3 d-flex align-items-center justify-content-center border border-dashed">
                            <span class="text-muted small">La bandera aparecerá al seleccionar</span>
                        </div>
                        <p class="mt-3 text-muted small"><i class="bi bi-info-circle"></i> Acertar el campeón otorga **150 puntos** directos.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card border-0 shadow-sm h-100 rounded-4 bg-dark text-white">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <label class="form-label fw-bold fs-5 mb-2"><i class="bi bi-hash text-info"></i> Goles Totales</label>
                        <p class="small opacity-75 mb-4">Pronostica cuántos goles se marcarán en todo el torneo (64 partidos).</p>
                        <input type="number" name="total_goals" id="total_goals" class="form-control form-control-lg bg-transparent text-white border-2 border-info text-center" 
                               value="<?php echo htmlspecialchars($current_goals); ?>" 
                               placeholder="Ej: 172" min="0" <?php echo $is_locked ? 'disabled' : ''; ?> required style="font-size: 2rem; font-weight: 800;">
                        <div class="mt-3 text-info small text-center">Rango de error: ±5 goles = 25 Pts</div>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="section-title text-dark">Máximo Goleador (Bota de Oro)</h4>
        <div class="row g-3 mb-5">
            <?php foreach($scorers as $c): 
                $isSelected = ($current_scorer_id === $c['id']);
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="card candidate-card h-100 border-0 shadow-sm <?php echo $isSelected ? 'candidate-selected' : ''; ?>" 
                     onclick="selectCandidate(this, 'scorer', <?php echo $c['id']; ?>)">
                    <div class="card-body p-3 d-flex align-items-center">
                        <input type="radio" name="scorer_id" value="<?php echo $c['id']; ?>" class="d-none" id="scorer_<?php echo $c['id']; ?>"
                               <?php echo $isSelected ? 'checked' : ''; ?> <?php echo $is_locked ? 'disabled' : ''; ?> required>
                        <img src="<?php echo htmlspecialchars($c['photo_url'] ?: 'assets/img/players/default.png'); ?>" class="candidate-photo shadow-sm">
                        <div class="overflow-hidden">
                            <h6 class="mb-0 text-truncate fw-bold"><?php echo htmlspecialchars($c['name']); ?></h6>
                            <small class="text-muted small"><?php echo htmlspecialchars($c['team_name']); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h4 class="section-title text-dark">Mejor Portero (Guante de Oro)</h4>
        <div class="row g-3 mb-5">
            <?php foreach($keepers as $c): 
                $isSelected = ($current_keeper_id === $c['id']);
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="card candidate-card h-100 border-0 shadow-sm <?php echo $isSelected ? 'candidate-selected' : ''; ?>" 
                     onclick="selectCandidate(this, 'keeper', <?php echo $c['id']; ?>)">
                    <div class="card-body p-3 d-flex align-items-center">
                        <input type="radio" name="keeper_id" value="<?php echo $c['id']; ?>" class="d-none" id="keeper_<?php echo $c['id']; ?>"
                               <?php echo $isSelected ? 'checked' : ''; ?> <?php echo $is_locked ? 'disabled' : ''; ?> required>
                        <img src="<?php echo htmlspecialchars($c['photo_url'] ?: 'assets/img/players/default.png'); ?>" class="candidate-photo shadow-sm">
                        <div class="overflow-hidden">
                            <h6 class="mb-0 text-truncate fw-bold"><?php echo htmlspecialchars($c['name']); ?></h6>
                            <small class="text-muted small"><?php echo htmlspecialchars($c['team_name']); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(!$is_locked): ?>
            <div class="text-center pb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow-lg fw-bold">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> Guardar Mis Predicciones de Oro
                </button>
            </div>
        <?php endif; ?>

    </form>
</div>

<script>
    // Función mejorada para seleccionar candidatos
    function selectCandidate(cardElement, type, id) {
        <?php if($is_locked) echo 'return;'; ?>

        // 1. Deseleccionar todos los del mismo tipo
        document.querySelectorAll(`.candidate-card`).forEach(card => {
            const radio = card.querySelector(`input[name="${type}_id"]`);
            if (radio) {
                card.classList.remove('candidate-selected');
            }
        });

        // 2. Seleccionar el actual
        cardElement.classList.add('candidate-selected');
        const input = cardElement.querySelector('input[type="radio"]');
        input.checked = true;
    }

    // Preview de bandera para Campeón
    const championSelect = document.getElementById('champion_id');
    championSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const preview = document.getElementById('champion_preview');
        if (this.value) {
            const flag = option.getAttribute('data-flag').toLowerCase();
            preview.innerHTML = `
                <img src="assets/img/banderas/${flag}.png" class="flag-img-select me-3" style="width: 50px; height: auto;">
                <h4 class="mb-0 fw-bold text-dark">${option.textContent.trim()}</h4>
            `;
        } else {
            preview.innerHTML = '<span class="text-muted small">La bandera aparecerá al seleccionar</span>';
        }
    });

    // Disparar el cambio al cargar si ya hay selección
    if(championSelect.value) championSelect.dispatchEvent(new Event('change'));
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>