<?php
if (!is_user()) { app_redirect('index.php?page=login'); }
if (empty($_SESSION['cart'])) { app_redirect('index.php?page=cart'); }

// Always pull freshest user data from Firestore before rendering checkout
$fresh = firestore_get('users', $_SESSION['user']['id']);
if ($fresh && !isset($fresh['error'])) {
    $_SESSION['user']['address'] = $fresh['address'] ?? '';
    $_SESSION['user']['phone']   = $fresh['phone'] ?? '';
}

$user  = $_SESSION['user'];
$total = 0;
foreach ($_SESSION['cart'] as $it) $total += $it['price'] * $it['qty'];
?>
<div class="container">
  <h4 class="fw-bold mb-4">🛍️ Checkout</h4>

  <?php if (empty($user['address'])): ?>
    <div class="alert alert-warning">
      ⚠️ You need to add a delivery address before placing an order.
      <a href="index.php?page=profile" class="btn btn-sm btn-pet ms-3">Add Address</a>
    </div>
  <?php else: ?>

  <div class="row g-3">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header card-header-pet fw-bold">💳 Payment Method</div>
        <div class="card-body">
          <form method="post" action="index.php?page=checkout&action=place">
            <input type="hidden" name="act" value="checkout">
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="payment" id="cod" value="COD" checked>
              <label class="form-check-label" for="cod">💵 Cash on Delivery</label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="payment" id="upi" value="UPI">
              <label class="form-check-label" for="upi">📱 UPI (Demo)</label>
            </div>
            <button class="btn btn-pet w-100 mt-2">✅ Place Order</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-header card-header-pet fw-bold">📍 Delivery Address</div>
        <div class="card-body">
          <p class="mb-1"><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
          <?php if (!empty($user['phone'])): ?>
            <p class="small text-muted mt-1">📞 <?php echo htmlspecialchars($user['phone']); ?></p>
          <?php endif; ?>
          <a href="index.php?page=profile" class="btn btn-outline-pet btn-sm mt-2">✏️ Edit Address</a>
        </div>
      </div>
      <div class="card shadow-sm mt-3">
        <div class="card-header card-header-pet fw-bold">🧾 Order Summary</div>
        <div class="card-body">
          <?php foreach ($_SESSION['cart'] as $it): ?>
            <div class="d-flex justify-content-between small mb-1">
              <span><?php echo htmlspecialchars($it['name']); ?> × <?php echo (int)$it['qty']; ?></span>
              <span>₹<?php echo number_format($it['price'] * $it['qty'], 2); ?></span>
            </div>
          <?php endforeach; ?>
          <hr>
          <div class="d-flex justify-content-between fw-bold">
            <span>Total</span>
            <span>₹<?php echo number_format($total, 2); ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>