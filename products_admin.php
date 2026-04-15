<?php
require_once('config.php');

// --- ACCESS CONTROL ---
if (!is_admin()) {
    app_redirect('index.php?page=admin_login');
}

// --- SERVER-SIDE OPERATIONS ---

$act = $_POST['act'] ?? $_GET['act'] ?? '';

// 1. Save Product (Add or Update)
if ($act === 'admin_product_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_POST['id'] ?? '';
    $is_new = ($id === '' || $id === '0');
    $now    = date('Y-m-d\TH:i:s\Z');

    $productData = [
        'name'        => trim($_POST['name'] ?? ''),
        'categoryId'  => trim($_POST['category'] ?? ''),
        'price'       => (float)($_POST['price'] ?? 0),
        'stock'       => (int)($_POST['stock'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'updatedAt'   => $now
    ];

    $existingImages = [];
    if (!$is_new) {
        $existing = firestore_get('products', $id);
        if ($existing && isset($existing['imageUrls']) && is_array($existing['imageUrls'])) {
            $existingImages = $existing['imageUrls'];
        } elseif ($existing && isset($existing['images']) && is_array($existing['images'])) {
            $existingImages = $existing['images'];
        }
        if ($existing && isset($existing['createdAt'])) {
            $productData['createdAt'] = $existing['createdAt'];
        } elseif ($existing && isset($existing['created_at'])) {
            $productData['createdAt'] = $existing['created_at'];
        }
    } else {
        $productData['createdAt'] = floor(microtime(true) * 1000); // 13 digit
    }

    // --- Handling Multi-Image Cloudinary Upload ---
    if (!empty($_FILES['images']['name'][0])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $tmp  = $_FILES['images']['tmp_name'][$i];
            $type = $_FILES['images']['type'][$i];
            $size = $_FILES['images']['size'][$i];
            $name = $_FILES['images']['name'][$i];

            if (!is_uploaded_file($tmp)) continue;

            if (!in_array($type, $allowed)) {
                $_SESSION['flash_err'] = "Invalid type for $name. Only JPG, PNG, WEBP allowed.";
                continue;
            }
            if ($size > $maxSize) {
                $_SESSION['flash_err'] = "File $name exceeds 2MB limit.";
                continue;
            }

            // Use the Cloudinary upload function from config.php
            $upload_result = cloudinary_upload($tmp);
            if ($upload_result['success']) {
                $existingImages[] = $upload_result['url'];
            } else {
                $_SESSION['flash_err'] = "Upload failed: " . $upload_result['message'];
            }
        }
    }

    $productData['imageUrls'] = $existingImages;

    if ($is_new) {
        $add_result = firestore_add('products', $productData);
        if ($add_result['success']) {
            $_SESSION['flash_msg'] = 'Product created successfully.';
        } else {
            $_SESSION['flash_err'] = 'Failed to create product: ' . $add_result['message'];
        }
    } else {
        $update_result = firestore_update('products', $id, $productData);
        if ($update_result['success']) {
            $_SESSION['flash_msg'] = 'Product updated successfully.';
        } else {
            $_SESSION['flash_err'] = 'Failed to update product: ' . $update_result['message'];
        }
    }

    header('Location: index.php?page=products_admin');
    exit;
}

// 2. Delete Single Image Reference
if ($act === 'delete_image' && isset($_GET['pid']) && isset($_GET['img_idx'])) {
    $pid     = $_GET['pid'];
    $idx     = (int)$_GET['img_idx'];
    $product = firestore_get('products', $pid);
    
    if ($product) {
        $imgs = $product['imageUrls'] ?? ($product['images'] ?? []);
        if (is_array($imgs)) {
            array_splice($imgs, $idx, 1);
            firestore_update('products', $pid, [
                'imageUrls' => $imgs,
                'updatedAt' => date('Y-m-d\TH:i:s\Z')
            ]);
            $_SESSION['flash_msg'] = 'Image reference removed.';
        }
    }
    header('Location: index.php?page=products_admin');
    exit;
}

// 3. Delete Entire Product
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    if ($id !== '') {
        firestore_delete('products', $id);
        $_SESSION['flash_msg'] = 'Product deleted successfully.';
    }
    header('Location: index.php?page=products_admin');
    exit;
}

