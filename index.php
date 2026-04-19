<?php
ob_start();
require_once __DIR__ . '/config.php';

if (!function_exists('firestore_get_all')) {
    die("<h3>Vercel Cache Error</h3><p>Your web server loaded an old cached copy of the config file. Please wait a few more seconds for Vercel to finish building the latest commit and refresh this page!</p>");
}

// --- LOGOUT ACTIONS ---
if (isset($_GET['action']) && $_GET['action'] === 'logout_user') { 
    unset($_SESSION['user']); 
    $_SESSION['cart'] = []; 
    app_redirect('index.php'); 
}
if (isset($_GET['action']) && $_GET['action'] === 'logout_admin') { unset($_SESSION['admin']); app_redirect('index.php'); }

// --- CART REMOVAL ---
if (isset($_GET['page']) && $_GET['page'] === 'cart' && isset($_GET['remove'])) {
    $i = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$i])) {
        array_splice($_SESSION['cart'], $i, 1);
        if (is_logged_in()) {
            firestore_update('users', $_SESSION['user']['id'], ['cart' => $_SESSION['cart']]);
        }
    }
    app_redirect('index.php?page=cart');
}

// --- POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    // Profile Update
    if ($act === 'profile_update' || $act === 'profile_update_lite') {
        if (!is_logged_in()) { app_redirect('index.php?page=login'); }
        $uid     = $_SESSION['user']['id'];
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validation for mobile no to be 10
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            $_SESSION['flash_err'] = 'Mobile number must be exactly 10 digits.';
            app_redirect('index.php?page=' . ($act === 'profile_update' ? 'profile' : 'checkout'));
        }

        firestore_update('users', $uid, [
            'phone'      => $phone,
            'address'    => $address,
            'updated_at' => date('Y-m-d\TH:i:s\Z')
        ]);
        $_SESSION['user']['phone']   = $phone;
        $_SESSION['user']['address'] = $address;
        $_SESSION['flash_msg'] = 'Profile details updated!';
        app_redirect('index.php?page=' . ($act === 'profile_update' ? 'profile' : 'checkout'));
    }

    // Register User (Firebase Auth REST)
    if ($act === 'register') {
        $email = trim($_POST['username'] ?? ''); // Assuming form uses 'username' field for email
        $pass  = $_POST['password'] ?? '';
        
        $res = firebase_auth_request('signUp', [
            'email' => $email,
            'password' => $pass,
            'returnSecureToken' => true
        ]);
        
        if (isset($res['error'])) {
            $_SESSION['flash_err'] = 'Registration failed: ' . $res['message'];
            app_redirect('index.php?page=register');
        } else {
            // Create user profile in Firestore
            $profile_result = firestore_update('users', $res['localId'], [
                'email'      => $email,
                'isAdmin'    => false,
                'name'       => explode('@', $email)[0],
                'createdAt'  => ['timestamp_utc' => date('Y-m-d\TH:i:s\Z')],
                'phone'      => '',
                'address'    => ''
            ]);
            
            $_SESSION['flash_msg'] = 'Registered successfully! You can now login.';
            app_redirect('index.php?page=login');
        }
    }

    // Login User (Firebase Auth REST)
    if ($act === 'login') {
        $email = trim($_POST['username'] ?? '');
        $pass  = $_POST['password'] ?? '';
        
        $res = firebase_auth_request('signInWithPassword', [
            'email' => $email,
            'password' => $pass,
            'returnSecureToken' => true
        ]);
        
        if (isset($res['error'])) {
            $_SESSION['flash_err'] = 'Login failed: ' . $res['message'];
            app_redirect('index.php?page=login');
        } else {
            // Fetch extra profile data from Firestore
            $profile = firestore_get('users', $res['localId']);
            $_SESSION['user'] = [
                'id'       => $res['localId'],
                'username' => $res['email'],
                'phone'    => $profile['phone'] ?? '',
                'address'  => $profile['address'] ?? ''
            ];
            
            // Sync cart with Firestore
            $db_cart = $profile['cart'] ?? [];
            if (!empty($_SESSION['cart'])) {
                // If local cart has items, overwrite the DB cart
                firestore_update('users', $res['localId'], ['cart' => $_SESSION['cart']]);
            } else if (!empty($db_cart)) {
                // If local cart is empty but DB has cart, use DB cart
                $_SESSION['cart'] = $db_cart;
            }
            
            app_redirect('index.php');
        }
    }

    // Admin Login (Firebase Auth REST + Role check)
    if ($act === 'admin_login') {
        $email = trim($_POST['username'] ?? '');
        $pass  = $_POST['password'] ?? '';
        
        $res = firebase_auth_request('signInWithPassword', [
            'email' => $email,
            'password' => $pass,
            'returnSecureToken' => true
        ]);
        
        if (isset($res['error'])) {
            $_SESSION['flash_err'] = 'Admin Login failed: ' . $res['message'];
            app_redirect('index.php?page=admin_login');
        } else {
            // Verify if user info exists and role is admin in Firestore
            $profile = firestore_get('users', $res['localId']);
            if (is_array($profile) && isset($profile['isAdmin']) && $profile['isAdmin'] === true) {
                $_SESSION['admin'] = [
                    'id'       => $res['localId'],
                    'username' => $res['email']
                ];
                app_redirect('index.php?page=index_admin');
            } else {
                $_SESSION['flash_err'] = 'Unauthorized: Admin privileges required.';
                app_redirect('index.php?page=admin_login');
            }
        }
    }

    // Add to Cart
    if ($act === 'add_cart') {
        $id  = $_POST['id'] ?? '';
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $p   = firestore_get('products', $id);
        if ($p && !isset($p['error'])) {
            $avail = (int)($p['stock'] ?? 999);
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            $found = false;
            foreach ($_SESSION['cart'] as &$it) {
                if ($it['id'] === $id) {
                    $new_qty = min($it['qty'] + $qty, $avail);
                    if ($new_qty <= 0) {
                        $_SESSION['flash_err'] = 'This item is out of stock.';
                        app_redirect('index.php?page=cart');
                    }
                    $it['qty'] = $new_qty;
                    $found = true;
                    break;
                }
            }
            unset($it);
            if (!$found) {
                if ($avail < 1) {
                    $_SESSION['flash_err'] = htmlspecialchars($p['name']).' is out of stock.';
                    app_redirect('index.php?page=products');
                }
                $_SESSION['cart'][] = [
                    'id'        => $id,
                    'name'      => $p['name'],
                    'price'     => $p['price'],
                    'qty'       => min($qty, $avail),
                    'image_url' => $p['imageUrls'][0] ?? ($p['images'][0] ?? '')
                ];
            }
            if (is_logged_in()) {
                firestore_update('users', $_SESSION['user']['id'], ['cart' => $_SESSION['cart']]);
            }
        }
        app_redirect('index.php?page=cart');
    }

    // Update Cart Quantities
    if ($act === 'cart_update') {
        if (isset($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $i => $q) {
                if (isset($_SESSION['cart'][$i])) {
                    $pid = $_SESSION['cart'][$i]['id'];
                    $qty = max(1, (int)$q);
                    
                    // Stock logic in update
                    $p = firestore_get('products', $pid);
                    $avail = (int)($p['stock'] ?? 999);
                    
                    if ($qty > $avail) {
                        $_SESSION['cart'][$i]['qty'] = $avail;
                        $_SESSION['flash_err'] = 'Some quantities were adjusted due to limited stock.';
                    } else {
                        $_SESSION['cart'][$i]['qty'] = $qty;
                    }
                }
            }
            if (is_logged_in()) {
                firestore_update('users', $_SESSION['user']['id'], ['cart' => $_SESSION['cart']]);
            }
        }
        app_redirect('index.php?page=cart');
    }

    // Checkout
    if ($act === 'checkout') {
        if (!isset($_SESSION['user'])) { app_redirect('index.php?page=login'); }
        $u = $_SESSION['user'];

        // Always fetch freshest address from Firestore (because profile may have been updated)
        $fresh = firestore_get('users', $u['id']);
        if ($fresh && !isset($fresh['error'])) {
            $_SESSION['user']['address'] = $fresh['address'] ?? '';
            $_SESSION['user']['phone']   = $fresh['phone'] ?? '';
            $u = $_SESSION['user'];
        }

        if (empty($u['address'])) {
            $_SESSION['flash_err'] = 'Please add a delivery address before checkout.';
            app_redirect('index.php?page=profile');
        }

        // --- Stock validation ---
        $stock_errors = [];
        $stock_map = []; // product_id => current_stock
        foreach ($_SESSION['cart'] as $it) {
            $prod = firestore_get('products', $it['id']);
            if (!$prod || isset($prod['error'])) { continue; }
            $avail = (int)($prod['stock'] ?? 999);
            $stock_map[$it['id']] = $avail;
            if ((int)$it['qty'] > $avail) {
                $stock_errors[] = htmlspecialchars($it['name']) . ' (only ' . $avail . ' in stock)';
            }
        }
        if (!empty($stock_errors)) {
            $_SESSION['flash_err'] = 'Stock limit exceeded for: ' . implode(', ', $stock_errors);
            app_redirect('index.php?page=cart');
        }

        // --- Place order ---
        $total = 0;
        foreach ($_SESSION['cart'] as $it) $total += $it['price'] * $it['qty'];

        $orderData = [
            'userId'        => $u['id'],
            'totalAmount'   => (float)$total,
            'paymentMethod' => $_POST['payment'] ?? 'COD',
            'status'        => 'Placed',
            'createdAt'     => ['timestamp_utc' => date('Y-m-d\TH:i:s\Z')],
            'address'       => $u['address'] ?? '',
            'items'         => $_SESSION['cart']
        ];
        $order_result = firestore_add('orders', $orderData);

        // Decrement stock for each ordered item
        foreach ($_SESSION['cart'] as $it) {
            $pid = $it['id'];
            if (isset($stock_map[$pid])) {
                $new_stock = max(0, $stock_map[$pid] - (int)$it['qty']);
                firestore_update('products', $pid, ['stock' => $new_stock]);
            }
        }

        $_SESSION['cart'] = [];
        firestore_update('users', $u['id'], ['cart' => []]);
        app_redirect('index.php?page=user_orders&ok=1');
    }
}

