<?php
// admin/teams.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Lógica de Guardado (POST)
if (isset($_POST['action']) && $_POST['action'] === 'save_key_players') {
    $id = $_POST['team_id'];
    $players = $_POST['key_players'];
    
    $sql = "UPDATE teams SET key_players = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$players, $id]);
    header('Location: teams.php?msg=saved');
    exit;
}

// Obtener todos los equipos
$stmt = $pdo->query("SELECT id, name, flag, group_name, key_players FROM teams ORDER BY group_name, name");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Equipos</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Estilo para las banderas en la tabla de administración */
        .flag-admin { 
            width: 25px; 
            height: auto; 
            border-radius: 4px; 
            border: 1px solid rgba(0,0,0,0.1); 
            vertical-align: middle; 
        }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'teams'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4">Gestión de Equipos y Jugadores Clave</h2>
    <p class="text-muted">Introduce los nombres separados por coma (ej: Messi, Di María, Dibu Martínez).</p>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success fw-bold">Jugadores clave guardados correctamente.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Grupo</th>
                            <th>Selección</th>
                            <th>Jugadores Clave</th>
                            <th class="text-end">Guardar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teams as $t): ?>
                        <form action="teams.php" method="POST">
                            <input type="hidden" name="action" value="save_key_players">
                            <input type="hidden" name="team_id" value="<?php echo $t['id']; ?>">
                            <tr>
                                <td><span class="badge bg-secondary">Grupo <?php echo $t['group_name']; ?></span></td>
                                <td class="fw-bold">
                                    <?php 
                                        // Definimos la ruta de la bandera (desde admin/ subimos un nivel)
                                        $flag_path = "../assets/img/banderas/" . $t['flag'] . ".png";
                                        
                                        if (file_exists($flag_path)): 
                                    ?>
                                        <img src="<?php echo $flag_path; ?>" class="flag-admin me-2" alt="<?php echo $t['flag']; ?>">
                                    <?php else: ?>
                                        <small class="text-muted me-2">[<?php echo strtoupper($t['flag']); ?>]</small>
                                    <?php endif; ?>
                                    
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </td>
                                <td>
                                    <input type="text" name="key_players" class="form-control form-control-sm" 
                                           value="<?php echo htmlspecialchars($t['key_players']); ?>" 
                                           placeholder="Ej: Mbappé, Griezmann" required>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-save"></i> Guardar
                                    </button>
                                </td>
                            </tr>
                        </form>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>