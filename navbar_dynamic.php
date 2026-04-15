<?php
require_once(__DIR__ . '/config.php');
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>

<nav class="navbar navbar-expand-lg navbar-dark navbar-pet mb-4">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand fw-bold" href="index.php">PetCare</a>

    <!--  Navbar toggler for mobile -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
            aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar links -->
    <div class="collapse navbar-collapse" id="navMain">
      <!-- Left links -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php?page=products&cat=Food">Food</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=products&cat=Toys">Toys</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=products&cat=Accessories">Accessories</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=cart">
          Cart <span class="badge bg-light text-dark"><?php echo $cart_count; ?></span>
        </a></li>
      </ul>

      <!-- Right side (user/admin/account dropdowns) -->
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

        <!-- Customer logged in -->
        <?php if (is_user()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="index.php?page=orders">My Orders</a></li>
              <li><a class="dropdown-item" href="index.php?page=profile">Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="index.php?action=logout_user">Logout</a></li>
            </ul>
          </li>

        <!--  Admin logged in -->
        <?php elseif (is_admin()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo htmlspecialchars($_SESSION['admin']['username']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
              <li><a class="dropdown-item" href="index.php?page=admin">Dashboard</a></li>
              <li><a class="dropdown-item" href="index.php?page=admin_products">Manage Products</a></li>
              <li><a class="dropdown-item" href="index.php?page=admin_users">Manage Users</a></li>
              <li><a class="dropdown-item" href="index.php?page=admin_orders">Manage Orders</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="index.php?action=logout_admin">Logout</a></li>
            </ul>
          </li>

        <!--  Not logged in -->
        <?php else: ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">
              Account
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
              <li><a class="dropdown-item" href="index.php?page=login">Login</a></li>
              <li><a class="dropdown-item" href="index.php?page=register">Register</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="index.php?page=admin_login">Admin Login</a></li>
            </ul>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
