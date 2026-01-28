<?php
// admin/includes/navbar.php

// Definimos la página actual para saber cuál resaltar
$page = isset($current_page) ? $current_page : '';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        ⚙️ Panel Admin
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'alerts') ? 'active fw-bold text-white' : ''; ?>" href="alerts.php">
                    🔔 Alertas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'matches') ? 'active fw-bold text-white' : ''; ?>" href="index.php">
                    ⚽ Resultados
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'knockout') ? 'active fw-bold text-white' : ''; ?>" href="knockout.php">
                    📊 Bracket
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'bonus') ? 'active fw-bold text-white' : ''; ?>" href="bonus_candidates.php">
                    ⭐ Bonus (Goleador/Portero)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'final_results') ? 'active fw-bold text-white' : ''; ?>" href="final_results.php">
                    🥇 Resultados Finales
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'quiz') ? 'active fw-bold text-white' : ''; ?>" href="quiz.php">
                    🧠 Quiz Diario
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                    ⚙️ Cálculos
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="calculate_group_bonus.php">🏆 Bonificación Grupos (A-L)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item fw-bold" href="calculate_bonus_elections.php">⭐ Puntuación Final (Goleador/Portero)</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'teams') ? 'active fw-bold text-white' : ''; ?>" href="teams.php">
                    ⚽ Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'stadiums') ? 'active fw-bold text-white' : ''; ?>" href="stadiums.php">
                    🏟️ Estadios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'users') ? 'active fw-bold text-white' : ''; ?>" href="users.php">
                    👥 Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($page == 'guide') ? 'active fw-bold text-white' : ''; ?>" href="guide.php">
                    📖 Guía/Reset
                </a>
            </li>
                    <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'audit') ? 'active fw-bold' : ''; ?>" href="audit.php">
                📈 Auditoría
            </a>
        </li>
        </ul>

        <div class="d-flex">
            <a href="../index.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-left"></i> Volver a la App
            </a>
        </div>
    </div>
  </div>
</nav>