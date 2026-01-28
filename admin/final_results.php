<?php
// admin/final_results.php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Obtener lista de equipos y resultados actuales
$teams_list = $pdo->query("SELECT id, name, flag FROM teams ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$current_results = $pdo->query("SELECT final_total_goals, champion_team_id FROM tournament_results WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$current_goals = $current_results['final_total_goals'] ?? null;
$current_champion = $current_results['champion_team_id'] ?? null;

// Lógica de Guardado (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $final_goals = $_POST['final_goals'] ?? null;
    $champion_id = $_POST['champion_id'] ?? null;
    
    // Convertir valor vacío a NULL
    if ($final_goals === '') $final_goals = NULL;
    if ($champion_id === '') $champion_id = NULL;

    $sql = "UPDATE tournament_results SET final_total_goals = ?, champion_team_id = ? WHERE id = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$final_goals, $champion_id]);
    
    header('Location: final_results.php?msg=saved');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Resultados Finales</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php 
    $current_page = 'final_results'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4">Registro de Resultados Oficiales</h2>
    <p class="text-muted">Introduce los resultados finales del torneo para poder asignar los puntos de bonificación correspondientes.</p>
    
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success fw-bold">Resultados guardados correctamente.</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="final_results.php">
                
                <div class="mb-4">
                    <label for="champion_id" class="form-label fw-bold">🏆 Campeón del Mundial (150 Puntos)</label>
                    <select name="champion_id" id="champion_id" class="form-select" required>
                        <option value="">-- Selecciona el Ganador --</option>
                        <?php foreach($teams_list as $team): ?>
                            <option value="<?php echo $team['id']; ?>" <?php echo ($current_champion === $team['id']) ? 'selected' : ''; ?>>
                                <?php echo $team['flag']; ?> <?php echo htmlspecialchars($team['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="final_goals" class="form-label fw-bold">⚽ Total de Goles del Torneo (25 Puntos)</label>
                    <input type="number" name="final_goals" id="final_goals" class="form-control" 
                           value="<?php echo htmlspecialchars($current_goals); ?>" 
                           placeholder="Ej: 168" min="0" required>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg">Guardar Resultados Oficiales</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>