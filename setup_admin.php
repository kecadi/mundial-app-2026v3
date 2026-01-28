<?php
// setup_admin.php
require_once 'config/db.php';

// Datos del admin
$nombre = "Admin";
$email = "admin@mundial.com";
$password_plana = "admin123"; // Esta será tu contraseña para entrar

// Encriptamos la contraseña
$password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

try {
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    
    if($stmt->rowCount() > 0) {
        echo "<h3>El usuario admin ya existe.</h3>";
    } else {
        // Insertar nuevo admin
        $sql = "INSERT INTO users (nombre, email, password, role) VALUES (:nombre, :email, :pass, 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'pass' => $password_hash
        ]);
        
        echo "<h3>¡Usuario Admin creado con éxito!</h3>";
        echo "<p>Email: $email</p>";
        echo "<p>Contraseña: $password_plana</p>";
        echo "<p><a href='login.php'>Ir al Login</a></p>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>