<?php if(!isset($PAGE_TITLE)) $PAGE_TITLE='PetCare'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($PAGE_TITLE); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="styles.css" rel="stylesheet">
<script>
  // Firebase project config (public, safe to expose)
  window.FIREBASE_CONFIG = {
    apiKey: "<?php echo FIREBASE_API_KEY ?? ''; ?>",
    projectId: "<?php echo FIREBASE_PROJECT_ID ?? ''; ?>"
  };

  // Inject credentials for client-side Firebase Auth sign-in.
  // This allows the JS Firebase SDK to authenticate and comply with Firestore rules.
  // The password is already known to the user (they typed it), so this is not a security leak.
  <?php if (isset($_SESSION['user_creds'])): ?>
  window.FIREBASE_USER_CREDS = {
    email: <?php echo json_encode($_SESSION['user_creds']['email']); ?>,
    password: <?php echo json_encode($_SESSION['user_creds']['password']); ?>
  };
  <?php elseif (isset($_SESSION['admin_creds'])): ?>
  window.FIREBASE_USER_CREDS = {
    email: <?php echo json_encode($_SESSION['admin_creds']['email']); ?>,
    password: <?php echo json_encode($_SESSION['admin_creds']['password']); ?>
  };
  <?php else: ?>
  window.FIREBASE_USER_CREDS = null;
  <?php endif; ?>
</script>
</head>
<body>
