<?php

$apiRoot = null;
$apiHealth = null;

// duration
// no calling api every page
// it won't update immediately before the 10 mins mark
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
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #f8f9fa;
    border-top: 1px solid #ccc;
    padding: 12px 20px;
    font-size: 0.9em;
    color: #333;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
    z-index: 1000;
">
    <div>
        <strong>API Info:</strong> <?= htmlspecialchars($apiMessage) ?> (v<?= htmlspecialchars($apiVersion) ?>)
    </div>
    <div>
        endpoints:
        <?php if (!empty($endpoints)): ?>
            <?php foreach ($endpoints as $name => $url): ?>
                <span><?= htmlspecialchars($name) ?>: <code><?= htmlspecialchars($url) ?></code></span>
            <?php endforeach; ?>
        <?php else: ?>
            None
        <?php endif; ?>
    </div>
    <div style="margin-top: 5px;">
        <strong>status:</strong>
        <span style="color: <?= $statusColor ?>; font-weight: bold;">
            <?= htmlspecialchars($statusText) ?>
        </span>
        (database: <?= htmlspecialchars($databaseStatus) ?>)
    </div>
</footer>



</body>
</html>