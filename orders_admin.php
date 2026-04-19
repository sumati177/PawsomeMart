<?php
// Ensure admin is authenticated - uses app_redirect so cookie survives
if (!is_admin()) {
    app_redirect('index.php?page=admin_login');
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'admin_order_update') {
    $order_id  = $_POST['id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    if ($order_id && $new_status) {
        firestore_update('orders', $order_id, [
            'status'    => $new_status,
            'updatedAt' => date('Y-m-d\TH:i:s\Z')
        ]);
        $_SESSION['flash_msg'] = 'Order status updated.';
        app_redirect('index.php?page=orders_admin');
    }
}

// Fetch ALL orders
$all_orders = firestore_get_all('orders');

// Sort orders by createdAt descending (if possible in PHP)
if ($all_orders) {
    uasort($all_orders, function($a, $b) {
        $da = $a['createdAt'] ?? '';
        $db = $b['createdAt'] ?? '';
        return strcmp($db, $da);
    });
}


// Build a user email map
$all_users = firestore_get_all('users');
$user_map  = [];
if ($all_users) {
    foreach ($all_users as $uid => $u) {
        $user_map[$uid] = $u['email'] ?? $u['name'] ?? $uid;
    }
}
?>
<div class="container mt-4">
  <h4 class="mb-3 fw-bold">🛒 Manage Orders <span class="badge bg-secondary ms-2" style="font-size:0.8rem;"><?php echo count($all_orders); ?> total</span></h4>

  <?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_msg']); unset($_SESSION['flash_msg']); ?></div>
  <?php endif; ?>

  <?php if (empty($all_orders)): ?>
    <div class="alert alert-info">No orders found in the database.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-dark">
        <tr>
          <th style="min-width:120px;">Order ID</th>
          <th>Customer</th>
          <th>Status</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Address</th>
          <th>Items</th>
          <th style="min-width:200px;">Update Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($all_orders as $id => $o):
          $display_user = $user_map[$o['userId'] ?? $o['user_id'] ?? ''] ?? $o['userId'] ?? $o['user_id'] ?? 'Unknown';
          $status = $o['status'] ?? 'Placed';
          $badge = [
            'delivered'  => 'bg-success',
            'processing' => 'bg-warning text-dark',
            'shipped'    => 'bg-info text-dark',
            'cancelled'  => 'bg-danger',
            'placed'     => 'bg-primary',
          ][strtolower($status)] ?? 'bg-secondary';
        ?>
        <tr>
          <td><small class="text-muted"><?php echo htmlspecialchars(substr($id,0,12)).'...'; ?></small></td>
          <td><?php echo htmlspecialchars($display_user); ?></td>
          <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span></td>
          <td>₹<?php echo number_format((float)($o['totalAmount'] ?? $o['total'] ?? 0), 2); ?></td>
          <td><?php echo htmlspecialchars($o['paymentMethod'] ?? $o['payment_method'] ?? 'N/A'); ?></td>
          <td class="small" style="max-width:180px;"><?php echo nl2br(htmlspecialchars($o['address'] ?? $o['address_snapshot'] ?? '')); ?></td>
          <td>
            <?php
            $items = $o['items'] ?? [];
            if (!empty($items) && is_array($items)) {
                foreach ($items as $item) {
                    $iname = $item['name'] ?? ($item['productName'] ?? 'Item');
                    $iqty  = (int)($item['qty'] ?? $item['quantity'] ?? 1);
                    echo '<div class="small">'.htmlspecialchars($iname).' × '.$iqty.'</div>';
                }
            } else {
                echo '<small class="text-muted">—</small>';
            }
            ?>
          </td>
          <td>
            <form method="post" action="index.php?page=orders_admin" class="d-flex gap-2 align-items-center">
              <input type="hidden" name="act" value="admin_order_update">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
              <select name="status" class="form-select form-select-sm">
                <?php foreach (['Placed','Processing','Shipped','Delivered','Cancelled'] as $s): ?>
                  <option <?php echo $status === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-pet text-nowrap">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>