<div class="container py-5">
  <!-- Hero Section -->
  <div class="row align-items-center mb-5 g-4">
    <div class="col-lg-6">
      <h1 class="display-3 fw-bold mb-3" style="letter-spacing:-1px; color:var(--pc-text)">
        Premium Care for <br/>
        <span style="color:var(--pc-primary)">Your Best Friends</span>
      </h1>
      <p class="lead text-muted mb-4 fs-4">
        Discover curated nutrition, interactive toys, and stylish accessories designed to make every tail wag and purr.
      </p>
      <div class="d-flex gap-3">
        <a href="index.php?page=products" class="btn btn-pet btn-lg shadow-lg">Shop Best Sellers</a>
        <a href="#categories" class="btn btn-outline-pet btn-lg">Explore Categories</a>
      </div>
    </div>
    <div class="col-lg-6 d-none d-lg-block">
        <div class="p-5 rounded-circle shadow-lg" style="background: hsla(var(--pc-hue), 50%, 85%, 0.4); backdrop-filter: blur(20px); border: 2px solid var(--pc-border)">
            <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&q=80&w=800" 
                 class="img-fluid rounded-circle shadow-2xl transition-transform duration-700 hover:scale-105" 
                 alt="Pets">
        </div>
    </div>
  </div>

  <!-- Categories Grid -->
  <div id="categories" class="row g-4 py-3">
    <div class="col-12 text-center mb-2">
      <h2 class="fw-bold h1">Shop by Category</h2>
      <p class="text-muted">High-quality essentials for every pet</p>
    </div>
    <?php 
    $catData = [
      'Food'        => '🥗 Nourish them with organic and premium nutrition.',
      'Toys'        => '🎾 Stimulate their mind with interactive play.',
      'Accessories' => '🧣 Style and comfort for your furry companions.'
    ];
    foreach($catData as $c => $desc): 
    ?>
    <div class="col-md-4">
      <div class="card h-100 border-0 shadow-sm hover-card">
        <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
            <div class="mb-4 d-inline-block p-3 rounded-circle" style="background: hsla(var(--pc-hue), 50%, 90%, 0.5); width: fit-content; margin: 0 auto;">
                <span class="fs-2"><?php echo ($c === 'Food' ? '🍱' : ($c === 'Toys' ? '🧸' : '🎨')); ?></span>
            </div>
          <h3 class="fw-bold mb-3"><?php echo $c; ?></h3>
          <p class="text-muted small mb-4"><?php echo $desc; ?></p>
          <div class="mt-auto">
            <a class="btn btn-outline-pet w-100" href="index.php?page=products&cat=<?php echo urlencode($c); ?>">Browse Collections</a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
.hover-card:hover { transform: scale(1.02); }
.shadow-2xl { filter: drop-shadow(0 20px 30px rgba(0,0,0,0.15)); }
</style>