<?php
// login.php
session_start();
require_once 'config/db.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);

    if (empty($email) || empty($pass)) {
        $error = "Por favor, rellena todos los campos.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = "Credenciales incorrectas.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mundial 2026</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0;
        }
        .login-card { 
            max-width: 400px; 
            width: 100%; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            background: white; 
        }
        .logo-area { text-align: center; margin-bottom: 25px; }
        /* Estilo para la imagen banner */
        .banner-img { 
            width: 100%; 
            height: auto; 
            border-radius: 8px; 
            margin-bottom: 20px;
            object-fit: cover;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-area">
        <h3 class="fw-bold text-primary">Mundial 2026</h3>
        <p class="text-muted small mb-3">Predicciones Familiares</p>
        
        <img src="assets/img/IMAGEN-WEB-TICKETS-1-1300x500.jpg" alt="FIFA World Cup 2026" class="banner-img shadow-sm">
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="email" class="form-label small fw-bold">Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com" required>
            </div>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label small fw-bold">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
            Entrar al Mundial
        </button>
    </form>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</body>
</html>