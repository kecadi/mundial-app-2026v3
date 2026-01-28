<?php
// admin/audit.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Consulta para ver el estado de cada usuario
$sql = "SELECT 
            u.id, u.nombre, u.email,
            u.wildcard_used_match_id,
            (SELECT COUNT(*) FROM match_challenges WHERE challenger_user_id = u.id) as duelos_lanzados,
            (SELECT COUNT(*) FROM match_challenges WHERE challenged_user_id = u.id) as duelos_recibidos
        FROM users u 
        WHERE u.role = 'user' 
        ORDER BY u.nombre ASC";

$stmt = $pdo->query($sql);
$audit_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Auditoría Estratégica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-eye-fill"></i> Auditoría de Estrategia</h2>
    
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Familiar</th>
                        <th class="text-center">Comodín x2</th>
                        <th class="text-center">Duelos Lanzados</th>
                        <th class="text-center">Duelos Recibidos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($audit_data as $row): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($row['nombre']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td class="text-center">
                            <?php if ($row['wildcard_used_match_id']): ?>
                                <span class="badge bg-danger">AGOTADO</span>
                                <div class="small text-muted">ID Partido: <?php echo $row['wildcard_used_match_id']; ?></div>
                            <?php else: ?>
                                <span class="badge bg-success">DISPONIBLE</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-primary"><?php echo $row['duelos_lanzados']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-info text-dark"><?php echo $row['duelos_recibidos']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>