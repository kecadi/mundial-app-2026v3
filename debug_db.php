<?php
// debug_db.php
$host = 'localhost';
$port = '3307'; // Tu puerto personalizado
$username = 'admin_mundial'; 
$password = 'secreto'; 

echo "<h2>Diagnóstico de Base de Datos</h2>";

try {
    // Nos conectamos SIN especificar dbname para que no falle si no existe
    $dsn = "mysql:host=$host;port=$port;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>✅ Conexión exitosa al servidor MySQL en el puerto $port.</p>";
    
    // Listar todas las bases de datos disponibles
    echo "<h3>Bases de datos encontradas:</h3>";
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    $encontrada = false;
    foreach ($dbs as $db) {
        echo "<li>" . htmlspecialchars($db) . "</li>";
        if ($db === 'mundial_db') {
            $encontrada = true;
        }
    }
    echo "</ul>";

    if ($encontrada) {
        echo "<h3 style='color:green'>¡La base de datos 'mundial_db' EXISTE!</h3>";
        echo "<p>Si ves esto y setup_admin falla, revisa si tienes permisos o typos en config/db.php</p>";
    } else {
        echo "<h3 style='color:red'>La base de datos 'mundial_db' NO aparece en la lista.</h3>";
        echo "<p>Intentando crearla automáticamente...</p>";
        
        try {
            $pdo->exec("CREATE DATABASE mundial_db");
            echo "<h3 style='color:blue'>✨ Base de datos creada ahora mismo.</h3>";
            echo "<p>Ahora necesitamos crear la tabla 'users'. Recarga esta página para ver si ahora aparece en la lista.</p>";
            
            // Seleccionar la base de datos recién creada
            $pdo->exec("USE mundial_db");
            
            // Crear la tabla users
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'user') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
            echo "<p style='color:blue'>✨ Tabla 'users' creada.</p>";
            
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error al intentar crearla: " . $e->getMessage() . "</p>";
            echo "<p>Es probable que el usuario 'admin_mundial' no tenga permisos para CREAR bases de datos nuevas.</p>";
        }
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error crítico de conexión: " . $e->getMessage() . "</p>";
    echo "<p>Asegúrate de que el puerto 3307 es correcto y el usuario/contraseña están bien.</p>";
}
?>