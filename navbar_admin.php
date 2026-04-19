<?php
require_once(__DIR__ . '/config.php');
if (!is_admin()) {
  app_redirect('index.php?page=admin_login');
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top py-2" style="background: rgba(20, 35, 28, 0.98) !important; border-bottom: 1px solid rgba(255,255,255,0.1); box-shadow: 0 2px 20px rgba(0,0,0,0.4);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php?page=index_admin" style="font-size:1.4rem; letter-spacing:-0.3px;">
      <span style="color:#ffffff;">Admin</span><span style="color:#4ade80;">Core</span>
    </a>
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" style="color:#fff;">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link fw-500" href="index.php?page=products_admin" style="color:#e2e8f0 !important; font-weight:500; opacity:1; padding: 0.5rem 1rem;">
            📦 Products
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=users_admin" style="color:#e2e8f0 !important; font-weight:500; opacity:1; padding: 0.5rem 1rem;">
            👥 Users
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=orders_admin" style="color:#e2e8f0 !important; font-weight:500; opacity:1; padding: 0.5rem 1rem;">
            🛒 Orders
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle px-3 rounded-pill" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown"
             style="color:#ffffff !important; background: rgba(255,255,255,0.12); font-weight:600; border: 1px solid rgba(255,255,255,0.2);">
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
