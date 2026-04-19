<?php
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$total=0;
?>
<div class="container">
  <h4 class="mb-3">Shopping Cart</h4>
  <?php if(empty($_SESSION['cart'])): ?>
    <div class="alert alert-info">Your cart is empty. <a class="alert-link" href="index.php?page=products">Browse products</a>.</div>
  <?php else: ?>
  <form method="post" action="index.php?page=cart&action=update">
    <input type="hidden" name="act" value="cart_update">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>#</th><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
          <?php 
          foreach($_SESSION['cart'] as $i=>$it): 
            $sub = $it['price'] * $it['qty']; 
            $total += $sub; 
            
            $pid = $it['id'];
            $stock = 999;
            if (isset($all_products[$pid])) {
                $stock = (int)($all_products[$pid]['stock'] ?? 0);
            }
          ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td><?php echo htmlspecialchars($it['name']); ?></td>
            <td>₹<?php echo number_format($it['price'],2); ?></td>
            <td style="max-width:120px">
              <input type="number" class="form-control" name="qty[]" value="<?php echo min((int)$it['qty'], $stock); ?>" min="1" max="<?php echo $stock; ?>">
              <?php if ((int)$it['qty'] >= $stock): ?>
                <div class="text-danger mt-1" style="font-size:0.75rem;">Only <?php echo $stock; ?> items left</div>
              <?php endif; ?>
            </td>
            <td>₹<?php echo number_format($sub,2); ?></td>
            <td>
              <a class="btn btn-sm btn-outline-danger" href="index.php?page=cart&remove=<?php echo $i; ?>" onclick="return confirm('Remove this item?')">Remove</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-between">
      <a href="index.php?page=products" class="btn btn-outline-pet">Continue Shopping</a>
      <div>
        <button class="btn btn-outline-secondary">Update Cart</button>
        <a href="index.php?page=checkout" class="btn btn-pet <?php echo $total==0?'disabled':''; ?>">Checkout</a>
      </div>
    </div>
  </form>
  <?php endif; ?>
</div>