// --- ROUTING & GLOBAL DATA ---
$page = $_GET['page'] ?? 'home';
$allowed = ['home','products','cart','checkout','login','register','profile','user_orders','admin_login','index_admin','products_admin','users_admin','orders_admin'];
if (!in_array($page, $allowed)) $page = 'home';

// Fetch global products for display templates
$all_products = [];
if (in_array($page, ['home', 'products', 'products_admin'])) {
    $res = firestore_get_all('products');
    if (is_array($res) && !isset($res['error'])) {
        $all_products = $res;
    }
}

include 'header.php';
if (is_admin()) include 'navbar_admin.php'; else include 'navbar_user.php';

if (!empty($_SESSION['flash_msg'])) { echo '<div class="alert alert-success">'.$_SESSION['flash_msg'].'</div>'; unset($_SESSION['flash_msg']); }
if (!empty($_SESSION['flash_err'])) { echo '<div class="alert alert-danger">'.$_SESSION['flash_err'].'</div>'; unset($_SESSION['flash_err']); }

include "{$page}.php";
include 'footer.php';

// Safe shutdown stateless cookie injection
if (!headers_sent()) {
    $sess_str = json_encode($_SESSION ?? []);
    $compressed = function_exists('gzcompress') ? gzcompress($sess_str) : $sess_str;
    setcookie('app_sess', base64_encode($compressed), time() + 86400 * 7, '/');
}
ob_end_flush();
?>
