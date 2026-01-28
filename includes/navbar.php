<?php
// includes/navbar.php

// Asegurarnos de que variables existen para evitar errores
$page = isset($current_page) ? $current_page : '';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$name = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <img src="assets/img/logotipo.png" alt="Logotipo Mundial 2026" style="height: 40px; width: auto;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'home') ? 'active fw-bold' : ''; ?>" href="index.php">
                🏠 Inicio
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'challenge') ? 'active fw-bold' : ''; ?>" href="challenge.php">
                ⚔️ Desafíos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'groups') ? 'active fw-bold' : ''; ?>" href="groups.php">
                📊 Clasificación Grupos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'bonus') ? 'active fw-bold' : ''; ?>" href="bonus.php">
                ⭐ Elecciones Bonus
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'stats') ? 'active fw-bold' : ''; ?>" href="stats.php">
                📈 Estadísticas
            </a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
            <a class="text-white nav-link" href="profile.php">Hola, <strong><?php echo htmlspecialchars($name); ?></strong></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'rules') ? 'active fw-bold' : ''; ?>" href="rules.php">
                📜 Reglas
            </a>
        </li>
        <?php if($role === 'admin'): ?>
            <li class="nav-item me-2">
                <a class="btn btn-warning btn-sm text-dark fw-bold" href="admin/index.php">
                    ⚙️ Panel Admin
                </a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item">
          <a class="btn btn-danger btn-sm" href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Salir
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>