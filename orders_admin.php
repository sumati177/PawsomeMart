<?php
// Ensure admin is authenticated (PHP session check)
if (!is_admin()) {
    app_redirect('index.php?page=admin_login');
}
?>
<div class="container mt-4">
  <h4 class="mb-3 fw-bold">🛒 Manage Orders <span id="ordersCount" class="badge bg-secondary ms-2" style="font-size:0.8rem;">0 total</span></h4>
  <div id="ordersContainer">
      <div class="text-center py-5">
        <div class="spinner-border text-success" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Authenticating & loading orders...</p>
      </div>
  </div>
</div>

<script type="module">
  import { orderService } from './js/orderService.js';
  import { ensureAuth } from './js/firebase-config.js';
  
  const container = document.getElementById('ordersContainer');
  const countSpan = document.getElementById('ordersCount');

  async function loadAdminOrders() {
      try {
          // Authenticate as admin in Firebase Auth
          const user = await ensureAuth();
          if (!user) {
              container.innerHTML = '<div class="alert alert-danger">Admin authentication failed. Please <a href="index.php?page=admin_login" class="alert-link">log in again</a>.</div>';
              return;
          }

          console.log("[AdminOrders] Authenticated as:", user.uid);
          const orders = await orderService.getAdminOrders();
          countSpan.textContent = orders.length + ' total';

          if (orders.length === 0) {
              container.innerHTML = '<div class="alert alert-info border-0 shadow-sm">No orders found in the system.</div>';
              return;
          }
          
          // Sort newest first
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
              <th class="ps-3" style="min-width:140px;">Order ID / Date</th>
              <th>Customer</th>
              <th style="min-width:110px;">Status</th>
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
          else if (status === 'pending' || status === 'placed') badgeClass = 'bg-primary';
          
          const totalAmt = parseFloat(o.totalAmount || o.total || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
          const address = o.address || 'N/A';
          const phone = o.phone || '';
          
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
          
          let dateStr = 'N/A';
          if (o.createdAt && o.createdAt.toDate) {
              dateStr = o.createdAt.toDate().toLocaleString();
          } else if (typeof o.createdAt === 'string') {
              dateStr = new Date(o.createdAt).toLocaleString();
          } else if (o.createdAt && o.createdAt.seconds) {
              dateStr = new Date(o.createdAt.seconds * 1000).toLocaleString();
          }
          
          let optionsHtml = '';
          const statuses = ['placed', 'pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
          statuses.forEach(s => {
              optionsHtml += `<option value="${s}" ${status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`;
          });

          html += `
            <tr>
              <td class="ps-3">
                <code class="text-primary d-block small mb-1">${id.substring(0,10)}</code>
                <small class="text-muted" style="font-size:0.7rem">${dateStr}</small>
              </td>
              <td>
                <div class="fw-bold small">${userEmail}</div>
                <small class="text-muted">${phone}</small>
              </td>
              <td><span class="badge rounded-pill ${badgeClass}" style="padding:0.5em 1em;">${status.toUpperCase()}</span></td>
              <td class="fw-bold">₹${totalAmt}</td>
              <td class="small text-muted" style="max-width:180px;">${address}</td>
              <td>${itemsHtml}</td>
              <td class="pe-3">
                <select class="form-select form-select-sm status-select border-0 bg-light" data-id="${id}" data-original="${status}">
                  ${optionsHtml}
                </select>
              </td>
            </tr>
          `;
      });
      
      html += `</tbody></table></div>`;
      container.innerHTML = html;
      
      // Bind status update events
      document.querySelectorAll('.status-select').forEach(select => {
          select.addEventListener('change', async (e) => {
              const orderId = e.target.getAttribute('data-id');
              const newStatus = e.target.value;
              const original = e.target.getAttribute('data-original');
              
              e.target.disabled = true;
              const res = await orderService.updateOrderStatus(orderId, newStatus);
              e.target.disabled = false;
              
              if (!res.success) {
                  alert("Failed to update status: " + res.error);
                  e.target.value = original;
              } else {
                  e.target.setAttribute('data-original', newStatus);
                  // Reload to update badges
                  loadAdminOrders();
              }
          });
      });

      } catch(e) {
          console.error("[AdminOrders] Error:", e);
          let msg = e.message || 'Unknown error';
          if (e.code === 'permission-denied') {
              msg = 'Permission denied by Firestore rules. Ensure your account has isAdmin: true in the users collection.';
          }
          container.innerHTML = `<div class="alert alert-danger border-0 shadow-sm"><strong>Error:</strong> ${msg}</div>`;
      }
  }
  
  loadAdminOrders();
</script>