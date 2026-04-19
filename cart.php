<?php
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// Refresh stock for all cart items to ensure accuracy
$total = 0;
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">🛒 Shopping Cart</h3>
    <span class="badge bg-light text-dark border"><?php echo count($_SESSION['cart']); ?> Items</span>
  </div>

  <?php if(empty($_SESSION['cart'])): ?>
    <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
        <div class="mb-3" style="font-size: 3rem;">🛍️</div>
        <h4 class="text-muted">Your cart is currently empty</h4>
        <p class="text-muted small">Looks like you haven't added anything to your cart yet.</p>
        <a class="btn btn-pet px-4 mt-2" href="index.php?page=products">Start Shopping</a>
    </div>
  <?php else: ?>
  
  <form id="cartForm" method="post" action="index.php?page=cart">
    <input type="hidden" name="act" value="cart_update">
    
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4 py-3">Product</th>
              <th class="py-3">Price</th>
              <th class="py-3" style="width: 150px;">Quantity</th>
              <th class="py-3 text-end pe-4">Subtotal</th>
              <th class="py-3"></th>
            </tr>
          </thead>
          <tbody>
            <?php 
            foreach($_SESSION['cart'] as $i => $it): 
              $pid = $it['id'];
              $stock = 999;
              if (isset($all_products[$pid])) {
                  $stock = (int)($all_products[$pid]['stock'] ?? 0);
              }
              
              // Ensure qty doesn't exceed stock
              $qty = min((int)$it['qty'], $stock);
              if ($qty < 1 && $stock > 0) $qty = 1;
              
              $sub = (float)$it['price'] * $qty; 
              $total += $sub; 
            ?>
            <tr class="cart-item-row" data-price="<?php echo (float)$it['price']; ?>">
              <td class="ps-4 py-3">
                <div class="d-flex align-items-center">
                  <?php if(!empty($it['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($it['image_url']); ?>" class="rounded border me-3" style="width: 50px; height: 50px; object-fit: cover;">
                  <?php endif; ?>
                  <div>
                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($it['name']); ?></div>
                    <small class="text-muted"><?php echo $stock; ?> in stock</small>
                  </div>
                </div>
              </td>
              <td class="py-3">₹<?php echo number_format($it['price'], 2); ?></td>
              <td class="py-3">
                <div class="input-group input-group-sm" style="width: 110px;">
                  <input type="number" 
                         class="form-control border-end-0 qty-input" 
                         name="qty[<?php echo $i; ?>]" 
                         value="<?php echo $qty; ?>" 
                         min="1" 
                         max="<?php echo $stock; ?>"
                         data-index="<?php echo $i; ?>">
                  <span class="input-group-text bg-white text-muted border-start-0 small" style="font-size: 0.7rem;">/<?php echo $stock; ?></span>
                </div>
                <?php if ((int)$it['qty'] > $stock): ?>
                  <div class="text-danger x-small mt-1" style="font-size: 0.65rem;">Stock adjusted</div>
                <?php endif; ?>
              </td>
              <td class="py-3 text-end pe-4 fw-bold">₹<span class="row-subtotal"><?php echo number_format($sub, 2); ?></span></td>
              <td class="py-3">
                <a class="btn btn-link text-danger p-0" href="index.php?page=cart&remove=<?php echo $i; ?>" onclick="return confirm('Remove this item?')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                  </svg>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="bg-light border-top">
            <tr>
              <td colspan="3" class="text-end py-4 fw-bold text-muted">Grand Total</td>
              <td class="text-end pe-4 py-4 fs-4 fw-bold text-primary">₹<span id="grand-total-display"><?php echo number_format($total, 2); ?></span></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3">
      <a href="index.php?page=products" class="btn btn-link text-decoration-none text-muted">
        ← Continue Shopping
      </a>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-outline-secondary px-4">🔄 Update Cart</button>
        <a href="index.php?page=checkout" class="btn btn-pet px-5 shadow-sm fw-bold <?php echo $total==0?'disabled':''; ?>">Proceed to Checkout →</a>
      </div>
    </div>
  </form>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const grandTotalDisplay = document.getElementById('grand-total-display');
    
    function updateTotals() {
        let total = 0;
        document.querySelectorAll('.cart-item-row').forEach(row => {
            const price = parseFloat(row.getAttribute('data-price'));
            const qtyInput = row.querySelector('.qty-input');
            const subDisplay = row.querySelector('.row-subtotal');
            
            const qty = parseInt(qtyInput.value) || 0;
            const sub = price * qty;
            
            subDisplay.textContent = sub.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            total += sub;
        });
        grandTotalDisplay.textContent = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    qtyInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Respect max stock
            const max = parseInt(this.max);
            if (parseInt(this.value) > max) this.value = max;
            if (parseInt(this.value) < 1) this.value = 1;
            
            updateTotals();
        });
    });
});
</script>

<style>
.cart-item-row:hover { background: rgba(var(--pc-hue), 30%, 20%, 0.02); }
.qty-input { text-align: center; font-weight: 500; }
.btn-pet.disabled { opacity: 0.5; pointer-events: none; }
</style>