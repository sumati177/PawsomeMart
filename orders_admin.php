<?php 
if(!is_admin()){ 
    header('Location:index.php?page=admin_login'); 
    exit; 
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'admin_order_update') {
    $order_id = $_POST['id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    if ($order_id && $new_status) {
        firestore_update('orders', $order_id, [
            'status' => $new_status,
            'updated_at' => date('Y-m-d\TH:i:s\Z')
        ]);
        $_SESSION['flash_msg'] = 'Order status updated successfully.';
        header('Location: index.php?page=orders_admin');
        exit;
    }
}
?>
<div class="container">
  <h4>Manage Orders</h4>
  <?php if(isset($_SESSION['flash_msg'])){ echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['flash_msg']).'</div>'; unset($_SESSION['flash_msg']); } ?>
  <div class="table-responsive mt-3">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>User</th><th>Status</th><th>Total</th><th>Payment</th><th>Address</th><th>Items</th><th>Update</th></tr></thead>
      <tbody>
        <?php 
        $all_orders = firestore_get_all('orders');
        if ($all_orders) {
          foreach($all_orders as $id => $o): 
        ?>
        <tr>
          <td><?php echo htmlspecialchars($id); ?></td>
          <td><?php echo htmlspecialchars($o['user_name'] ?? 'User'); ?></td>
          <td><span class="<?php echo 'badge-status '.(strtolower($o['status'])==='delivered'?'badge-delivered':(strtolower($o['status'])==='processing'?'badge-processing':(strtolower($o['status'])==='shipped'?'badge-shipped':(strtolower($o['status'])==='cancelled'?'badge-cancelled':'badge-placed')))); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
          <td>₹<?php echo number_format($o['total'],2); ?></td>
          <td><?php echo htmlspecialchars($o['payment_method'] ?? 'N/A'); ?></td>
          <td class="small"><?php echo nl2br(htmlspecialchars($o['address_snapshot'] ?? '')); ?></td>
          <td><?php 
            if (isset($o['items']) && is_array($o['items'])) {
              foreach($o['items'] as $item){ 
                echo '<div class="small">'.htmlspecialchars($item['name']).' × '.(int)$item['qty'].'</div>'; 
              }
            }
          ?></td>
          <td>
            <form method="post" class="d-flex gap-2">
              <input type="hidden" name="act" value="admin_order_update">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
              <select name="status" class="form-select form-select-sm">
                <?php foreach(['Placed','Processing','Shipped','Delivered','Cancelled'] as $s): ?><option <?php echo ($o['status'] ?? '')===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-pet">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; } ?>
      </tbody>
    </table>
  </div>
</div>