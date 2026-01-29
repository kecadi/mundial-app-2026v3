<?php
// challenge.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// 1. Obtener datos para los SELECTS
$stmt_matches = $pdo->query("SELECT 
    m.id, m.match_date, m.phase, t1.name AS home, t2.name AS away, t1.flag AS home_flag, t2.flag AS away_flag
FROM matches m
JOIN teams t1 ON m.team_home_id = t1.id
JOIN teams t2 ON m.team_away_id = t2.id
WHERE m.status = 'scheduled' AND m.phase != 'final' AND m.phase != 'third_place'
ORDER BY m.match_date ASC");
$matches_list = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);

$stmt_users = $pdo->prepare("SELECT id, nombre FROM users WHERE id != ? AND role = 'user' ORDER BY nombre ASC");
$stmt_users->execute([$user_id]);
$users_list = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

$stmt_busy = $pdo->query("SELECT match_id, challenger_user_id, challenged_user_id FROM match_challenges WHERE wager_status = 'PENDING'");
$busy_map = [];
while($row = $stmt_busy->fetch(PDO::FETCH_ASSOC)) {
    $busy_map[$row['match_id']][] = (int)$row['challenger_user_id'];
    $busy_map[$row['match_id']][] = (int)$row['challenged_user_id'];
}

// 2. Lógica de Envío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = $_POST['match_id'] ?? null;
    $challenged_user_id = $_POST['challenged_user_id'] ?? null;

    if (!$match_id || !$challenged_user_id) {
        $error = "Debes seleccionar un partido y un rival.";
    } else {
        try {
            $stmt_phase = $pdo->prepare("SELECT phase FROM matches WHERE id = ?");
            $stmt_phase->execute([$match_id]);
            $match_phase = $stmt_phase->fetchColumn();

            $stmt_count = $pdo->prepare("SELECT COUNT(mc.id) FROM match_challenges mc JOIN matches m ON mc.match_id = m.id WHERE mc.challenger_user_id = ? AND m.phase = ?");
            $stmt_count->execute([$user_id, $match_phase]);
            
            if ($stmt_count->fetchColumn() > 0) {
                $error = "Ya has lanzado un duelo en esta fase ($match_phase).";
            } else {
                $stmt_occupied = $pdo->prepare("SELECT id FROM match_challenges WHERE match_id = ? AND (challenger_user_id = ? OR challenged_user_id = ?)");
                $stmt_occupied->execute([$match_id, $challenged_user_id, $challenged_user_id]);
                
                if ($stmt_occupied->fetch()) {
                    $error = "Este rival ya está en un duelo para este partido.";
                } else {
                    $sql = "INSERT INTO match_challenges (match_id, challenger_user_id, challenged_user_id) VALUES (?, ?, ?)";
                    $pdo->prepare($sql)->execute([$match_id, $user_id, $challenged_user_id]);
                    $success = "¡Reto lanzado! Prepárate para el duelo.";
                }
            }
        } catch (PDOException $e) { $error = "Error: " . $e->getMessage(); }
    }
}

