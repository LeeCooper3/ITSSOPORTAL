<?php
$host = "localhost";
$user = "root";   // default in XAMPP
$pass = "";       // default empty
$db   = "itsso_system";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
