<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = stripcslashes($_POST['username']);
    $password = stripcslashes($_POST['password']);

    // Prepared statement
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];
            header("Location: admin.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #F9990D, #235601);
    
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-card {
      border-radius: 20px;
      padding: 2rem;
      background: #fff;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .logo {
      width: 100px;
      margin: 0 auto 15px auto;
      display: block;
    }
    .btn-primary {
      transition: 0.3s ease-in-out;
    }
    .btn-primary:hover {
      background-color: #0056b3;
      transform: scale(1.02);
    }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="login-card text-center">
        <!-- Logo -->
        <img src="ITSSOLOGO.png" alt="Logo" class="logo">
        <h3 class="mb-4">Admin Login</h3>
        
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
          <button type="submit" class="btn btn-primary w-100">Login</button>
          <a href="register_admin.php" class="btn btn-outline-secondary w-100 mt-2">Register</a>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
