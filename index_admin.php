<?php
if (!is_admin()) {
  header('Location: index.php?page=admin_login');
  exit;
}

$u = firestore_get_all('users');
$p = firestore_get_all('products');
$o = firestore_get_all('orders');

$users = is_array($u) ? count($u) : 0;
$products = is_array($p) ? count($p) : 0;
$orders = is_array($o) ? count($o) : 0;
?>
<div class="container">
  <h4>Admin Dashboard</h4>
  <div class="row g-3 mt-1 mb-3">
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><h6>Users</h6><div class="display-6"><?php echo $users; ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><h6>Products</h6><div class="display-6"><?php echo $products; ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><h6>Orders</h6><div class="display-6"><?php echo $orders; ?></div></div></div></div>
  </div>
  <div class="row g-3">
    <div class="col-md-4"><a class="btn btn-pet w-100" href="index.php?page=users_admin">Manage Users</a></div>
    <div class="col-md-4"><a class="btn btn-pet w-100" href="index.php?page=products_admin">Manage Products</a></div>
    <div class="col-md-4"><a class="btn btn-pet w-100" href="index.php?page=orders_admin">Manage Orders</a></div>
  </div>
</div>
