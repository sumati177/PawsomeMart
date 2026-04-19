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
  
  function setLoading(isLoading) {
      if (isLoading) {
          container.innerHTML = `
            <div class="text-center py-5">
              <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 text-muted">Synchronizing with central database...</p>
            </div>
          `;
      }
  }

  async function loadAdminOrders() {
      setLoading(true);
      try {
          const orders = await orderService.getAdminOrders();
          countSpan.textContent = orders.length + ' total';
          
          let debugHtml = `
            <div class="card border-0 bg-dark text-white mb-4 shadow-sm overflow-hidden">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold small">🛡️ CLUSTER DEBUG LOG</span>
                  <span class="badge bg-secondary font-monospace" style="font-size:0.75rem;">Nodes: ${orders.length}</span>
                </div>
                <pre class="small text-info mb-0" style="max-height:120px; overflow:auto; background:rgba(0,0,0,0.5); padding:10px; border-radius:6px; font-family: 'Consolas', monospace;">${JSON.stringify(orders, null, 2)}</pre>
              </div>
            </div>
          `;

          if (orders.length === 0) {
              container.innerHTML = debugHtml + '<div class="alert alert-warning border-0 shadow-sm">No orders recorded in the system logs.</div>';
              return;
          }
          
          let tableHtml = `
      <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-dark text-white">
            <tr>
              <th class="ps-3" style="min-width:140px;">Order ID/Date</th>
              <th>Customer</th>
              <th style="min-width:120px;">Status</th>
              <th>Total</th>
              <th>Address</th>
              <th>Items</th>
              <th class="pe-3" style="min-width:160px;">Update Status</th>
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
          
          const totalAmount = parseFloat(o.totalAmount || o.total || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
          const address = o.address || 'N/A';
          const phone = o.phone || '';
          
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
          
          let dateStr = 'N/A';
          if (o.createdAt && o.createdAt.toDate) {
              dateStr = o.createdAt.toDate().toLocaleString();
          } else if (typeof o.createdAt === 'string') {
              dateStr = new Date(o.createdAt).toLocaleString();
          } else if (o.createdAt && o.createdAt.seconds) {
              dateStr = new Date(o.createdAt.seconds * 1000).toLocaleString();
          }
          
          let optionsHtml = '';
          const statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
          statuses.forEach(s => {
              optionsHtml += `<option value="${s}" ${status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`;
          });

          tableHtml += `
            <tr style="border-left: 4px solid ${badgeClass.includes('primary') ? '#0d6efd' : (badgeClass.includes('success') ? '#198754' : '#6c757d')}">
              <td class="ps-3">
                <code class="text-primary d-block small mb-1">${id.substring(0,10)}</code>
                <small class="text-muted" style="font-size:0.7rem">${dateStr}</small>
              </td>
              <td>
                <div class="fw-bold small">${userEmail}</div>
                <small class="text-muted">${phone}</small>
              </td>
              <td><span class="badge rounded-pill ${badgeClass}" style="padding:0.5em 1em; font-weight:600;">${status.toUpperCase()}</span></td>
              <td class="fw-bold">₹${totalAmount}</td>
              <td class="small text-muted" style="max-width:180px;">${address}</td>
              <td>${itemsHtml}</td>
              <td class="pe-3">
                <select class="form-select form-select-sm status-select border-0 bg-light" data-id="${id}">
                  ${optionsHtml}
                </select>
              </td>
            </tr>
          `;
      });
      
      tableHtml += `</tbody></table></div>`;
      container.innerHTML = debugHtml + tableHtml;
      
      // Bind status events
      document.querySelectorAll('.status-select').forEach(select => {
          select.addEventListener('change', async (e) => {
              const orderId = e.target.getAttribute('data-id');
              const newStatus = e.target.value;
              e.target.disabled = true;
              const res = await orderService.updateOrderStatus(orderId, newStatus);
              e.target.disabled = false;
              if (!res.success) {
                  alert("Failed to update status: " + res.error);
                  e.target.value = e.target.getAttribute('data-original'); // revert
              } else {
                  e.target.setAttribute('data-original', newStatus);
                  // Optionally reload to update badge/border
                  loadAdminOrders();
              }
          });
          select.setAttribute('data-original', select.value);
      });
      } catch(e) {
          console.error("Admin render failed:", e);
          container.innerHTML = `<div class="alert alert-danger border-0 shadow-sm"><strong>Terminal Error:</strong> ${e.message}</div>`;
      } finally {
          // setLoading(false) equivalent
      }
  }
  
  loadAdminOrders();
</script>