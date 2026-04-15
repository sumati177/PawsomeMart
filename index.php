<?php
ob_start();
require_once('config.php');
$PAGE_TITLE = 'PetCare';

// --- SESSION SECURITY: VALIDATE FIREBASE TOKEN ---
if (isset($_SESSION['user']['idToken'])) {
    $check = firebase_auth_request('lookup', ['idToken' => $_SESSION['user']['idToken']]);
    if (isset($check['error'])) {
        unset($_SESSION['user']);
        $_SESSION['flash_err'] = 'Session expired. Please login again.';
        header('Location: index.php?page=login');
        exit;
    }
}
if (isset($_SESSION['admin']['idToken'])) {
    $check = firebase_auth_request('lookup', ['idToken' => $_SESSION['admin']['idToken']]);
    if (isset($check['error'])) {
        unset($_SESSION['admin']);
        $_SESSION['flash_err'] = 'Admin session expired.';
        header('Location: index.php?page=admin_login');
        exit;
    }
}

// --- LOGOUT ACTIONS ---
if (isset($_GET['action']) && $_GET['action'] === 'logout_user') { unset($_SESSION['user']); header('Location: index.php'); exit; }
if (isset($_GET['action']) && $_GET['action'] === 'logout_admin') { unset($_SESSION['admin']); header('Location: index.php'); exit; }

// --- CART REMOVAL ---
if (isset($_GET['page']) && $_GET['page'] === 'cart' && isset($_GET['remove'])) {
    $i = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$i])) array_splice($_SESSION['cart'], $i, 1);
    header('Location:index.php?page=cart'); exit;
}

// --- POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

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
            header('Location: index.php?page=register');
        } else {
            // Create user profile in Firestore
            $profile_result = firestore_add('users', [
                'email'      => $email,
                'role'       => 'user',
                'created_at' => date('Y-m-d\TH:i:s\Z'),
                'phone'      => '',
                'address'    => ''
            ]);
            
            $_SESSION['flash_msg'] = 'Registered successfully! You can now login.';
            header('Location: index.php?page=login');
        }
        exit;
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
            header('Location: index.php?page=login');
        } else {
            // Fetch extra profile data from Firestore
            $profile = firestore_get('users', $res['localId']);
            $_SESSION['user'] = [
                'id'       => $res['localId'],
                'username' => $res['email'],
                'idToken'  => $res['idToken'],
                'phone'    => $profile['phone'] ?? '',
                'address'  => $profile['address'] ?? ''
            ];
            header('Location: index.php');
        }
        exit;
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
            header('Location: index.php?page=admin_login');
        } else {
            // Verify if user info exists and role is admin in Firestore
            $profile = firestore_get('users', $res['localId']);
            if (is_array($profile) && isset($profile['role']) && $profile['role'] === 'admin') {
                $_SESSION['admin'] = [
                    'id'       => $res['localId'],
                    'username' => $res['email'],
                    'idToken'  => $res['idToken']
                ];
                header('Location: index.php?page=index_admin');
            } else {
                $_SESSION['flash_err'] = 'Unauthorized: Admin role required.';
                header('Location: index.php?page=admin_login');
            }
        }
        exit;
    }

    // Add to Cart
    if ($act === 'add_cart') {
        $id = $_POST['id'] ?? ''; $qty = (int)($_POST['qty'] ?? 1);
        $p = firestore_get('products', $id);
        if ($p) {
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            $found = false;
            foreach ($_SESSION['cart'] as &$it) { if ($it['id'] === $id) { $it['qty'] += $qty; $found = true; break; } }
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $id, 'name' => $p['name'], 'price' => $p['price'], 
                    'qty' => $qty, 'image_url' => $p['images'][0] ?? ''
                ];
            }
        }
        header('Location: index.php?page=cart'); exit;
    }

    // Checkout
    if ($act === 'checkout') {
        if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit; }
        $u = $_SESSION['user'];
        $total = 0; foreach ($_SESSION['cart'] as $it) $total += $it['price'] * $it['qty'];
        
        $orderData = [
            'user_id' => $u['id'], 'user_name' => $u['username'], 'total' => $total,
            'status' => 'Placed', 'created_at' => date('Y-m-d\TH:i:s\Z'), 'items' => $_SESSION['cart']
        ];
        firestore_add('orders', $orderData);
        $_SESSION['cart'] = [];
        header('Location: index.php?page=user_orders&ok=1'); exit;
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
?>
