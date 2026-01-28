<?php
// admin/alerts.php
session_start();
require_once '../config/db.php'; 

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// 1. Encontrar el Próximo Partido Programado
$stmt_next_match = $pdo->query("SELECT 
    m.id, m.match_date, 
    t1.name AS home_name, t2.name AS away_name 
FROM matches m
JOIN teams t1 ON m.team_home_id = t1.id
JOIN teams t2 ON m.team_away_id = t2.id
WHERE m.status = 'scheduled'
ORDER BY m.match_date ASC
LIMIT 1");

$next_match = $stmt_next_match->fetch(PDO::FETCH_ASSOC);

$users_to_alert = [];

if ($next_match) {
    $next_match_id = $next_match['id'];
    $match_date = date('D, d/m H:i', strtotime($next_match['match_date']));

    // 2. Query para encontrar usuarios que NO han predicho este partido
    $sql_non_predictors = "SELECT 
        u.id, u.nombre, u.email
    FROM users u
    
    /* LEFT JOIN para ver si existe una predicción para ESTE partido y ESTE usuario */
    LEFT JOIN predictions p ON u.id = p.user_id AND p.match_id = :mid
    
    /* Filtrar: Rol 'user' Y la predicción (p.id) no existe (IS NULL) */
    WHERE u.role = 'user' AND p.id IS NULL";

    $stmt_alerts = $pdo->prepare($sql_non_predictors);
    $stmt_alerts->execute(['mid' => $next_match_id]);
    $users_to_alert = $stmt_alerts->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Alertas</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
    $current_page = 'alerts'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4">🔔 Panel de Alertas y Control</h2>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark fw-bold">
            <?php if ($next_match): ?>
                Próximo Partido a Jugar: <?php echo htmlspecialchars($next_match['home_name']); ?> vs <?php echo htmlspecialchars($next_match['away_name']); ?>
                <br><small><?php echo $match_date; ?></small>
            <?php else: ?>
                No hay partidos programados.
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($next_match): ?>
                
                <?php if (count($users_to_alert) > 0): ?>
                    <p class="alert alert-danger fw-bold">
                        🚨 Hay <?php echo count($users_to_alert); ?> usuario(s) pendiente(s) de predicción.
                    </p>
                    <table class="table table-striped table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users_to_alert as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="alert alert-success fw-bold">
                        ✅ ¡Todos los usuarios han predicho el próximo partido!
                    </p>
                <?php endif; ?>

            <?php else: ?>
                 <p class="text-muted">El torneo ha finalizado o aún no se han generado los partidos.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>