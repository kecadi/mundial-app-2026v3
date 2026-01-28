<?php
// admin/bonus_candidates.php
session_start();
require_once '../config/db.php'; 

// Lógica de borrado (GET)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM bonus_candidates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: bonus_candidates.php?msg=deleted');
    exit;
}

// Obtener todos los candidatos
// NOTA: Usamos SELECT * para asegurarnos de obtener photo_url
$stmt = $pdo->query("SELECT * FROM bonus_candidates ORDER BY type DESC, team_name ASC");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Bonus</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
    $current_page = 'bonus'; 
    include 'includes/navbar.php'; 
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Gestión de Candidatos Bonus (100 Pts)</h3>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#candidateModal" onclick="clearModal()">
            <i class="bi bi-plus-circle"></i> Añadir Candidato
        </button>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                if($_GET['msg'] == 'saved') echo "Candidato guardado correctamente.";
                if($_GET['msg'] == 'deleted') echo "Candidato eliminado.";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['err']) && $_GET['err'] == 'duplicate'): ?>
         <div class="alert alert-danger alert-dismissible fade show">
            Error: Ya existe un candidato con ese nombre y tipo.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Tipo</th>
                        <th style="width: 5%;">Foto</th> 
                        <th>Nombre / Equipo</th>
                        <th>Ganador Real</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($candidates as $c): ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?php echo ($c['type'] === 'scorer') ? 'danger' : 'info'; ?>">
                                <?php echo ($c['type'] === 'scorer') ? 'Goleador' : 'Portero'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($c['photo_url']): ?>
                                <img src="<?php echo htmlspecialchars($c['photo_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($c['name']); ?>" 
                                     style="width: 35px; height: 35px; object-fit: cover; border-radius: 50%;" 
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></span> 
                            <small class="text-muted">(<?php echo htmlspecialchars($c['team_name']); ?>)</small>
                        </td>
                        <td>
                            <?php if ($c['is_winner']): ?>
                                <span class="badge bg-success">🏆 SÍ</span>
                            <?php else: ?>
                                NO
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" 
                                    onclick="editCandidate(<?php echo htmlspecialchars(json_encode($c)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="bonus_candidates.php?action=delete&id=<?php echo $c['id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Seguro? Esto borrará el candidato.')">
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

<div class="modal fade" id="candidateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="save_candidate.php" method="POST">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalTitle">Nuevo Candidato</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="candidate_id">
            <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select name="type" id="type" class="form-select" required>
                    <option value="scorer">Máximo Goleador</option>
                    <option value="keeper">Mejor Portero del Mundial</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre del Jugador</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Equipo (Selección)</label>
                <input type="text" name="team_name" id="team_name" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">URL de la Foto</label>
                <input type="text" name="photo_url" id="photo_url" class="form-control">
                <small class="text-muted">Ej: https://.../mbappe.jpg</small>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_winner" id="is_winner" class="form-check-input" value="1">
                <label class="form-check-label" for="is_winner">Marcar como GANADOR FINAL (Solo al final)</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Candidato</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const candidateModal = new bootstrap.Modal(document.getElementById('candidateModal'));

    function clearModal() {
        document.getElementById('modalTitle').textContent = "Añadir Nuevo Candidato";
        document.getElementById('candidate_id').value = "";
        document.getElementById('type').value = "scorer";
        document.getElementById('name').value = "";
        document.getElementById('team_name').value = "";
        document.getElementById('photo_url').value = ""; // Limpiar
        document.getElementById('is_winner').checked = false;
        candidateModal.show();
    }

    function editCandidate(c) {
        document.getElementById('modalTitle').textContent = "Editar Candidato";
        document.getElementById('candidate_id').value = c.id;
        document.getElementById('type').value = c.type;
        document.getElementById('name').value = c.name;
        document.getElementById('team_name').value = c.team_name;
        
        // CRÍTICO: Cargar la URL de la foto desde el objeto de la base de datos
        document.getElementById('photo_url').value = c.photo_url;
        
        document.getElementById('is_winner').checked = c.is_winner == 1;
        candidateModal.show();
    }
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>