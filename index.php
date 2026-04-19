<?php
ob_start();
require_once __DIR__ . '/config.php';

if (!function_exists('firestore_get_all')) {
    die("<h3>Vercel Cache Error</h3><p>Your web server loaded an old cached copy of the config file. Please wait a few more seconds for Vercel to finish building the latest commit and refresh this page!</p>");
}

// --- LOGOUT ACTIONS ---
if (isset($_GET['action']) && $_GET['action'] === 'logout_user') { 
    unset($_SESSION['user']); 
    unset($_SESSION['user_creds']);
    unset($_SESSION['user_id_token']);
    $_SESSION['cart'] = []; 
    app_redirect('index.php'); 
}
if (isset($_GET['action']) && $_GET['action'] === 'logout_admin') { 
    unset($_SESSION['admin']); 
    unset($_SESSION['admin_creds']); 
    unset($_SESSION['user_id_token']);
    app_redirect('index.php'); 
}

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

if (isset($_GET['action']) && $_GET['action'] === 'clear_cart_after_order') {
    $_SESSION['cart'] = [];
    if (is_logged_in()) {
        firestore_update('users', $_SESSION['user']['id'], ['cart' => []]);
    }
    $_SESSION['flash_msg'] = 'Order placed successfully!';
    app_redirect('index.php?page=user_orders&ok=1');
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
            
            // Store credentials for client-side Firebase Auth sign-in
            // Required so JS SDK can authenticate for Firestore security rules
            $_SESSION['user_creds'] = ['email' => $email, 'password' => $pass];
            $_SESSION['user_id_token'] = $res['idToken'] ?? null;
            
            // Sync cart with Firestore
            $db_cart = $profile['cart'] ?? [];
            if (!empty($_SESSION['cart'])) {
                firestore_update('users', $res['localId'], ['cart' => $_SESSION['cart']]);
            } else if (!empty($db_cart)) {
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
                // Store admin credentials for client-side Firebase Auth
                $_SESSION['admin_creds'] = ['email' => $email, 'password' => $pass];
                $_SESSION['user_id_token'] = $res['idToken'] ?? null;
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

    // Checkout/Place Order
    if ($act === 'checkout') {
        if (!is_logged_in()) { app_redirect('index.php?page=login'); }
        if (empty($_SESSION['cart'])) { app_redirect('index.php?page=cart'); }
        
        $u = $_SESSION['user'];

        // Fresh address check
        $fresh = firestore_get('users', $u['id']);
        if ($fresh && !isset($fresh['error'])) {
            $_SESSION['user']['address'] = $fresh['address'] ?? '';
            $_SESSION['user']['phone']   = $fresh['phone'] ?? '';
            $u = $_SESSION['user'];
        }

        if (empty($u['address']) || empty($u['phone'])) {
            $_SESSION['flash_err'] = 'Please complete your address and phone details before placing an order.';
            app_redirect('index.php?page=checkout');
        }

        // --- Step 1: Strict Stock Validation ---
        $stock_errors = [];
        $validated_items = [];
        $total = 0;

        foreach ($_SESSION['cart'] as $it) {
            $prod = firestore_get('products', $it['id']);
            if (!$prod || isset($prod['error'])) {
                $stock_errors[] = "Product " . htmlspecialchars($it['name']) . " no longer exists.";
                continue;
            }
            $avail = (int)($prod['stock'] ?? 0);
            if ((int)$it['qty'] > $avail) {
                $stock_errors[] = htmlspecialchars($it['name']) . " (Only $avail left)";
            } else {
                // Prepare item with correct schema for order
                $validated_items[] = [
                    'productId' => $it['id'],
                    'name'      => $it['name'],
                    'price'     => (float)$it['price'],
                    'quantity'  => (int)$it['qty'],
                    'image'     => $it['image_url'] ?? ''
                ];
                $total += (float)$it['price'] * (int)$it['qty'];
            }
        }

        if (!empty($stock_errors)) {
            $_SESSION['flash_err'] = 'Stock issue: ' . implode(', ', $stock_errors);
            app_redirect('index.php?page=cart');
        }

        // --- Step 2: Create Order Document ---
        $orderData = [
            'userId'        => $u['id'],
            'userEmail'     => $u['username'], // Helper for admin
            'items'         => $validated_items,
            'totalAmount'   => (float)$total,
            'address'       => $u['address'],
            'phone'         => $u['phone'],
            'status'        => 'pending',
            'createdAt'     => date('Y-m-d\TH:i:s\Z')
        ];

        $order_res = firestore_add('orders', $orderData);

        if (isset($order_res['id'])) {
            // Update the order with its own ID (optional but good for consistency)
            firestore_update('orders', $order_res['id'], ['orderId' => $order_res['id']]);

            // --- Step 3: Atomic Stock Deduction ---
            foreach ($validated_items as $vi) {
                firestore_increment('products', $vi['productId'], 'stock', -($vi['quantity']));
            }

            // --- Step 4: Clear Cart ---
            $_SESSION['cart'] = [];
            if (is_logged_in()) {
                firestore_update('users', $u['id'], ['cart' => []]);
            }
            
            $_SESSION['flash_msg'] = 'Order placed successfully!';
            app_redirect('index.php?page=user_orders&ok=1');
        } else {
            $_SESSION['flash_err'] = 'Critical: Failed to save order. Please try again.';
            app_redirect('index.php?page=checkout');
        }
    }

}

// --- ROUTING & GLOBAL DATA ---
$page = $_GET['page'] ?? 'home';
$allowed = ['home','products','cart','checkout','login','register','profile','user_orders','admin_login','index_admin','products_admin','users_admin','orders_admin'];
if (!in_array($page, $allowed)) $page = 'home';

// Fetch global products for display templates
$all_products = [];
if (in_array($page, ['home', 'products', 'products_admin', 'cart'])) {
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
