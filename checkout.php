<?php
if(!is_user()){ header('Location:index.php?page=login'); exit; }
if(empty($_SESSION['cart'])){ header('Location:index.php?page=cart'); exit; }
$user=$_SESSION['user'];
if(empty($user['address'])){ $_SESSION['flash_err']='Please add address before checkout'; header('Location:index.php?page=profile'); exit; }
$total=0; foreach($_SESSION['cart'] as $it) $total += $it['price']*$it['qty'];
?>
<div class="container">
  <h4>Checkout</h4>
  <div class="row g-3">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header card-header-pet">Payment</div>
        <div class="card-body">
          <form method="post" action="index.php?page=checkout&action=place">
            <input type="hidden" name="act" value="checkout">
            <div class="form-check"><input class="form-check-input" type="radio" name="payment" id="cod" value="COD" checked><label class="form-check-label" for="cod">Cash on Delivery (Demo)</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="payment" id="upi" value="UPI"><label class="form-check-label" for="upi">UPI (Demo)</label></div>
            <button class="btn btn-pet mt-3">Place Order</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-header card-header-pet">Address</div>
        <div class="card-body">
          <div><?php echo nl2br(htmlspecialchars($user['address'])); ?></div>
          <div class="small small-muted mt-2">Phone: <?php echo htmlspecialchars($user['phone']??''); ?></div>
          <a href="index.php?page=profile" class="btn btn-outline-pet btn-sm mt-2">Edit Address</a>
        </div>
      </div>
      <div class="card shadow-sm mt-3">
        <div class="card-header card-header-pet">Summary</div>
        <div class="card-body"><div>Total: <strong>₹<?php echo number_format($total,2); ?></strong></div></div>
      </div>
    </div>
  </div>
</div>