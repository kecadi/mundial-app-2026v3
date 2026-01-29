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

// Obtener datos actuales del usuario
$stmt_user = $pdo->prepare("SELECT nombre, email FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

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
        
        $stmt_update = $pdo->prepare($sql);
        $stmt_update->execute($update_params);
        $success = 'Perfil actualizado correctamente.';
        
        $stmt_user->execute([$user_id]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    } elseif (empty($error) && empty($update_fields)) {
        $error = 'No se realizaron cambios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-avatar {
            width: 120px;
            height: 120px;
            background-color: #f8f9fa;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .duel-win { border-left: 5px solid #198754; }
        .duel-loss { border-left: 5px solid #dc3545; }
        .duel-draw { border-left: 5px solid #6c757d; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'profile'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <div class="row align-items-center mb-4">
        <div class="col-auto">
            <?php $avatar_url = "https://api.dicebear.com/7.x/fun-emoji/svg?seed=" . $user_id; ?>
            <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="rounded-circle profile-avatar">
        </div>
        <div class="col">
            <h2 class="mb-0 text-primary">Mi Perfil de Usuario</h2>
            <p class="text-muted mb-0">Gestiona tu identidad y revisa tus estadísticas</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            
            <h4 class="mb-4 text-warning"><i class="bi bi-award-fill"></i> Logros Desbloqueados</h4>
            <?php 
            $stmt_ach = $pdo->prepare("SELECT code, description FROM user_achievements WHERE user_id = ? ORDER BY achieved_at DESC");
            $stmt_ach->execute([$user_id]);
            $achievements = $stmt_ach->fetchAll(PDO::FETCH_ASSOC);

            if (empty($achievements)): ?>
                <p class="alert alert-info border-0 small">Aún no has desbloqueado ningún logro. ¡Sigue participando!</p>
            <?php else: ?>
                <div class="row mb-4">
                    <?php foreach($achievements as $ach): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-warning bg-opacity-10 border-warning border-opacity-25 h-100">
                                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small"><i class="bi bi-patch-check-fill text-warning"></i> <?php echo htmlspecialchars($ach['code']); ?></span>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars($ach['description']); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr class="my-5 opacity-25">

            <h4 class="mb-4 text-primary"><i class="bi bi-swords me-2"></i>Historial de Duelos (Últimos 5)</h4>
            <?php 
            // Buscamos desafíos donde el usuario participó y ya fueron procesados
            $stmt_duels = $pdo->prepare("
                SELECT c.*, u1.nombre as retador, u2.nombre as retado, m.home_score, m.away_score
                FROM match_challenges c
                JOIN users u1 ON c.challenger_user_id = u1.id
                JOIN users u2 ON c.challenged_user_id = u2.id
                JOIN matches m ON c.match_id = m.id
                WHERE (c.challenger_user_id = :uid OR c.challenged_user_id = :uid)
                AND c.wager_status = 'PROCESSED'
                ORDER BY c.id DESC LIMIT 5
            ");
            $stmt_duels->execute(['uid' => $user_id]);
            $duels = $stmt_duels->fetchAll(PDO::FETCH_ASSOC);

            if (empty($duels)): ?>
                <p class="text-muted small italic">No tienes duelos finalizados todavía.</p>
            <?php else: ?>
                <div class="list-group mb-4 shadow-sm">
                    <?php foreach($duels as $d): 
                        // Lógica para determinar si el usuario actual ganó o perdió el duelo
                        // Basado en el calculate.php: quien tenga más puntos en ese partido gana.
                        // Para simplificar la vista, mostraremos si hubo puntos confiscados.
                        $es_retador = ($d['challenger_user_id'] == $user_id);
                        $oponente = $es_retador ? $d['retado'] : $d['retador'];
                        
                        // Nota: En una mejora futura, podrías guardar el winner_id en la tabla match_challenges
                        // Por ahora lo marcamos como 'Duelo Finalizado'
                    ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-start-0 border-end-0 py-3">
                            <div>
                                <span class="fw-bold">Vs. <?php echo htmlspecialchars($oponente); ?></span>
                                <div class="text-muted small">Reto en el partido ID: <?php echo $d['match_id']; ?></div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-dark rounded-pill"><?php echo $d['points_seized']; ?> pts en juego</span>
                                <div class="small text-success mt-1">Procesado</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr class="my-5 opacity-25">

            <form method="POST" action="profile.php">
                <h4 class="mb-4"><i class="bi bi-person-vcard me-2"></i>Información Personal</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Email (No modificable)</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label for="new_name" class="form-label fw-bold">Nombre en el Ranking</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-pencil"></i></span>
                            <input type="text" name="new_name" id="new_name" class="form-control" value="<?php echo htmlspecialchars($user_data['nombre']); ?>" required>
                        </div>
                    </div>
                </div>

                <hr class="my-5 opacity-25">

                <h4 class="mb-4"><i class="bi bi-shield-lock me-2"></i>Seguridad</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <input type="password" name="new_password" id="new_password" class="form-control shadow-sm" placeholder="Dejar en blanco para no cambiar">
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control shadow-sm">
                    </div>
                </div>

                <div class="mt-4 border-top pt-4 text-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                        <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>