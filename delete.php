<?php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    http_response_code(400);
    die("ID tidak valid.");
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pgw*";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("DELETE FROM hidran_kering WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: index.php");
    exit();
}

$error = $stmt->error;
$stmt->close();
$conn->close();

http_response_code(500);
throw new RuntimeException("Error: " . $error);
?>