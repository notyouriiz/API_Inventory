<?php

$apiRoot = null;
$apiHealth = null;
$cacheTime = 600;

// check duration
if (isset($_SESSION['api_cache_time']) && (time() - $_SESSION['api_cache_time']) < $cacheTime) {
    $apiRoot = $_SESSION['api_root'] ?? null;
    $apiHealth = $_SESSION['api_health'] ?? null;
} else {
    // root
    $ch = curl_init("http://127.0.0.1:5000/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['message'])) $apiRoot = $data;
    }
    curl_close($ch);

    // health
    $ch = curl_init("http://127.0.0.1:5000/health");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) $apiHealth = $data;
    }
    curl_close($ch);

    // store in session
    $_SESSION['api_root'] = $apiRoot;
    $_SESSION['api_health'] = $apiHealth;
    $_SESSION['api_cache_time'] = time();
}

// connected status
$apiMessage = $apiRoot['message'] ?? "Not connected";
$apiVersion = $apiRoot['version'] ?? "-";
$endpoints = $apiRoot['endpoints'] ?? [];

// health statuus
$statusText = $apiHealth['status'] ?? "Not connected";
$statusColor = ($statusText === "healthy") ? "#28a745" : ($statusText === "Not connected" ? "#6c757d" : "#dc3545");
$databaseStatus = $apiHealth['database'] ?? "-";
?>

<!-- UI -->
</main>
<footer style="
    background: #fff;
    border-top: 1px solid #ddd;
    padding: 20px 30px;
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    font-size: 0.95em;
    color: #333;
    box-shadow: 0 -2px 6px rgba(0,0,0,0.05);
    margin-top: 30px;
">
    <div style="max-width:1200px; margin:0 auto; display:flex; flex-direction:column; gap:10px;">

        <!-- API message & version -->
        <div style="font-weight:bold;">
            API Info: <?= htmlspecialchars($apiMessage) ?> 
            <span style="color:#0073e6;">(v<?= htmlspecialchars($apiVersion) ?>)</span>
        </div>

        <!-- API endpoints -->
        <div>
            <strong>Endpoints:</strong>
            <?php if (!empty($endpoints)): ?>
                <ul style="list-style:none; padding-left:0; margin:5px 0;">
                    <?php foreach ($endpoints as $name => $url): ?>
                        <li style="background:#f7f7f7; padding:6px 10px; border-radius:6px; margin-bottom:4px;">
                            <strong><?= htmlspecialchars($name) ?>:</strong> 
                            <code style="color:#555; font-size:0.9em;"><?= htmlspecialchars($url) ?></code>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <span style="color:#888;">None</span>
            <?php endif; ?>
        </div>

        <!-- API health status -->
        <div>
            <strong>Status:</strong>
            <span style="color: <?= $statusColor ?>; font-weight:bold;"><?= htmlspecialchars($statusText) ?></span>
            <span>(Database: <?= htmlspecialchars($databaseStatus) ?>)</span>
        </div>

    </div>
</footer>
</body>
</html>