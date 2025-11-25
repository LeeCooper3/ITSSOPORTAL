<?php
include 'db.php';

if (isset($_GET['id'])) {
    $submission_id = intval($_GET['id']);

    //
    $stmt = $conn->prepare("SELECT full_name, email, ip_type FROM submissions WHERE id = ?");
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();
    $stmt->close();

    // ✅ Fetch uploaded files
    $stmt = $conn->prepare("SELECT file_name FROM submission_files WHERE submission_id = ?");
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $files = $stmt->get_result();
    $stmt->close();
} else {
    header("Location:index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .thank-you-box {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 500px;
        }
        .message-box {
            background: #f1f1f1;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            text-align: left;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="thank-you-box">
        <h2>✅ Thank You, <?php echo htmlspecialchars($submission['full_name']); ?>!</h2>
        <p>Your application has been submitted successfully.</p>

        <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['email']); ?></p>
        <p><strong>IP Type:</strong> <?php echo htmlspecialchars($submission['ip_type']); ?></p>

        

        <!-- ✅ Uploaded Files -->
        <h5 class="mt-4">Uploaded Files:</h5>
        <ul class="list-group text-start">
            <?php while ($row = $files->fetch_assoc()): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($row['file_name']); ?></li>
            <?php endwhile; ?>
        </ul>

        <a href="index.php" class="btn btn-primary mt-3">Submit Another</a>
    </div>
</body>
</html>
