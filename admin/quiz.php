<?php
// admin/quiz.php
session_start();
require_once '../config/db.php'; 

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Lógica de borrado
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Primero, borramos las respuestas asociadas a esta pregunta para evitar fallos de Foreign Key
    $pdo->prepare("DELETE FROM daily_quiz_responses WHERE question_id = ?")->execute([$_GET['id']]);
    
    // Luego, borramos la pregunta
    $pdo->prepare("DELETE FROM daily_quiz_questions WHERE id = ?")->execute([$_GET['id']]);
    header('Location: quiz.php?msg=deleted');
    exit;
}

// Obtener todas las preguntas
// NOTA: 'date_available' es necesario para la carga de datos al editar.
$stmt = $pdo->query("SELECT * FROM daily_quiz_questions ORDER BY id DESC");
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quiz Diario</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .table-quiz td { font-size: 0.9rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'quiz'; 
    include 'includes/navbar.php'; 
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>🧠 Gestión de Preguntas Diarias</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal" onclick="clearModal()">
            <i class="bi bi-plus-circle"></i> Nueva Pregunta
        </button>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Pregunta guardada/eliminada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 table-quiz align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Pregunta</th>
                            <th>Resp. Correcta</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($questions as $q): ?>
                        <tr>
                            <td><?php echo $q['id']; ?></td>
                            <td><?php echo htmlspecialchars($q['question']); ?></td>
                            <td>
                                <span class="badge bg-success"><?php echo $q['correct_answer']; ?></span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editQuestion(<?php echo htmlspecialchars(json_encode($q)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="quiz.php?action=delete&id=<?php echo $q['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Seguro? Esto borrará todas las respuestas históricas.')">
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
</div>

<div class="modal fade" id="questionModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="save_quiz_question.php" method="POST">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalTitle">Nueva Pregunta</h5>
            <button type="button" class="btn-close white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="question_id">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Pregunta:</label>
                <textarea name="question" id="question" class="form-control" rows="3" required></textarea>
            </div>
            
            <hr>
            <h6 class="fw-bold">Opciones de Respuesta:</h6>
            <div class="row">
                <?php $options = ['A', 'B', 'C', 'D']; ?>
                <?php foreach ($options as $opt): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opción <?php echo $opt; ?></label>
                        <input type="text" name="option_<?php echo strtolower($opt); ?>" id="option_<?php echo strtolower($opt); ?>" class="form-control" required>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Respuesta Correcta:</label>
                <select name="correct_answer" id="correct_answer" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($options as $opt): ?>
                        <option value="<?php echo $opt; ?>">Opción <?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Fecha de Lanzamiento (Día del Quiz):</label>
                <input type="date" name="date_available" id="date_available" class="form-control" required>
                <small class="text-muted">Solo se puede programar una pregunta por día.</small>
            </div>
            
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Pregunta</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const questionModal = new bootstrap.Modal(document.getElementById('questionModal'));

    function clearModal() {
        document.getElementById('modalTitle').textContent = "Añadir Nueva Pregunta";
        document.getElementById('question_id').value = "";
        
        // Limpiar campos de texto
        document.getElementById('question').value = "";
        document.getElementById('option_a').value = "";
        document.getElementById('option_b').value = "";
        document.getElementById('option_c').value = "";
        document.getElementById('option_d').value = "";
        
        // Limpiar SELECT y Fecha
        document.getElementById('correct_answer').value = "";
        document.getElementById('date_available').value = ""; 
        questionModal.show();
    }

    function editQuestion(q) {
        document.getElementById('modalTitle').textContent = "Editar Pregunta ID: " + q.id;
        document.getElementById('question_id').value = q.id;
        
        // CARGAR DATOS
        document.getElementById('question').value = q.question; 
        document.getElementById('option_a').value = q.option_a; 
        document.getElementById('option_b').value = q.option_b; 
        document.getElementById('option_c').value = q.option_c; 
        document.getElementById('option_d').value = q.option_d; 
        
        // CARGAR SELECT Y FECHA
        document.getElementById('correct_answer').value = q.correct_answer;
        document.getElementById('date_available').value = q.date_available; 
        
        questionModal.show();
    }
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>