<?php
// admin/stadiums.php
session_start();
require_once '../config/db.php'; 

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Lógica de borrado
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM stadiums WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: stadiums.php?msg=deleted');
    exit;
}

// Obtener estadios
$stmt = $pdo->query("SELECT * FROM stadiums ORDER BY name ASC");
$stadiums = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Estadios</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stadium-img { width: 100px; height: 50px; object-fit: cover; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'stadiums'; 
    include 'includes/navbar.php'; 
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Gestión de Estadios y Fondos</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stadiumModal" onclick="clearModal()">
            <i class="bi bi-plus-circle"></i> Nuevo Estadio
        </button>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                if($_GET['msg'] == 'saved') echo "Estadio guardado correctamente.";
                if($_GET['msg'] == 'deleted') echo "Estadio eliminado.";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Estadio</th>
                        <th>Ubicación</th>
                        <th>Fondo (Preview)</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($stadiums as $s): ?>
                    <tr>
                        <td><span class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></span></td>
                        <td><?php echo htmlspecialchars($s['city_country']); ?></td>
                        <td>
                            <?php if ($s['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($s['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($s['name']); ?>" 
                                     class="stadium-img">
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" 
                                    onclick="editStadium(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="stadiums.php?action=delete&id=<?php echo $s['id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Seguro que quieres eliminar este estadio?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="stadiumModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="save_stadium.php" method="POST">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalTitle">Nuevo Estadio</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="stadium_id">
            <div class="mb-3">
                <label class="form-label">Nombre del Estadio (Debe coincidir con la tabla 'matches')</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ubicación (Ciudad, País)</label>
                <input type="text" name="city_country" id="city_country" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">URL o Ruta de la Imagen de Fondo</label>
                <input type="text" name="image_url" id="image_url" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Estadio</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const stadiumModal = new bootstrap.Modal(document.getElementById('stadiumModal'));

    function clearModal() {
        document.getElementById('modalTitle').textContent = "Añadir Nuevo Estadio";
        document.getElementById('stadium_id').value = "";
        document.getElementById('name').value = "";
        document.getElementById('city_country').value = "";
        document.getElementById('image_url').value = "";
        stadiumModal.show();
    }

    function editStadium(s) {
        document.getElementById('modalTitle').textContent = "Editar: " + s.name;
        document.getElementById('stadium_id').value = s.id;
        document.getElementById('name').value = s.name;
        document.getElementById('city_country').value = s.city_country;
        document.getElementById('image_url').value = s.image_url;
        stadiumModal.show();
    }
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>