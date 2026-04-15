<div class="container container-auth">
  <div class="card shadow" style="max-width:420px;width:100%">
    <div class="card-header card-header-pet text-center fw-bold">Create Account</div>
    <div class="card-body">
      <form method="post" action="index.php?page=register">
        <input type="hidden" name="act" value="register">
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="username" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="confirm" class="form-control" required></div>
        <button class="btn btn-pet w-100">Register</button>
      </form>
    </div>
  </div>
</div>