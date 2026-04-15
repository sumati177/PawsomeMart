<?php
if (!is_user()) {
    $_SESSION['flash_err'] = 'Please login first.';
    header('Location: index.php?page=login');
    exit;
}

$uid = $_SESSION['user']['id'];
$all_orders = firestore_get_all('orders');
$orders = [];
if ($all_orders) {
    foreach ($all_orders as $id => $o) {
        if (isset($o['user_id']) && $o['user_id'] === $uid) {
            $o['id'] = $id;
            $orders[] = $o;
        }
    }
}
?>

<div class="container mt-4">
  <h3 class="mb-3">My Orders</h3>

  <?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-success text-center">✅ Your order has been placed successfully!</div>
  <?php endif; ?>

  <?php if (count($orders) === 0): ?>
    <div class="alert alert-info">You have not placed any orders yet.</div>
  <?php else: ?>
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Total</th>
          <th>Status</th>
          <th>Payment</th>
          <th>Address</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?php echo htmlspecialchars($o['id']); ?></td>
            <td>₹<?php echo number_format($o['total'], 2); ?></td>
            <td><?php echo htmlspecialchars($o['status']); ?></td>
            <td><?php echo htmlspecialchars($o['payment_method']); ?></td>
            <td><?php echo htmlspecialchars($o['address_snapshot']); ?></td>
            <td><?php echo htmlspecialchars($o['created_at'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
