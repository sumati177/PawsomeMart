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
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading your orders...</p>
      </div>
  </div>
</div>

<script type="module">
  import { orderService } from './js/orderService.js';
  import { ensureAuth } from './js/firebase-config.js';
  
  const container = document.getElementById('ordersContainer');

  async function loadOrders() {
      try {
          // Authenticate first — getUserOrders() uses auth.currentUser internally
          const user = await ensureAuth();
          if (!user) {
              container.innerHTML = '<div class="alert alert-warning">Authentication failed. Please <a href="index.php?page=login" class="alert-link">log in again</a>.</div>';
              return;
          }
          
          console.log("[MyOrders] Fetching orders for:", user.uid);
          const orders = await orderService.getUserOrders();
          console.log("[MyOrders] Received:", orders.length, "orders");

          if (orders.length === 0) {
              container.innerHTML = '<div class="alert alert-info border-0 shadow-sm">You have not placed any orders yet. <a href="index.php?page=products" class="alert-link">Browse products</a>.</div>';
              return;
          }
          
          // Sort by createdAt descending (newest first)
          orders.sort((a, b) => {
              const aTime = a.createdAt?.seconds || 0;
              const bTime = b.createdAt?.seconds || 0;
              return bTime - aTime;
          });

          let html = `
            <div class="table-responsive card border-0 shadow-sm">
              <table class="table table-hover align-middle mb-0">
                <thead class="bg-dark text-white">
                  <tr>
                    <th class="ps-3">Order ID</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Address</th>
                    <th class="pe-3">Date</th>
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
              else if (status === 'pending' || status === 'placed') badgeClass = 'bg-primary';
              
              let itemsHtml = '';
              if (o.items && Array.isArray(o.items)) {
                  o.items.forEach(item => {
                      const name = item.name || item.productName || 'Item';
                      const qty = parseInt(item.quantity || item.qty || 1);
                      itemsHtml += `<div class="small">${name} <span class="text-muted">× ${qty}</span></div>`;
                  });
              } else {
                  itemsHtml = '<small class="text-muted">—</small>';
              }
              
              const total = parseFloat(o.totalAmount || o.total || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
              const address = o.address || '';
              
              let dateStr = 'N/A';
              if (o.createdAt && o.createdAt.toDate) {
                  dateStr = o.createdAt.toDate().toLocaleString();
              } else if (typeof o.createdAt === 'string') {
                  dateStr = new Date(o.createdAt).toLocaleString();
              } else if (o.createdAt && o.createdAt.seconds) {
                  dateStr = new Date(o.createdAt.seconds * 1000).toLocaleString();
              }
              
              html += `
                <tr>
                  <td class="ps-3"><code class="text-primary small">${(o.id || '').substring(0,8)}</code></td>
                  <td>${itemsHtml}</td>
                  <td class="fw-bold">₹${total}</td>
                  <td><span class="badge rounded-pill ${badgeClass}" style="padding:0.5em 1em;">${status.toUpperCase()}</span></td>
                  <td class="small text-muted" style="max-width:150px;">${address}</td>
                  <td class="pe-3 small">${dateStr}</td>
                </tr>
              `;
          });
          
          html += `</tbody></table></div>`;
          container.innerHTML = html;
          
      } catch (e) {
          console.error("[MyOrders] Error:", e);
          let msg = e.message || 'Unknown error';
          if (e.code === 'permission-denied') {
              msg = 'Access denied. Please log out and log in again.';
          }
          container.innerHTML = `<div class="alert alert-danger border-0 shadow-sm"><strong>Error:</strong> ${msg}</div>`;
      }
  }
  
  loadOrders();
</script>
