<?php
// setup_user.php
require_once 'config/db.php';

$nombre = "kepacampo";
$email = "kepa@mundial.com"; // Inventado para el login
$password_plana = "Mundial26*";

// Encriptar contraseña
$password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

try {
    // Verificamos si ya existe el email para no dar error
    $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute(['email' => $email]);

    if ($check->rowCount() > 0) {
        echo "<h3>El usuario ya existe.</h3>";
    } else {
        $sql = "INSERT INTO users (nombre, email, password, role) VALUES (:nombre, :email, :pass, 'user')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'pass' => $password_hash
        ]);
        
        echo "<h3>✅ Usuario Jugador Creado</h3>";
        echo "<p>Usuario: $nombre</p>";
        echo "<p>Email: $email</p>";
        echo "<p>Pass: $password_plana</p>";
        echo "<p>Role: user (Jugador)</p>";
        echo "<br><a href='logout.php'>Cerrar sesión actual y probar</a>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>