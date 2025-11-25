<?php
include 'db.php';

$id = $_GET['id'];

// Delete files first
$files = $conn->query("SELECT * FROM submission_files WHERE submission_id=$id");
while($f = $files->fetch_assoc()) {
    if(file_exists($f['file_path'])) {
        unlink($f['file_path']);
    }
}
$conn->query("DELETE FROM submissions WHERE id=$id");

echo "<script>alert('Deleted Successfully'); window.location='admin.php';</script>";
?>
