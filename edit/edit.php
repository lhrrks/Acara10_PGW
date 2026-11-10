<?php
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$ogc_fid = filter_input(INPUT_POST, 'ogc_fid', FILTER_VALIDATE_FLOAT);
$no = filter_input(INPUT_POST, 'no', FILTER_VALIDATE_INT);
$nama_kode = trim((string)filter_input(INPUT_POST, 'nama_kode'));
$alamat = trim((string)filter_input(INPUT_POST, 'alamat'));
$status = trim((string)filter_input(INPUT_POST, 'status'));
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);

if ($id === false || $ogc_fid === false || $no === false || $longitude === false || $latitude === false || $nama_kode === '' || $alamat === '' || $status === '') {
    http_response_code(400);
    die("Data tidak valid.");
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pgw*";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("UPDATE hidran_kering SET ogc_fid = ?, no = ?, nama_kode = ?, alamat = ?, status = ?, longitude = ?, latitude = ? WHERE id = ?");
$stmt->bind_param("diissddi", $ogc_fid, $no, $nama_kode, $alamat, $status, $longitude, $latitude, $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../index.php");
    exit();
}

$error = $stmt->error;
$stmt->close();
$conn->close();

http_response_code(500);
throw new RuntimeException("Error: " . $error);
?>