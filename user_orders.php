<?php
if (!is_user()) {
    $_SESSION['flash_err'] = 'Please login to view your orders.';
    app_redirect('index.php?page=login');
}
?>

<div class="container mt-4">
  <h3 class="mb-3 fw-bold">📦 My Orders</h3>

  <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success text-center">✅ Your order has been placed successfully!</div>
  <?php endif; ?>

  <div id="ordersContainer">
      <div class="alert alert-info">Loading your orders...</div>
  </div>
</div>

<script type="module">
  import { orderService } from './js/orderService.js';
  
  const uid = <?php echo json_encode($_SESSION['user']['id']); ?>;
  const container = document.getElementById('ordersContainer');
  
  async function loadOrders() {
      try {
          const orders = await orderService.getUserOrders(uid);
          
          if (orders.length === 0) {
              container.innerHTML = '<div class="alert alert-info">You have not placed any orders yet. <a href="index.php?page=products" class="alert-link">Browse products</a>.</div>';
              return;
          }
          
          let html = `
            <div class="table-responsive">
              <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                  <tr>
                    <th>Order ID</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Address</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
          `;
          
          orders.forEach(o => {
              const status = (o.status || 'pending').toLowerCase();
              let badgeClass = 'bg-secondary';
              if (status === 'delivered') badgeClass = 'bg-success';
              else if (status === 'processing' || status === 'confirmed') badgeClass = 'bg-warning text-dark';
              else if (status === 'shipped') badgeClass = 'bg-info text-dark';
              else if (status === 'cancelled') badgeClass = 'bg-danger';
              else if (status === 'pending') badgeClass = 'bg-primary';
              
              let itemsHtml = '';
              if (o.items && Array.isArray(o.items)) {
                  o.items.forEach(item => {
                      const name = item.name || item.productName || 'Item';
                      const qty = parseInt(item.quantity || item.qty || 1);
                      itemsHtml += `<div class="small">${name} &times; ${qty}</div>`;
                  });
              } else {
                  itemsHtml = '<small>—</small>';
              }
              
              const total = parseFloat(o.totalAmount || o.total || 0).toFixed(2);
              const address = o.address || '';
              
              let dateStr = o.createdAt || '';
              if (o.createdAt && o.createdAt.toDate) {
                  dateStr = o.createdAt.toDate().toLocaleString();
              } else if (typeof o.createdAt === 'string') {
                  dateStr = new Date(o.createdAt).toLocaleString();
              }
              
              html += `
                <tr>
                  <td><small class="text-muted">${(o.id || '').substring(0,10)}...</small></td>
                  <td>${itemsHtml}</td>
                  <td>₹${total}</td>
                  <td><span class="badge ${badgeClass}">${status}</span></td>
                  <td>${address}</td>
                  <td>${dateStr}</td>
                </tr>
              `;
          });
          
          html += `</tbody></table></div>`;
          container.innerHTML = html;
          
      } catch (err) {
          console.error(err);
          // Fallback if index is missing:
          container.innerHTML = `<div class="alert alert-danger">Error loading orders. (Database index might be building, check console). ${err.message}</div>`;
      }
  }
  
  loadOrders();
</script>
