<?php
require_once(__DIR__ . '/config.php');
if (!is_admin()) {
  header('Location: index.php?page=admin_login');
  exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top navbar-pet py-2" style="background: rgba(30, 47, 39, 0.95) !important;">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php?page=index_admin">
      <span class="text-white">Admin</span><span style="color:var(--pc-primary)">Core</span>
    </a>
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white-50" href="index.php?page=products_admin">Products</a></li>
        <li class="nav-item"><a class="nav-link text-white-50" href="index.php?page=users_admin">Users</a></li>
        <li class="nav-item"><a class="nav-link text-white-50" href="index.php?page=orders_admin">Orders</a></li>
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle bg-dark-subtle px-3 rounded-pill text-white" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
            🛡️ <?php echo htmlspecialchars($_SESSION['admin']['username']); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2" aria-labelledby="adminDropdown">
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
