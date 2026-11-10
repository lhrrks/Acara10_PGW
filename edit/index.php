<!DOCTYPE html>
<html>
<body>
<h2>Form Edit Data Hidran Kering</h2>
<?php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
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

$stmt = $conn->prepare("SELECT * FROM hidran_kering WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<form action='edit.php' onsubmit='return validateForm()' method='post'>";
        echo "<input type='hidden' name='id' value='" . (int)$row['id'] . "'><br>";

        echo "<label for='ogc_fid'>OGC FID: </label><br>";
        echo "<input type='text' id='ogc_fid' name='ogc_fid' value='" . htmlspecialchars($row['ogc_fid'], ENT_QUOTES, 'UTF-8') . "'><br>";

        echo "<label for='no'>No: </label><br>";
        echo "<input type='text' id='no' name='no' value='" . htmlspecialchars($row['no'], ENT_QUOTES, 'UTF-8') . "'><br>";

        echo "<label for='nama_kode'>Nama Kode: </label><br>";
        echo "<input type='text' id='nama_kode' name='nama_kode' value='" . htmlspecialchars($row['nama_kode'], ENT_QUOTES, 'UTF-8') . "'><br>";

        echo "<label for='alamat'>Alamat: </label><br>";
        echo "<input type='text' id='alamat' name='alamat' value='" . htmlspecialchars($row['alamat'], ENT_QUOTES, 'UTF-8') . "'><br>";

        echo "<label for='status'>Status: </label><br>";
        echo "<input type='text' id='status' name='status' value='" . htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') . "'><br>";

        echo "<label for='long'>Longitude: </label><br>";
        echo "<input type='text' id='long' name='longitude' value='" . htmlspecialchars($row['longitude'], ENT_QUOTES, 'UTF-8') . "'><br>";

        echo "<label for='lat'>Latitude: </label><br>";
        echo "<input type='text' id='lat' name='latitude' value='" . htmlspecialchars($row['latitude'], ENT_QUOTES, 'UTF-8') . "'><br><br>";

        echo "<input type='submit' value='Submit'>";
        echo "</form>";
    }
}

$stmt->close();
$conn->close();
?>
<p id="informasi"></p>

<script>
function validateForm() {
    let long = document.getElementById("long").value;
    let lat = document.getElementById("lat").value;
    let text = "";

    if (isNaN(long) || isNaN(lat)) {
        text = "Koordinat (Longitude & Latitude) harus berupa angka!";
        document.getElementById("informasi").innerHTML = text;
        return false;
    }
    return true;
}
</script>
</body>
</html>