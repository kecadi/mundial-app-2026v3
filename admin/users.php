<?php
// admin/users.php
session_start();
require_once '../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Obtener usuarios
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Usuarios</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
    $current_page = 'matches'; // Marcamos esta página como activa
    include 'includes/navbar.php'; 
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Gestión de Familiares</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="limpiarModal()">
            <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
        </button>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                if($_GET['msg'] == 'created') echo "Usuario creado correctamente.";
                if($_GET['msg'] == 'updated') echo "Usuario actualizado.";
                if($_GET['msg'] == 'deleted') echo "Usuario eliminado.";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['err'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            Error: El email ya existe o hubo un fallo técnico.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Nombre</th>
                            <th>Email (Login)</th>
                            <th>Rol</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td>
                                <span class="fw-bold"><?php echo htmlspecialchars($u['nombre']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <?php if($u['role'] === 'admin'): ?>
                                    <span class="badge bg-dark">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">Jugador</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        title="Editar"
                                        onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="user_actions.php?action=delete&id=<?php echo $u['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       title="Borrar"
                                       onclick="return confirm('¿Seguro? Se borrarán también sus puntos.')">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="user_actions.php" method="POST">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalTitle">Nuevo Usuario</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="user_id">
            <input type="hidden" name="action" id="form_action" value="create">
            
            <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="text" name="password" id="password" class="form-control" placeholder="Escribe para cambiarla">
                <small class="text-muted" id="passHelp">Si lo dejas vacío, se mantiene la actual.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Rol</label>
                <select name="role" id="role" class="form-select">
                    <option value="user">Jugador</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const userModal = new bootstrap.Modal(document.getElementById('userModal'));

    function limpiarModal() {
        document.getElementById('modalTitle').textContent = "Nuevo Usuario";
        document.getElementById('form_action').value = "create";
        document.getElementById('user_id').value = "";
        document.getElementById('nombre').value = "";
        document.getElementById('email').value = "";
        document.getElementById('password').value = "";
        document.getElementById('password').required = true; 
        document.getElementById('passHelp').style.display = 'none';
        document.getElementById('role').value = "user";
    }

    function editarUsuario(user) {
        document.getElementById('modalTitle').textContent = "Editar: " + user.nombre;
        document.getElementById('form_action').value = "update";
        document.getElementById('user_id').value = user.id;
        document.getElementById('nombre').value = user.nombre;
        document.getElementById('email').value = user.email;
        document.getElementById('password').value = ""; 
        document.getElementById('password').required = false; 
        document.getElementById('passHelp').style.display = 'block';
        document.getElementById('role').value = user.role;
        
        userModal.show();
    }
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>