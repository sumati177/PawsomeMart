<?php
require_once('config.php');

if (!is_admin()) {
    header('Location: index.php?page=admin_login');
    exit;
}

        // --- Handle Delete User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'admin_delete_user') {
    $uid = $_POST['id'] ?? '';
    if ($uid) {
        firestore_delete('users', $uid);
        $_SESSION['flash_msg'] = 'User deleted successfully.';
        header('Location: index.php?page=users_admin');
        exit;
    }
}

// --- Handle Flash Messages ---
if (isset($_SESSION['flash_msg'])) {
    echo '<div class="container mt-3"><div class="alert alert-success text-center">'
        . htmlspecialchars($_SESSION['flash_msg']) . '</div></div>';
    unset($_SESSION['flash_msg']);
}
if (isset($_SESSION['flash_err'])) {
    echo '<div class="container mt-3"><div class="alert alert-danger text-center">'
        . htmlspecialchars($_SESSION['flash_err']) . '</div></div>';
    unset($_SESSION['flash_err']);
}

// --- Fetch All Users ---
$all_users = firestore_get_all('users');
$users = [];
if ($all_users) {
    foreach ($all_users as $id => $u) {
        $u['id'] = $id;
        $users[] = $u;
    }
}
?>

<div class="container mt-4">
  <h3 class="mb-3">Manage Users</h3>
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Email</th>
          <th>Address</th>
          <th>Phone</th>
          <th>Role</th>
          <th class="text-center" style="width:100px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($users) > 0): ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo htmlspecialchars($u['id']); ?></td>
              <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($u['address'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td>
              <td><?php echo (isset($u['isAdmin']) && $u['isAdmin'] === true) ? 'Admin' : 'User'; ?></td>
              <td class="text-center">
                <form method="post" action="index.php?page=users_admin"
                      onsubmit="return confirm('Are you sure you want to delete this user permanently?');">
                  <input type="hidden" name="act" value="admin_delete_user">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($u['id']); ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
