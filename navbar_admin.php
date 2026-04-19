<?php
require_once(__DIR__ . '/config.php');
if (!is_admin()) {
  app_redirect('index.php?page=admin_login');
}
?>
<nav class="navbar navbar-expand-lg sticky-top py-2 navbar-admin" style="background-color: #1e293b; color: #ffffff;">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php?page=index_admin">
      <span class="text-white">Admin</span><span style="color:#4ade80;">Core</span>
    </a>
    <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link nav-link-admin fw-bold" href="index.php?page=products_admin">
            📦 Products
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-admin fw-bold" href="index.php?page=users_admin">
            👥 Users
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-admin fw-bold" href="index.php?page=orders_admin">
            🛒 Orders
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle px-3 nav-button-admin" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
            🛡️ <?php echo htmlspecialchars($_SESSION['admin']['username']); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 admin-dropdown-menu" aria-labelledby="adminDropdown">
            <li><a class="dropdown-item py-2" href="index.php?page=index_admin">📊 Dashboard</a></li>
            <li><a class="dropdown-item py-2" href="index.php">🌐 View Site</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item py-2 text-danger" href="index.php?action=logout_admin">🚪 Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
