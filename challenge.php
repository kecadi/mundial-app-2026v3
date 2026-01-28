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
// Equipos y partidos que aún no se han jugado
$stmt_matches = $pdo->query("SELECT 
    m.id, m.match_date, m.phase, t1.name AS home, t2.name AS away, t1.flag AS home_flag, t2.flag AS away_flag
FROM matches m
JOIN teams t1 ON m.team_home_id = t1.id
JOIN teams t2 ON m.team_away_id = t2.id
WHERE m.status = 'scheduled' AND m.phase != 'final' AND m.phase != 'third_place'
ORDER BY m.match_date ASC");
$matches_list = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);

// Usuarios jugadores (el rival no puede ser el propio usuario ni el admin)
$stmt_users = $pdo->prepare("SELECT id, nombre FROM users WHERE id != ? AND role = 'user' ORDER BY nombre ASC");
$stmt_users->execute([$user_id]);
$users_list = $stmt_users->fetchAll(PDO::FETCH_ASSOC);


// 2. Lógica de Envío del Desafío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = $_POST['match_id'] ?? null;
    $challenged_user_id = $_POST['challenged_user_id'] ?? null;

    if (!$match_id || !$challenged_user_id) {
        $error = "Debes seleccionar un partido y un rival.";
    } else {
        try {
            // A. Obtener la FASE del partido seleccionado
            $stmt_phase = $pdo->prepare("SELECT phase FROM matches WHERE id = ?");
            $stmt_phase->execute([$match_id]);
            $match_phase = $stmt_phase->fetchColumn();

            // B. Contar desafíos existentes del usuario en esa fase
            $stmt_count = $pdo->prepare("SELECT COUNT(mc.id) FROM match_challenges mc
                                         JOIN matches m ON mc.match_id = m.id
                                         WHERE mc.challenger_user_id = ? AND m.phase = ?");
            $stmt_count->execute([$user_id, $match_phase]);
            $challenges_in_phase = $stmt_count->fetchColumn();

            // 3. ENFORZAR RESTRICCIÓN (El core de la nueva regla)
            if ($challenges_in_phase > 0) {
                $error = "No puedes iniciar este desafío. Ya has usado tu duelo en la fase de **" . $match_phase . "**.";
            } else {
                // 4. Verificar si el desafío ya existe
                $stmt_check = $pdo->prepare("SELECT id FROM match_challenges 
                                             WHERE match_id = ? AND challenger_user_id = ? AND challenged_user_id = ?");
                $stmt_check->execute([$match_id, $user_id, $challenged_user_id]);
                
                if ($stmt_check->rowCount() > 0) {
                     $error = "Ya existe un desafío pendiente entre tú y este rival para este partido.";
                } else {
                    // 5. Insertar el desafío
                    $sql = "INSERT INTO match_challenges (match_id, challenger_user_id, challenged_user_id) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$match_id, $user_id, $challenged_user_id]);
                    $success = "¡Desafío creado con éxito! Los puntos se ajustarán después del partido.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error de base de datos al crear el desafío: " . $e->getMessage();
        }
    }
}


// 3. Obtener Desafíos Pendientes del Usuario (Para la tabla)
$stmt_pending = $pdo->prepare("SELECT 
    mc.id, m.match_date, m.phase, t1.name AS home, t2.name AS away, t1.flag AS home_flag, t2.flag AS away_flag, u.nombre AS rival_name, mc.wager_status
FROM match_challenges mc
JOIN matches m ON mc.match_id = m.id
JOIN teams t1 ON m.team_home_id = t1.id
JOIN teams t2 ON m.team_away_id = t2.id
JOIN users u ON mc.challenged_user_id = u.id
WHERE mc.challenger_user_id = ? AND mc.wager_status = 'PENDING'
ORDER BY m.match_date ASC");
$stmt_pending->execute([$user_id]);
$pending_challenges = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desafíos de Predicción</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .flag-img {
            width: 24px;
            height: 16px;
            object-fit: cover;
            margin-right: 5px;
            border: 1px solid #ddd;
        }
        .flag-img-small {
            width: 20px;
            height: 14px;
            object-fit: cover;
            margin-right: 3px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'challenge'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4 text-danger"><i class="bi bi-swords"></i> Duelos de Predicción</h2>
    <p class="text-muted">Desafía a un amigo a un partido. Tienes un único desafío por fase del torneo.</p>

    <div class="card shadow-sm mb-5 border-danger">
        <div class="card-header bg-danger text-white fw-bold">
            Crear Nuevo Desafío
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="challenge.php">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="match_id" class="form-label">Selecciona el Partido</label>
                        <select name="match_id" id="match_id" class="form-select" required>
                            <option value="">-- Partidos Pendientes --</option>
                            <?php foreach($matches_list as $match): ?>
                                <option value="<?php echo $match['id']; ?>" 
                                        data-home-flag="<?php echo htmlspecialchars($match['home_flag']); ?>"
                                        data-away-flag="<?php echo htmlspecialchars($match['away_flag']); ?>">
                                    <?php echo htmlspecialchars($match['home'] . ' vs ' . $match['away']); ?> (<?php echo date('d/m H:i', strtotime($match['match_date'])); ?> - Fase: <?php echo $match['phase']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="match_preview" class="mt-2 text-muted small"></div>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="challenged_user_id" class="form-label">Desafiar a:</label>
                        <select name="challenged_user_id" id="challenged_user_id" class="form-select" required>
                            <option value="">-- Selecciona un Rival --</option>
                            <?php foreach($users_list as $u): ?>
                                <option value="<?php echo $u['id']; ?>">
                                    <?php echo htmlspecialchars($u['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100" <?php echo empty($matches_list) ? 'disabled' : ''; ?>>⚔️ Desafiar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-dark text-white fw-bold">
            Tus Desafíos Pendientes
        </div>
        <div class="card-body p-0">
            <?php if (empty($pending_challenges)): ?>
                <div class="alert alert-light m-0 text-center">No has lanzado ningún desafío pendiente.</div>
            <?php else: ?>
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Fase</th>
                            <th>Partido</th>
                            <th>Rival</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending_challenges as $challenge): ?>
                        <tr>
                            <td><?php echo $challenge['phase']; ?></td>
                            <td>
                                <img src="assets/img/banderas/<?php echo strtolower($challenge['home_flag']); ?>.png" 
                                     alt="<?php echo $challenge['home_flag']; ?>" 
                                     class="flag-img-small"
                                     onerror="this.style.display='none'">
                                <?php echo htmlspecialchars($challenge['home']); ?>
                                <span class="text-muted">vs</span>
                                <img src="assets/img/banderas/<?php echo strtolower($challenge['away_flag']); ?>.png" 
                                     alt="<?php echo $challenge['away_flag']; ?>" 
                                     class="flag-img-small"
                                     onerror="this.style.display='none'">
                                <?php echo htmlspecialchars($challenge['away']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($challenge['rival_name']); ?></td>
                            <td><span class="badge bg-warning"><?php echo $challenge['wager_status']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mostrar preview con banderas cuando se selecciona un partido
    document.getElementById('match_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const preview = document.getElementById('match_preview');
        
        if (this.value) {
            const homeFlag = selectedOption.getAttribute('data-home-flag');
            const awayFlag = selectedOption.getAttribute('data-away-flag');
            const text = selectedOption.textContent;
            
            preview.innerHTML = `
                <img src="assets/img/banderas/${homeFlag.toLowerCase()}.png" class="flag-img" onerror="this.style.display='none'">
                <img src="assets/img/banderas/${awayFlag.toLowerCase()}.png" class="flag-img" onerror="this.style.display='none'">
                ${text}
            `;
        } else {
            preview.innerHTML = '';
        }
    });
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>