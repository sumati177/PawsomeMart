<?php
require_once(__DIR__ . '/config.php');
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>
<nav class="navbar navbar-expand-lg navbar-light sticky-top navbar-pet">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <span style="color:var(--pc-primary)">Pet</span>Care
    </a>
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="userNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php?page=products&cat=Food">Food</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=products&cat=Toys">Toys</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=products&cat=Accessories">Accessories</a></li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="index.php?page=cart">
            Cart 
            <span class="badge rounded-pill ms-2" style="background:var(--pc-primary)"><?php echo $cart_count; ?></span>
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <?php if (is_user()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle bg-white px-3 rounded-pill shadow-sm" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
              <span class="text-muted small me-1">Hello,</span> <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2" aria-labelledby="userDropdown">
              <li><a class="dropdown-item py-2" href="index.php?page=user_orders">📦 My Orders</a></li>
              <li><a class="dropdown-item py-2" href="index.php?page=profile">👤 My Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2 text-danger" href="index.php?action=logout_user">🚪 Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link px-3" href="index.php?page=login">Login</a></li>
          <li class="nav-item"><a class="btn btn-pet ms-lg-2 ms-0" href="index.php?page=register">Join Now</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
