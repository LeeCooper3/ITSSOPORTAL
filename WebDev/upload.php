<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['slsu-form-name'];
    $email = $_POST['slsu-email'];
    $ip_type = $_POST['iptype']; // ✅ Get the selected IP Type

    // Insert into submissions table (now with ip_type)
    $stmt = $conn->prepare("INSERT INTO submissions (full_name, email, ip_type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $email, $ip_type);
    $stmt->execute();
    $submission_id = $stmt->insert_id; // Get last inserted ID
    $stmt->close();

    // File upload directory
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Loop through uploaded files
    foreach ($_FILES['slsu-file']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['slsu-file']['name'][$key]);
        $targetPath = $uploadDir . time() . "_" . $fileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            // Save file record to DB
            $stmt = $conn->prepare("INSERT INTO submission_files (submission_id, file_name, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $submission_id, $fileName, $targetPath);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Redirect to thankyou.php with ID
    header("Location: thankyou.php?id=" . $submission_id);
    exit();
}
?>