// --- FETCH DATA FOR UI ---
$raw = firestore_get_all('products');
$prods = [];
if (is_array($raw)) {
    foreach ($raw as $key => $p) {
        $p['id'] = $key;
        $p['imageUrls'] = $p['imageUrls'] ?? ($p['images'] ?? []);
        $p['categoryId'] = $p['categoryId'] ?? ($p['category'] ?? '');
        $prods[] = $p;
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">Product Admin Panel</h2>
        <button class="btn btn-primary" onclick="resetForm()">+ Add Product</button>
    </div>

    <?php if ($flash_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_msg); ?></div><?php endif; ?>
    <?php if ($flash_err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($flash_err); ?></div><?php endif; ?>

    <!-- Form Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white font-weight-bold py-3">Add / Edit Product Details</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="act" value="admin_product_save">
                <input type="hidden" name="id" id="pid" value="0">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="pname" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="pcat" class="form-control">
                            <option value="Food">Food</option>
                            <option value="Toys">Toys</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" step="0.01" name="price" id="pprice" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" id="pstock" class="form-control" required>
                    </div>
                    <div class="col-md-9 mb-3">
                        <label class="form-label">Upload Images (JPG, PNG, WEBP — Max 2MB)</label>
                        <input type="file" name="images[]" id="pimages" class="form-control" multiple accept="image/*">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="pdesc" class="form-control" rows="2"></textarea>
                    </div>
                    <div id="preview-box" class="col-12 d-flex gap-2 flex-wrap mb-4"></div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success px-4">Save to Firebase</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Product Info</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Photos</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prods)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No products found in database.</td></tr>
                    <?php else: foreach ($prods as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($p['categoryId']); ?></small>
                            </td>
                            <td>₹<?php echo number_format($p['price'], 2); ?></td>
                            <td><?php echo (int)$p['stock']; ?></td>
                            <td>
                                <?php if($p['stock'] > 0): ?>
                                    <span class="badge bg-success">In Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php foreach ($p['imageUrls'] as $idx => $url): ?>
                                        <div class="position-relative">
                                            <img src="<?php echo $url; ?>" class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                            <a href="?page=products_admin&act=delete_image&pid=<?php echo $p['id']; ?>&img_idx=<?php echo $idx; ?>" 
                                               class="position-absolute translate-middle badge rounded-pill bg-danger" 
                                               style="top:0; left:100%; border: 1px solid white;"
                                               onclick="return confirm('Remove this image reference?')">&times;</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" onclick='editP(<?php echo json_encode($p); ?>)'>Edit</button>
                                <a href="?page=products_admin&del=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete entire product node?')">Del</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editP(p) {
    document.getElementById('pid').value = p.id;
    document.getElementById('pname').value = p.name;
    document.getElementById('pcat').value = p.categoryId || p.category || 'Food';
    document.getElementById('pprice').value = p.price;
    document.getElementById('pstock').value = p.stock;
    document.getElementById('pdesc').value = p.description || '';
    
    const pb = document.getElementById('preview-box'); pb.innerHTML = '';
    const pImgs = p.imageUrls || p.images || false;
    if (pImgs) {
        pImgs.forEach(url => {
            const img = document.createElement('img');
            img.src = url; img.className = 'img-thumbnail';
            img.style = 'width: 70px; height: 70px; object-fit: cover;';
            pb.appendChild(img);
        });
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('pid').value = 0;
    document.querySelector('form').reset();
    document.getElementById('preview-box').innerHTML = '';
}

document.getElementById('pimages').addEventListener('change', function(e) {
    const pb = document.getElementById('preview-box'); pb.innerHTML = '';
    [...e.target.files].forEach(file => {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const img = document.createElement('img');
            img.src = ev.target.result; img.className = 'img-thumbnail';
            img.style = 'width: 70px; height: 70px; object-fit: cover;';
            pb.appendChild(img);
        }
        reader.readAsDataURL(file);
    });
});
</script>

<style>
    .gap-1 { gap: 0.25rem; }
    .gap-2 { gap: 0.5rem; }
    .img-thumbnail { border-radius: 6px; }
    .badge { font-weight: 500; font-size: 0.75rem; }
</style>
