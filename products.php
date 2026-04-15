<?php
$cat = $_GET['cat'] ?? 'All';
$allowedCats = ['Food','Toys','Accessories'];
$cat = ($cat === 'All' || in_array($cat, $allowedCats, true)) ? $cat : 'All';
$placeholder = 'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&q=80&w=400';

$prods = [];
if (isset($all_products) && is_array($all_products)) {
    $sorted_products = array_reverse($all_products, true);
    foreach ($sorted_products as $id => $p) {
        if (!is_array($p)) continue; // Skip corrupted or invalid nodes
        if ($cat !== 'All' && (!isset($p['category']) || $p['category'] !== $cat)) continue;
        $p['id'] = $id;
        $prods[] = $p;
    }
}
?>

<div class="container py-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 gap-3">
    <div>
        <h1 class="fw-bold mb-0">Browse <span style="color:var(--pc-primary)"><?php echo htmlspecialchars($cat); ?></span></h1>
        <p class="text-muted mb-0">Discover high-quality essentials for your companions.</p>
    </div>
    <div class="glass-pill p-1 shadow-sm d-flex gap-1" style="background:var(--pc-glass); border-radius:50px; border:1px solid var(--pc-border)">
      <a class="btn btn-sm rounded-pill px-4 <?php echo $cat==='All'?'btn-pet shadow-sm':'btn-link text-muted text-decoration-none'; ?>" href="index.php?page=products">All</a>
      <?php foreach($allowedCats as $c): ?>
        <a class="btn btn-sm rounded-pill px-4 <?php echo $cat===$c?'btn-pet shadow-sm':'btn-link text-muted text-decoration-none'; ?>" href="index.php?page=products&cat=<?php echo urlencode($c); ?>"><?php echo htmlspecialchars($c); ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="row g-4">
    <?php if(empty($prods)): ?>
        <div class="col-12 text-center py-5">
            <div class="p-5 rounded-4 bg-white shadow-sm border">
                <h3 class="text-muted">No products found in this category.</h3>
                <a href="index.php?page=products" class="btn btn-pet mt-3">View All Products</a>
            </div>
        </div>
    <?php else: foreach ($prods as $p): 
      $prodId = $p['id'];
      $firstImage = (!empty($p['images']) && is_array($p['images'])) ? $p['images'][0] : $placeholder;
      $imgs_all = [];
      if (!empty($p['images']) && is_array($p['images'])) {
        foreach ($p['images'] as $idx => $url) { $imgs_all[] = ['id' => $idx, 'path' => $url]; }
      }
    ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 border-0 shadow-soft product-card">
        <div class="position-relative overflow-hidden" style="border-radius: 12px 12px 0 0;">
            <img src="<?php echo htmlspecialchars($firstImage); ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($p['name'] ?? 'Product'); ?>">
            <div class="position-absolute top-0 end-0 m-3">
                <span class="badge rounded-pill blur-badge"><?php echo htmlspecialchars($p['category'] ?? 'Uncategorized'); ?></span>
            </div>
        </div>
        <div class="card-body p-4 d-flex flex-column">
          <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($p['name'] ?? 'Unnamed Product'); ?></h5>
          <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="text-primary fw-bold fs-4">₹<?php echo number_format((float)($p['price'] ?? 0), 2); ?></div>
              <div class="badge-stock <?php echo (isset($p['stock']) && $p['stock'] > 5) ? 'text-success' : 'text-danger'; ?>">
                ● Stock: <?php echo (int)($p['stock'] ?? 0); ?>
              </div>
          </div>
          
          <div class="mt-auto d-flex gap-2">
            <button class="btn btn-outline-pet w-100 quick-view-btn" 
                data-product='<?php echo json_encode(['id'=>$prodId,'name'=>($p['name']??''),'price'=>number_format((float)($p['price']??0),2),'stock'=>(int)($p['stock']??0),'description'=>($p['description']??''),'first_image'=>$firstImage,'images'=>$imgs_all], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG); ?>'>
                Details
            </button>
            <form method="post" action="index.php?page=cart" class="w-100">
                <input type="hidden" name="act" value="add_cart">
                <input type="hidden" name="id" value="<?php echo $prodId; ?>">
                <input type="hidden" name="qty" value="1">
                <button class="btn btn-pet w-100">Add</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="row g-0">
          <div class="col-md-6 bg-light d-flex align-items-center justify-content-center p-4">
            <img id="qv-image" src="" alt="Product" class="img-fluid rounded shadow-sm">
          </div>
          <div class="col-md-6 p-4 d-flex flex-column">
            <span id="qv-stock" class="badge w-auto mb-2" style="width:fit-content">Stock</span>
            <h2 id="qv-name" class="fw-bold mb-3">Name</h2>
            <h3 id="qv-price" class="text-primary mb-3">Price</h3>
            <p id="qv-desc" class="text-muted flex-grow-1">Desc</p>
            <form method="post" action="index.php?page=cart" class="mt-auto">
                <input type="hidden" name="act" value="add_cart">
                <input type="hidden" id="qv-id" name="id" value="">
                <div class="d-flex gap-2">
                    <input type="number" name="qty" class="form-control" value="1" min="1" style="width:80px">
                    <button class="btn btn-pet flex-grow-1">Add to Cart</button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qvBtns = document.querySelectorAll('.quick-view-btn');
    if (qvBtns.length > 0) {
        const qvModal = new bootstrap.Modal(document.getElementById('quickViewModal'));
        qvBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const p = JSON.parse(this.getAttribute('data-product'));
                document.getElementById('qv-image').src = p.first_image;
                document.getElementById('qv-name').textContent = p.name;
                document.getElementById('qv-price').textContent = '₹' + p.price;
                document.getElementById('qv-desc').textContent = p.description || 'No description available.';
                document.getElementById('qv-stock').textContent = 'Stock: ' + p.stock;
                document.getElementById('qv-stock').className = p.stock > 0 ? 'badge bg-success mb-2' : 'badge bg-danger mb-2';
                document.getElementById('qv-id').value = p.id;
                qvModal.show();
            });
        });
    }
});
</script>

<style>
.shadow-soft { box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
.product-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(var(--pc-hue), 30%, 20%, 0.12); }
.blur-badge { background: rgba(255,255,255,0.7); backdrop-filter: blur(8px); color: var(--pc-text); border: 1px solid rgba(255,255,255,0.3); padding: 0.5rem 1rem; }
.badge-stock { font-size: 0.85rem; font-weight: 600; opacity: 0.8; }
</style>
