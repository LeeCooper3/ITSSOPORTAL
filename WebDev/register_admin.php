<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate password confirmation
    if ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } else {
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "⚠️ Username already taken!";
        } else {
            // Hash password with bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashedPassword);

            if ($stmt->execute()) {
                // Redirect with success flag
                header("Location: login.php?success=1");
                exit;
            } else {
                $error = "❌ Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #235601, #A7DBE6, #F9990D);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .register-card {
      border-radius: 20px;
      padding: 2rem;
      background: #fff;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 420px;
    }
    .logo {
      width: 100px;
      margin: 0 auto 15px auto;
      display: block;
    }
    .btn-success {
      transition: 0.3s ease-in-out;
    }
    .btn-success:hover {
      background-color: #218838;
      transform: scale(1.02);
    }
    .btn-secondary:hover {
      transform: scale(1.02);
    }
  </style>
</head>
<body>
<div class="register-card text-center">
  <!-- Logo -->
  <img src="ITSSOLOGO.png" alt="Logo" class="logo">
  <h3 class="mb-4">Register New Admin</h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3 text-start">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3 text-start">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3 text-start">
      <label class="form-label">Confirm Password</label>
      <input type="password" name="confirm_password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success w-100">Register</button>
    <a href="login.php" class="btn btn-outline-secondary w-100 mt-2">Back to Login</a>
  </form>
</div>
</body>
</html>
