<?php
// admin/user_actions.php
session_start();
require_once '../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado");
}

// Recoger acción (puede venir por POST o por GET para borrar)
$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'create') {
        // CREAR USUARIO
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $pass = $_POST['password'];
        $role = $_POST['role'];

        // Hash password
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (nombre, email, password, role) VALUES (:n, :e, :p, :r)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['n' => $nombre, 'e' => $email, 'p' => $hash, 'r' => $role]);

        header('Location: users.php?msg=created');

    } elseif ($action === 'update') {
        // ACTUALIZAR USUARIO
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $pass = $_POST['password'];

        // Si escribieron contraseña nueva, la actualizamos. Si no, dejamos la vieja.
        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET nombre=:n, email=:e, password=:p, role=:r WHERE id=:id";
            $params = ['n' => $nombre, 'e' => $email, 'p' => $hash, 'r' => $role, 'id' => $id];
        } else {
            $sql = "UPDATE users SET nombre=:n, email=:e, role=:r WHERE id=:id";
            $params = ['n' => $nombre, 'e' => $email, 'r' => $role, 'id' => $id];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header('Location: users.php?msg=updated');

    } elseif ($action === 'delete') {
        // BORRAR USUARIO
        $id = $_GET['id'];
        
        // Evitar borrarse a sí mismo
        if ($id == $_SESSION['user_id']) {
            die("No puedes borrarte a ti mismo.");
        }

        // Primero borramos sus predicciones (Foreign Key constraint)
        $pdo->prepare("DELETE FROM predictions WHERE user_id = :uid")->execute(['uid' => $id]);
        
        // Luego borramos al usuario
        $pdo->prepare("DELETE FROM users WHERE id = :uid")->execute(['uid' => $id]);

        header('Location: users.php?msg=deleted');
    }

} catch (PDOException $e) {
    // Error común: Email duplicado
    if ($e->getCode() == 23000) {
         header('Location: users.php?err=duplicate');
    } else {
         die("Error SQL: " . $e->getMessage());
    }
}
?>