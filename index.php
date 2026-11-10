<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "pgw*";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM hidran_kering ORDER BY id ASC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$rows = [];
$mapPoints = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;

    if (
        is_numeric($row["latitude"]) &&
        is_numeric($row["longitude"]) &&
        (float)$row["latitude"] >= -90 &&
        (float)$row["latitude"] <= 90 &&
        (float)$row["longitude"] >= -180 &&
        (float)$row["longitude"] <= 180
    ) {
        $mapPoints[] = [
            "latitude" => (float)$row["latitude"],
            "longitude" => (float)$row["longitude"],
            "nama_kode" => $row["nama_kode"],
            "alamat" => $row["alamat"],
            "status" => $row["status"]
        ];
    }
}

$mapPointsJson = json_encode(
    $mapPoints,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sebaran Hidran Kering</title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
        }

        #map {
            height: 480px;
            width: 100%;
            margin: 16px 0 24px;
        }

        .hydrant-div-icon {
            background: transparent;
            border: 0;
        }

        .hydrant-marker {
            display: block;
            color: #198754;
        }

        .hydrant-marker--inactive {
            color: #dc3545;
        }

        .layer-control-entry {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .layer-control-icon {
            display: block;
            flex: none;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
    <h2>Data Sebaran Hidran Kering</h2>
    <p><a href="input/index.html">Input</a></p>

    <div id="map" aria-label="Peta sebaran hidran kering"></div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>OGC FID</th>
                <th>No</th>
                <th>Nama Kode</th>
                <th>Alamat</th>
                <th>Status</th>
                <th>Longitude</th>
                <th>Latitude</th>
                <th colspan="2">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) > 0): ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["id"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["ogc_fid"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["no"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["nama_kode"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["alamat"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["status"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["longitude"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["latitude"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><a href="delete.php?id=<?= (int)$row["id"] ?>">hapus</a></td>
                        <td><a href="edit/index.php?id=<?= (int)$row["id"] ?>">edit</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10">Tidak ada data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
        const mapPoints = <?= $mapPointsJson ?>;
        const map = L.map("map").setView([-2.5, 118], 5);

        const osmLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        });
        const topoLayer = L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
            maxZoom: 17,
            attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="https://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a>'
        });
        osmLayer.addTo(map);

        const markerBounds = [];
        const workingHydrants = L.layerGroup().addTo(map);
        const notWorkingHydrants = L.layerGroup().addTo(map);
        const hydrantLayers = {
            active: workingHydrants,
            inactive: notWorkingHydrants
        };
        const hydrantPath = '<path d="M21 2C10.5 2 3 9.5 3 19c0 11 18 27 18 27s18-16 18-27C39 9.5 31.5 2 21 2Z" fill="currentColor" stroke="#fff" stroke-width="2"/>' +
            '<path d="M19 10h4v4h3v3h3v4h-3v3h3v4h-3v3h-3v3h-6v-3h-3v-3h-3v-4h3v-3h-3v-4h3v-3h3v-4h2Zm-4 9v2h12v-2H15Zm0 7v2h12v-2H15Z" fill="#fff"/>';

        function escapeHtml(value) {
            const element = document.createElement("span");
            element.textContent = value ?? "";
            return element.innerHTML;
        }

        function getStatusType(status) {
            const normalizedStatus = String(status ?? "").trim().toLocaleLowerCase("id");

            if (["berfungsi", "beroperasi", "aktif", "baik"].includes(normalizedStatus)) {
                return "active";
            }

            if (
                ["tidak berfungsi", "tidak beroperasi", "tidak aktif", "rusak", "nonaktif", "mati"].includes(normalizedStatus) ||
                /\b(tidak|rusak|nonaktif|mati|perbaikan)\b/.test(normalizedStatus)
            ) {
                return "inactive";
            }

            return "unknown";
        }

        function createHydrantIcon(statusType, small = false) {
            const size = small ? 21 : 48;
            const className = statusType === "inactive"
                ? "hydrant-marker hydrant-marker--inactive"
                : "hydrant-marker";

            return '<svg class="' + className + (small ? ' layer-control-icon' : '') +
                '" viewBox="0 0 42 48" width="' + (small ? 18 : 42) +
                '" height="' + size + '" aria-hidden="true">' + hydrantPath + '</svg>';
        }

        function layerControlLabel(label, statusType) {
            return '<span class="layer-control-entry">' +
                createHydrantIcon(statusType, true) +
                '<span>' + label + '</span></span>';
        }

        mapPoints.forEach((point) => {
            const statusType = getStatusType(point.status);
            if (statusType === "unknown") {
                return;
            }

            const icon = L.divIcon({
                className: "hydrant-div-icon",
                html: createHydrantIcon(statusType),
                iconSize: [42, 48],
                iconAnchor: [21, 46],
                popupAnchor: [0, -42]
            });
            const marker = L.marker([point.latitude, point.longitude], { icon })
                .addTo(hydrantLayers[statusType]);
            marker.bindPopup(
                "<strong>" + escapeHtml(point.nama_kode) + "</strong><br>" +
                escapeHtml(point.alamat) + "<br>Status: " +
                escapeHtml(point.status)
            );
            markerBounds.push([point.latitude, point.longitude]);
        });

        L.control.layers(
            {
                "OpenStreetMap": osmLayer,
                "Topografi": topoLayer
            },
            {
                [layerControlLabel("Hidran berfungsi", "active")]: workingHydrants,
                [layerControlLabel("Hidran tidak berfungsi", "inactive")]: notWorkingHydrants
            }
        ).addTo(map);

        if (markerBounds.length === 1) {
            map.setView(markerBounds[0], 16);
        } else if (markerBounds.length > 1) {
            map.fitBounds(markerBounds, { padding: [24, 24], maxZoom: 16 });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
