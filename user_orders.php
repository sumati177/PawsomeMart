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
  
  function setLoading(isLoading) {
      const btn = document.querySelector('.btn-refresh-orders');
      if (btn) btn.disabled = isLoading;
      if (isLoading) {
          container.innerHTML = `
            <div class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 text-muted">Locating your orders...</p>
            </div>
          `;
      }
  }

  async function loadOrders() {
      setLoading(true);
      try {
          const orders = await orderService.getUserOrders(uid);
          
          let debugHtml = `
            <div class="card border-0 bg-light mb-4 shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold text-dark">🔍 System Debug View</span>
                  <span class="badge bg-primary">Total: ${orders.length}</span>
                </div>
                <pre class="small text-dark mb-0" style="max-height:150px; overflow:auto; background:rgba(0,0,0,0.03); padding:10px; border-radius:8px;">${JSON.stringify(orders, null, 2)}</pre>
              </div>
            </div>
          `;

          if (orders.length === 0) {
              container.innerHTML = debugHtml + '<div class="alert alert-info border-0 shadow-sm">You have not placed any orders yet. <a href="index.php?page=products" class="alert-link">Browse products</a>.</div>';
              return;
          }
          
          let tableHtml = `
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
              else if (status === 'pending') badgeClass = 'bg-primary';
              
              let itemsHtml = '';
              if (o.items && Array.isArray(o.items)) {
                  o.items.forEach(item => {
                      const name = item.name || item.productName || 'Item';
                      const qty = parseInt(item.quantity || item.qty || 1);
                      itemsHtml += `<div class="small fw-500">${name} <span class="text-muted">× ${qty}</span></div>`;
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
              
              tableHtml += `
                <tr>
                  <td class="ps-3"><code class="text-primary small">${(o.id || '').substring(0,8)}</code></td>
                  <td>${itemsHtml}</td>
                  <td class="fw-bold text-dark">₹${total}</td>
                  <td><span class="badge rounded-pill ${badgeClass}" style="font-weight:500; padding:0.5em 1em;">${status.toUpperCase()}</span></td>
                  <td class="small text-muted" style="max-width:150px;">${address}</td>
                  <td class="pe-3 small">${dateStr}</td>
                </tr>
              `;
          });
          
          tableHtml += `</tbody></table></div>`;
          container.innerHTML = debugHtml + tableHtml;
      } catch (e) {
          console.error(e);
          container.innerHTML = `<div class="alert alert-danger shadow-sm border-0"><strong>Error:</strong> Failed to retrieve orders. ${e.message}</div>`;
      } finally {
          // ensure setLoading(false) equivalent logic but we just replace innerHTML anyway
      }
  }
  
  loadOrders();
</script>
