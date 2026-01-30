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
                📊 Grupos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'bonus') ? 'active fw-bold' : ''; ?>" href="bonus.php">
                ⭐ Bonus
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($page == 'stats') ? 'active fw-bold' : ''; ?>" href="stats.php">
                📈 Stats
            </a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
            <a class="text-white nav-link d-flex align-items-center" href="profile.php">
                <i class="bi bi-person-circle me-2"></i>
                <span>Hola, <strong><?php echo htmlspecialchars($name); ?></strong></span>
            </a>
        </li>
        <li class="nav-item me-2">
            <a class="nav-link <?php echo ($page == 'rules') ? 'active fw-bold' : ''; ?>" href="rules.php">
                📜 Reglas
            </a>
        </li>
        <?php if($role === 'admin'): ?>
            <li class="nav-item me-2">
                <a class="btn btn-warning btn-sm text-dark fw-bold" href="admin/index.php">
                    ⚙️ Admin
                </a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm border-0" href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Salir
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000; margin-top: 60px;">
    <div id="globalToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <div class="d-flex align-items-center">
                    <div id="toastIcon" class="me-2 fs-5"></div>
                    <div id="toastMessage"></div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
/**
 * Función global para mostrar notificaciones Toast
 * @param {string} mensaje - El texto a mostrar
 * @param {string} tipo - 'success', 'danger', 'warning', 'info'
 * @param {string} icon - Clase de Bootstrap Icon (ej: 'bi-check-circle')
 */
function showNotification(mensaje, tipo = 'primary', icon = 'bi-info-circle') {
    const toastEl = document.getElementById('globalToast');
    const msgEl = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');
    
    // Configurar color de fondo según tipo
    toastEl.classList.remove('bg-primary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info');
    toastEl.classList.add('bg-' + tipo);
    
    // Configurar texto e icono
    msgEl.innerText = mensaje;
    iconEl.innerHTML = `<i class="bi ${icon}"></i>`;
    
    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    toast.show();
}
</script>