<?php
// profile.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// 1. Obtener datos actuales del usuario (Incluyendo estadísticas)
$stmt_user = $pdo->prepare("SELECT nombre, email, created_at FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

// 2. Lógica de Estadísticas rápidas para las tarjetas superiores
$stmt_stats = $pdo->prepare("SELECT 
    COUNT(*) as total_preds,
    SUM(CASE WHEN points_earned >= 25 THEN 1 ELSE 0 END) as exact_hits,
    SUM(points_earned) as total_pts
    FROM predictions WHERE user_id = ?");
$stmt_stats->execute([$user_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// 3. Procesar actualización de perfil (Formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['new_name']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $update_fields = [];
    $update_params = [];
    
    if ($new_name !== $user_data['nombre'] && !empty($new_name)) {
        $update_fields[] = 'nombre = ?';
        $update_params[] = $new_name;
        $_SESSION['nombre'] = $new_name; 
    }
    
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (strlen($new_password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $update_fields[] = 'password = ?';
            $update_params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }
    
    if (empty($error) && !empty($update_fields)) {
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $update_params[] = $user_id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($update_params);
        $success = '¡Perfil actualizado correctamente!';
        $user_data['nombre'] = $new_name;
    }
}

// 4. OBTENER DATOS PARA EL GRÁFICO DE EVOLUCIÓN
$stmt_h = $pdo->prepare("SELECT points_at_moment, rank_at_moment, recorded_at 
                         FROM ranking_history 
                         WHERE user_id = ? 
                         ORDER BY recorded_at ASC");
$stmt_h->execute([$user_id]);
$history = $stmt_h->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$puntos = [];
$posiciones = [];
foreach($history as $h) {
    $labels[] = date('d/m', strtotime($h['recorded_at']));
    $puntos[] = $h['points_at_moment'];
    $posiciones[] = $h['rank_at_moment'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Mundial 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; border-radius: 15px; }
        .stat-card { transition: transform 0.3s; border: none; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">

<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    
    <div class="profile-header p-4 mb-4 shadow-sm d-flex align-items-center">
        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow" style="width: 80px; height: 80px;">
            <i class="bi bi-person-fill fs-1"></i>
        </div>
        <div class="ms-4">
            <h2 class="mb-0 fw-bold"><?php echo htmlspecialchars($user_data['nombre']); ?></h2>
            <p class="mb-0 opacity-75">Miembro desde: <?php echo date('M Y', strtotime($user_data['created_at'])); ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm h-100 text-center">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold">Puntos Totales</h6>
                    <h2 class="text-primary fw-bold mb-0"><?php echo $stats['total_pts'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm h-100 text-center border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold">Plenos (Resultados Exactos)</h6>
                    <h2 class="text-success fw-bold mb-0"><?php echo $stats['exact_hits'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm h-100 text-center">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold">Pronósticos Realizados</h6>
                    <h2 class="text-dark fw-bold mb-0"><?php echo $stats['total_preds'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-graph-up me-2"></i>Evolución del Campeonato</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bar-chart-steps fs-1 text-muted opacity-25"></i>
                            <p class="text-muted mt-3">Los datos de evolución se generarán tras el próximo partido cerrado.</p>
                        </div>
                    <?php else: ?>
                        <canvas id="evolutionChart" style="width: 100%; height: 300px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-gear me-2"></i>Configuración</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?> <div class="alert alert-success py-2 small"><?php echo $success; ?></div> <?php endif; ?>
                    <?php if ($error): ?> <div class="alert alert-danger py-2 small"><?php echo $error; ?></div> <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre de Usuario</label>
                            <input type="text" name="new_name" class="form-control" value="<?php echo htmlspecialchars($user_data['nombre']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email (No editable)</label>
                            <input type="email" class="form-control bg-light text-muted" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-danger">Cambiar Contraseña</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Nueva contraseña">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Repetir Contraseña</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">Actualizar mis datos</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($history)): ?>
    const ctx = document.getElementById('evolutionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Puntos',
                data: <?php echo json_encode($puntos); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y'
            }, {
                label: 'Posición',
                data: <?php echo json_encode($posiciones); ?>,
                borderColor: '#ef4444',
                borderDash: [5, 5],
                tension: 0.1,
                fill: false,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Puntos Totales' } },
                y1: { type: 'linear', display: true, position: 'right', reverse: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Posición Ranking' } }
            }
        }
    });
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>