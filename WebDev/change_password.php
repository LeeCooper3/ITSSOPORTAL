<?php
session_start();
include 'db.php';

// Only allow logged in admins
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['admin_username'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = stripcslashes($_POST['current_password']);
    $new_password     = stripcslashes($_POST['new_password']);
    $confirm_password = stripcslashes($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger'>❌ New passwords do not match.</div>";
    } else {
        // Fetch admin info
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify old password
            if (password_verify($current_password, $user['password'])) {
                // Hash new password
                $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);

                // Update in DB
                $update = $conn->prepare("UPDATE admin_users SET password=? WHERE username=?");
                $update->bind_param("ss", $hashedPassword, $username);
                if ($update->execute()) {
                    $message = "<div class='alert alert-success'>✅ Password updated successfully.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>❌ Failed to update password.</div>";
                }
                $update->close();
            } else {
                $message = "<div class='alert alert-danger'>❌ Current password is incorrect.</div>";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="col-md-5 mx-auto card p-4 shadow-lg">
    <h3 class="mb-3 text-center">Change Password</h3>
    <?= $message ?>
    <form method="POST">
      <div class="mb-3">
        <label>Current Password</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>New Password</label>
        <input type="password" name="new_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Update Password</button>
      <a href="admin.php" class="btn btn-secondary w-100 mt-2">Back to Dashboard</a>
    </form>
  </div>
</div>
</body>
</html>
