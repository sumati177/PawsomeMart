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

  <?php if (empty($user['address']) || empty($user['phone'])): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-warning text-dark fw-bold">⚠️ Complete Your Profile</div>
      <div class="card-body">
        <p class="small text-muted mb-3">Please provide your delivery details to continue with the order.</p>
        <form method="post" action="index.php?page=checkout">
          <input type="hidden" name="act" value="profile_update_lite">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Mobile Number (10 Digits)</label>
              <input type="text" name="phone" class="form-control" placeholder="e.g. 9876543210" value="<?php echo htmlspecialchars($user['phone']); ?>" required pattern="[0-9]{10}">
            </div>
            <div class="col-md-12">
              <label class="form-label small fw-bold">Delivery Address</label>
              <textarea name="address" class="form-control" rows="2" placeholder="Enter your full address..." required><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>
            <div class="col-12">
              <button class="btn btn-pet">💾 Save & Continue</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php else: ?>

  <div class="row g-3">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header card-header-pet fw-bold">💳 Payment Method</div>
        <div class="card-body">
          <form id="checkoutForm">
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="payment" id="cod" value="COD" checked>
              <label class="form-check-label" for="cod">💵 Cash on Delivery</label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="radio" name="payment" id="upi" value="UPI">
              <label class="form-check-label" for="upi">📱 UPI (Demo)</label>
            </div>
            <button type="submit" id="placeOrderBtn" class="btn btn-pet w-100 mt-2">✅ Place Order</button>
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

<?php if (!empty($user['address']) && !empty($user['phone'])): ?>
<script type="module">
  import { orderService } from './js/orderService.js';
  
  const checkoutForm = document.getElementById('checkoutForm');
  const placeOrderBtn = document.getElementById('placeOrderBtn');
  
  if (checkoutForm) {
      checkoutForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          placeOrderBtn.disabled = true;
          placeOrderBtn.innerHTML = '⏳ Processing Transaction...';
          
          const userId = <?php echo json_encode($user['id']); ?>;
          const userEmail = <?php echo json_encode($user['username'] ?? ''); ?>;
          const address = <?php echo json_encode($user['address']); ?>;
          const phone = <?php echo json_encode($user['phone']); ?>;
          const totalAmount = <?php echo $total; ?>;
          
          if (!/^[0-9]{10}$/.test(phone)) {
              alert('Invalid phone number. Must be 10 digits.');
              placeOrderBtn.disabled = false;
              placeOrderBtn.innerHTML = '✅ Place Order';
              return;
          }

          const items = <?php 
            $jsItems = array_map(function($it) {
                return [
                    'productId' => $it['id'],
                    'name' => $it['name'],
                    'price' => (float)$it['price'],
                    'quantity' => (int)$it['qty'],
                    'image' => $it['image_url'] ?? ''
                ];
            }, $_SESSION['cart']);
            echo json_encode(array_values($jsItems));
          ?>;

          const res = await orderService.placeOrder(userId, userEmail, items, totalAmount, address, phone);
          
          if(res.success) {
              window.location.href = 'index.php?page=checkout&action=clear_cart_after_order';
          } else {
              alert('Checkout Failed: ' + res.error);
              placeOrderBtn.disabled = false;
              placeOrderBtn.innerHTML = '✅ Place Order';
          }
      });
  }
</script>
<?php endif; ?>