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
      if (isLoading) {
          // Do nothing explicitly except maintaining loading state semantic variable for user testing verification
      }
  }

  async function loadOrders() {
      setLoading(true);
      container.innerHTML = '<div class="alert alert-info">Loading your orders...</div>';
      
      try {
          const orders = await orderService.getUserOrders(uid);
          
          let baseHtml = `<div style="background:#eee;padding:10px;margin-bottom:10px;border-radius:4px;"><p class="fw-bold m-0 text-dark">Total Orders: ${orders.length}</p><pre class="text-dark small m-0" style="max-height:200px;overflow:auto;">${JSON.stringify(orders, null, 2)}</pre></div>`;

          if (orders.length === 0) {
              container.innerHTML = baseHtml + '<div class="alert alert-info">You have not placed any orders yet. <a href="index.php?page=products" class="alert-link">Browse products</a>.</div>';
              return;
          }
          
          let tableHtml = `
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
              } else if (o.createdAt && o.createdAt.seconds) {
                  dateStr = new Date(o.createdAt.seconds * 1000).toLocaleString();
              }
              
              tableHtml += `
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
          
          tableHtml += `</tbody></table></div>`;
          container.innerHTML = baseHtml + tableHtml;
      } catch (e) {
          console.error(e);
          container.innerHTML = `<div class="alert alert-danger">Error loading orders. ${e.message}</div>`;
      } finally {
          setLoading(false);
      }
  }
  
  loadOrders();
</script>
