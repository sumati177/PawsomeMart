<?php 
if(!is_user()){ 
    app_redirect('index.php?page=login');
} 

// Always refresh user address/phone from Firestore to get the latest
$fresh_profile = firestore_get('users', $_SESSION['user']['id']);
if ($fresh_profile && !isset($fresh_profile['error'])) {
    $_SESSION['user']['address'] = $fresh_profile['address'] ?? '';
    $_SESSION['user']['phone']   = $fresh_profile['phone'] ?? '';
}

$user = $_SESSION['user'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'profile_update') {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Update in Firestore
    firestore_update('users', $user['id'], [
        'phone' => $phone,
        'address' => $address,
        'updated_at' => date('Y-m-d\TH:i:s\Z')
    ]);
    
    // Update session
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['address'] = $address;
    
    $_SESSION['flash_msg'] = 'Profile updated successfully!';
    app_redirect('index.php?page=profile');
}
?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header card-header-pet fw-bold">My Profile</div>
        <div class="card-body">
          <?php if(isset($_SESSION['flash_msg'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_msg']); ?></div>
            <?php unset($_SESSION['flash_msg']); ?>
          <?php endif; ?>
          <form method="post" action="index.php?page=profile">
            <input type="hidden" name="act" value="profile_update">
            <div class="mb-3"><label class="form-label">Username/Email</label><input class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled></div>
            <div class="mb-3"><label class="form-label">Mobile No</label><input name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']??''); ?>"></div>
            <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']??''); ?></textarea></div>
            <button class="btn btn-pet">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>