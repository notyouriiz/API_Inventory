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

<!-- Theme toggle -->
<!-- Theme toggle -->
<button id="themeToggle"
    style="
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;

        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid;
        cursor: pointer;
        font-size: 13px;

        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    ">
    🌙 Dark mode
</button>




<footer>
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



<style>
/* ===== Theme styling ===== */
:root {
    --surface-bg: #fff;
    --text-muted: #444;
    --flash-success-bg: #e8ffe8;
    --flash-success-border: #b7f0b7;

    --flash-error-bg: #ffe6e6;
    --flash-error-border: #f5b7b7;

    --flash-text: #000000ff;
}


.card {
    display: flex;
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    background: var(--surface-bg);
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    gap: 20px;
}

body.dark .card {
    background: #1e1e1e;
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
}

/* default (light) */
body {
    background: #f4f6f8;
    color: #222;
}

/* dark mode base */
body.dark {
    --surface-bg: #1e1e1e;
    --text-muted: #e1ddddff;
    --flash-success-bg: #e8ffe8;
    --flash-success-border: #b7f0b7;

    --flash-error-bg: #ffe6e6;
    --flash-error-border: #f5b7b7;

    --flash-text: #000000ff;
    --warn-bg: #fff3cd;
    background: #121212;
    color: #eaeaea;
}

footer {
    background: #fff;
    border-top: 1px solid #ddd;
    padding: 20px 30px;
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    font-size: 0.95em;
    color: #333;
    box-shadow: 0 -2px 6px rgba(0,0,0,0.05);
    margin-top: 30px;
}

/* footer */
body.dark footer {
    background: #1a1a1a;
    border-top-color: #333;
    color: #ddd;
}

/* lists & blocks used in footer */
body.dark li {
    background: #272727ff !important;
    color: #ddd !important;
}

body.dark li[data-surface] {
    background: #fff3cd !important;
    color: #1e1e1e !important;
}


body.dark code {
    color: #ccc !important;
}

/* inputs & controls */
body.dark input,
body.dark textarea,
body.dark select {
    background: #1f1f1f;
    color: #eaeaea;
    border: 1px solid #444;
}

/* Light mode → show Dark mode button */
body:not(.dark) #themeToggle {
    background: #111;
    color: #fff;
    border-color: #111;
}

/* Dark mode → show Light mode button */
body.dark #themeToggle {
    background: #ffffff;
    color: #111;
    border-color: #ddd;
}



</style>


<script>
(function () {
    const toggle = document.getElementById("themeToggle");
    if (!toggle) return;

    const body = document.body;

    // Load saved preference
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") {
        body.classList.add("dark");
        toggle.textContent = "☀️ Light mode";
    }

    toggle.addEventListener("click", () => {
        body.classList.toggle("dark");

        const isDark = body.classList.contains("dark");
        localStorage.setItem("theme", isDark ? "dark" : "light");

        toggle.textContent = isDark ? "☀️ Light mode" : "🌙 Dark mode";
    });
})();
</script>

</body>
</html>