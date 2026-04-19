<?php
if (!is_user()) {
    $_SESSION['flash_err'] = 'Please login to view your orders.';
    app_redirect('index.php?page=login');
}

$uid = $_SESSION['user']['id'];

// Use runQuery to filter orders by userId server-side (efficient, no full-scan)
$url = 'https://firestore.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/databases/(default)/documents:runQuery';
$query = [
    'structuredQuery' => [
        'from'  => [['collectionId' => 'orders']],
        'where' => [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'userId'],
                'op'    => 'EQUAL',
                'value' => ['stringValue' => $uid],
            ]
        ]
    ]
];
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($query),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
]);
$raw = curl_exec($ch);
curl_close($ch);

$orders = [];
$results = json_decode($raw, true);
if (is_array($results)) {
    foreach ($results as $r) {
        if (isset($r['document']['fields'])) {
            preg_match('/\/([^\/]+)$/', $r['document']['name'], $m);
            $doc = firestore_decode_data($r['document']['fields']);
            $doc['id'] = $m[1] ?? '';
            $orders[] = $doc;
        }
    }
}
?>

<div class="container mt-4">
  <h3 class="mb-3 fw-bold">📦 My Orders</h3>

  <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success text-center">✅ Your order has been placed successfully!</div>
  <?php endif; ?>

  <?php if (count($orders) === 0): ?>
    <div class="alert alert-info">You have not placed any orders yet. <a href="index.php?page=products" class="alert-link">Browse products</a>.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th>Order ID</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Address</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o):
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
            <td><small class="text-muted"><?php echo htmlspecialchars(substr($o['id'],0,10)).'...'; ?></small></td>
            <td>
              <?php
              $items = $o['items'] ?? [];
              if (!empty($items) && is_array($items)) {
                  foreach ($items as $item) {
                      echo '<div class="small">'.htmlspecialchars($item['name'] ?? 'Item').' × '.(int)($item['qty'] ?? 1).'</div>';
                  }
              } else {
                  echo '<small>—</small>';
              }
              ?>
            </td>
            <td>₹<?php echo number_format($o['totalAmount'] ?? $o['total'] ?? 0, 2); ?></td>
            <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span></td>
            <td><?php echo htmlspecialchars($o['paymentMethod'] ?? $o['payment_method'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($o['address'] ?? $o['address_snapshot'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($o['createdAt'] ?? $o['created_at'] ?? ''); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
