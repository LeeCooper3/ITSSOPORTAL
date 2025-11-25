<?php
include 'db.php';

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM submissions WHERE id=$id");
$row = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $conn->query("UPDATE submissions SET full_name='$full_name', email='$email' WHERE id=$id");
    echo "<script>alert('Updated Successfully'); window.location='admin.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Submission</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2>Edit Submission</h2>
  <form method="POST" class="card p-4 shadow-lg">
    <div class="mb-3">
      <label>Full Name</label>
      <input type="text" name="full_name" class="form-control" value="<?= $row['full_name'] ?>" required>
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" value="<?= $row['email'] ?>" required>
    </div>
    <button type="submit" class="btn btn-success">Update</button>
    <a href="admin.php" class="btn btn-secondary">Back</a>
  </form>
</div>
</body>
</html>
