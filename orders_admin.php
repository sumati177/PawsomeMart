<?php
// Ensure admin is authenticated
if (!is_admin()) {
    app_redirect('index.php?page=admin_login');
}
?>
<div class="container mt-4">
  <h4 class="mb-3 fw-bold">🛒 Manage Orders <span id="ordersCount" class="badge bg-secondary ms-2" style="font-size:0.8rem;">0 total</span></h4>
  <div id="ordersContainer">
      <div class="alert alert-info">Loading orders...</div>
  </div>
</div>

<script type="module">
  import { orderService } from './js/orderService.js';
  
  const container = document.getElementById('ordersContainer');
  const countSpan = document.getElementById('ordersCount');
  
  orderService.subscribeToAdminOrders((orders) => {
      countSpan.textContent = orders.length + ' total';
      
      if (orders.length === 0) {
          container.innerHTML = '<div class="alert alert-info">No orders found in the database.</div>';
          return;
      }
      
      let html = `
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-dark">
            <tr>
              <th style="min-width:120px;">Order ID/Date</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Total</th>
              <th>Address</th>
              <th>Items</th>
              <th style="min-width:150px;">Update Status</th>
            </tr>
          </thead>
          <tbody>
      `;
      
      orders.forEach(o => {
          const id = o.id || '';
          const userEmail = o.userEmail || o.userId || 'Unknown';
          const status = (o.status || 'pending').toLowerCase();
          
          let badgeClass = 'bg-secondary';
          if (status === 'delivered') badgeClass = 'bg-success';
          else if (status === 'processing' || status === 'confirmed') badgeClass = 'bg-warning text-dark';
          else if (status === 'shipped') badgeClass = 'bg-info text-dark';
          else if (status === 'cancelled') badgeClass = 'bg-danger';
          else if (status === 'pending') badgeClass = 'bg-primary';
          
          const total = parseFloat(o.totalAmount || o.total || 0).toFixed(2);
          const address = o.address || 'N/A';
          const phone = o.phone || '';
          
          let itemsHtml = '';
          if (o.items && Array.isArray(o.items)) {
              o.items.forEach(item => {
                  const name = item.name || item.productName || 'Item';
                  const qty = parseInt(item.quantity || item.qty || 1);
                  itemsHtml += `<div class="small">${name} &times; ${qty}</div>`;
              });
          } else {
              itemsHtml = '<small class="text-muted">—</small>';
          }
          
          let dateStr = o.createdAt || '';
          if (o.createdAt && o.createdAt.toDate) {
              dateStr = o.createdAt.toDate().toLocaleString();
          } else if (typeof o.createdAt === 'string') {
              dateStr = new Date(o.createdAt).toLocaleString();
          }
          
          let optionsHtml = '';
          const statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
          statuses.forEach(s => {
              optionsHtml += `<option value="${s}" ${status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`;
          });

          html += `
            <tr>
              <td>
                <small class="text-muted d-block">${id.substring(0,10)}...</small>
                <small class="text-muted" style="font-size:0.7rem">${dateStr}</small>
              </td>
              <td>${userEmail}<br><small class="text-muted">${phone}</small></td>
              <td><span class="badge ${badgeClass}">${status}</span></td>
              <td>₹${total}</td>
              <td class="small" style="max-width:180px;">${address}</td>
              <td>${itemsHtml}</td>
              <td>
                <select class="form-select form-select-sm status-select" data-id="${id}">
                  ${optionsHtml}
                </select>
              </td>
            </tr>
          `;
      });
      
      html += `</tbody></table></div>`;
      container.innerHTML = html;
      
      // Bind status events
      document.querySelectorAll('.status-select').forEach(select => {
          select.addEventListener('change', async (e) => {
              const orderId = e.target.getAttribute('data-id');
              const newStatus = e.target.value;
              const res = await orderService.updateOrderStatus(orderId, newStatus);
              if (!res.success) {
                  alert("Failed to update status: " + res.error);
                  e.target.value = e.target.getAttribute('data-original'); // revert
              } else {
                  e.target.setAttribute('data-original', newStatus);
              }
          });
          select.setAttribute('data-original', select.value);
      });
  });
</script>