// 3. Desafíos Pendientes (Donde YO reto y donde ME retan)
$stmt_pending = $pdo->prepare("SELECT 
    mc.id, m.match_date, m.phase, t1.name AS home, t2.name AS away, 
    t1.flag AS home_flag, t2.flag AS away_flag, 
    u1.nombre AS challenger_name, u2.nombre AS challenged_name,
    mc.challenger_user_id
FROM match_challenges mc
JOIN matches m ON mc.match_id = m.id
JOIN teams t1 ON m.team_home_id = t1.id
JOIN teams t2 ON m.team_away_id = t2.id
JOIN users u1 ON mc.challenger_user_id = u1.id
JOIN users u2 ON mc.challenged_user_id = u2.id
WHERE (mc.challenger_user_id = ? OR mc.challenged_user_id = ?) AND mc.wager_status = 'PENDING'
ORDER BY m.match_date ASC");
$stmt_pending->execute([$user_id, $user_id]);
$all_pending = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arena de Duelos - Mundial 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .arena-header { background: linear-gradient(135deg, #6610f2 0%, #0d6efd 100%); color: white; border-radius: 20px; padding: 40px; margin-bottom: 40px; position: relative; overflow: hidden; }
        .arena-header i { position: absolute; right: -20px; bottom: -20px; font-size: 15rem; opacity: 0.1; }
        .challenge-card { border: none; border-radius: 15px; background: white; transition: transform 0.2s; }
        .challenge-card:hover { transform: scale(1.01); }
        .vs-circle { width: 40px; height: 40px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; margin: 0 10px; }
        .flag-img { width: 30px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .badge-phase { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; }
    </style>
</head>
<body class="bg-light">

<?php $current_page = 'challenge'; include 'includes/navbar.php'; ?>

<div class="container py-5">
    
    <div class="arena-header shadow-lg">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge badge-phase mb-2">Competición Directa</span>
                <h1 class="display-4 fw-bold">Arena de Duelos</h1>
                <p class="lead opacity-75">Roba los puntos de tus rivales. Un solo duelo por fase. ¡Elige bien a tu víctima!</p>
            </div>
        </div>
        <i class="bi bi-swords"></i>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card challenge-card shadow-sm h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Nuevo Desafío</h4>
                    
                    <?php if ($success): ?> <div class="alert alert-success border-0 shadow-sm small"><?php echo $success; ?></div> <?php endif; ?>
                    <?php if ($error): ?> <div class="alert alert-danger border-0 shadow-sm small"><?php echo $error; ?></div> <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">1. Selecciona el Partido</label>
                            <select name="match_id" id="match_id" class="form-select border-2" required>
                                <option value="">-- Ver partidos --</option>
                                <?php foreach($matches_list as $match): ?>
                                    <option value="<?php echo $match['id']; ?>" 
                                            data-home-flag="<?php echo htmlspecialchars($match['home_flag']); ?>"
                                            data-away-flag="<?php echo htmlspecialchars($match['away_flag']); ?>">
                                        <?php echo htmlspecialchars($match['home'] . ' vs ' . $match['away']); ?> (<?php echo $match['phase']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="match_preview" class="mb-3 d-flex align-items-center justify-content-center p-3 bg-light rounded-3 border border-dashed" style="display:none !important;">
                            </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">2. Elige a tu Rival</label>
                            <select name="challenged_user_id" id="challenged_user_id" class="form-select border-2" required>
                                <option value="">-- ¿A quién retas? --</option>
                                <?php foreach($users_list as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" data-name="<?php echo htmlspecialchars($u['nombre']); ?>">
                                        <?php echo htmlspecialchars($u['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow">
                            LANZAR DUELO <i class="bi bi-send-fill ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card challenge-card shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h4 class="fw-bold mb-0"><i class="bi bi-fire text-danger me-2"></i>Duelos en Curso</h4>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($all_pending)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-ghost fs-1 text-muted opacity-25"></i>
                            <p class="text-muted mt-2">No hay duelos activos en este momento.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Partido</th>
                                        <th>Enfrentamiento</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_pending as $ch): 
                                        $soyRetador = ($ch['challenger_user_id'] == $user_id);
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <img src="assets/img/banderas/<?php echo strtolower($ch['home_flag']); ?>.png" class="flag-img me-2">
                                                <img src="assets/img/banderas/<?php echo strtolower($ch['away_flag']); ?>.png" class="flag-img">
                                                <div class="ms-2">
                                                    <div class="small fw-bold"><?php echo $ch['home']; ?>-<?php echo $ch['away']; ?></div>
                                                    <div class="text-muted" style="font-size:0.7rem;"><?php echo date('d/m H:i', strtotime($ch['match_date'])); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge <?php echo $soyRetador ? 'bg-primary' : 'bg-light text-dark border'; ?>"><?php echo $ch['challenger_name']; ?></span>
                                                <span class="mx-2 small fw-bold text-danger">VS</span>
                                                <span class="badge <?php echo !$soyRetador ? 'bg-primary' : 'bg-light text-dark border'; ?>"><?php echo $ch['challenged_name']; ?></span>
                                            </div>
                                            <div class="small mt-1" style="font-size:0.65rem">
                                                <?php echo $soyRetador ? '🔥 <span class="text-primary">Tú has retado</span>' : '⚠️ <span class="text-danger">Te han retado</span>'; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-warning text-dark px-3">PENDIENTE</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const busyUsers = <?php echo json_encode($busy_map); ?>;
    const matchSelect = document.getElementById('match_id');
    const userSelect = document.getElementById('challenged_user_id');
    const preview = document.getElementById('match_preview');

    matchSelect.addEventListener('change', function() {
        const matchId = this.value;
        const option = this.options[this.selectedIndex];
        
        // 1. Mostrar/Ocultar banderas
        if (matchId) {
            const h = option.dataset.homeFlag.toLowerCase();
            const a = option.dataset.awayFlag.toLowerCase();
            preview.style.setProperty('display', 'flex', 'important');
            preview.innerHTML = `<img src="assets/img/banderas/${h}.png" class="flag-img mx-2"> <span class="fw-bold">VS</span> <img src="assets/img/banderas/${a}.png" class="flag-img mx-2">`;
        } else {
            preview.style.setProperty('display', 'none', 'important');
        }

        // 2. Bloquear usuarios
        const blocked = busyUsers[matchId] || [];
        Array.from(userSelect.options).forEach(opt => {
            if (!opt.value) return;
            const originalName = opt.dataset.name;
            if (blocked.includes(parseInt(opt.value))) {
                opt.disabled = true;
                opt.textContent = originalName + " (Ocupado)";
            } else {
                opt.disabled = false;
                opt.textContent = originalName;
            }
        });
        if (userSelect.selectedOptions[0]?.disabled) userSelect.value = "";
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>