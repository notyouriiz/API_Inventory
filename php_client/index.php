<?php
require_once "header.php";
?>

<!-- UI -->
<h1>Dashboard</h1>
<p>This is the index. Navigate using links above</p>

<?php
// pop up login sucessful message
$last_login_json = $_SESSION['last_login_response_json'] ?? null;
if ($last_login_json) {
        unset($_SESSION['last_login_response_json']);

        // get keys from JSON
        $data = json_decode($last_login_json, true);
        $displayValue = null;

        // get tokens, refresh and user fields
        $access = $data['access_token'] ?? $data['token'] ?? $data['access'] ?? null;
        $refresh = $data['refresh_token'] ?? $data['refresh'] ?? null;

        $userId = null; $userName = null; $userEmail = null;
        if (isset($data['user']) && is_array($data['user'])) {
                $userId = $data['user']['id'] ?? null;
                $userName = $data['user']['name'] ?? null;
                $userEmail = $data['user']['email'] ?? null;
        } else {
                // fallback to top-level keys
                $userId = $data['id'] ?? null;
                $userName = $data['name'] ?? null;
                $userEmail = $data['email'] ?? null;
        }

        $accessLine = $access !== null ? $access : 'N/A';
        $refreshLine = $refresh !== null ? $refresh : 'N/A';
        $userIdLine = $userId !== null ? $userId : 'N/A';
        $userNameLine = $userName !== null ? $userName : 'N/A';
        $userEmailLine = $userEmail !== null ? $userEmail : 'N/A';

        $console = "Access token = " . $accessLine
                . "\nRefresh token = " . $refreshLine
                . "\nID = " . $userIdLine
                . "\nname = " . $userNameLine
                . "\nemail = " . $userEmailLine;

        $console_escaped = htmlspecialchars($console, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo <<<HTML
                <div id="login-json-modal" style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.4);z-index:9999">
                        <div style="background:#fff;border-radius:6px;max-width:90%;width:720px;max-height:80%;overflow:auto;padding:18px;box-shadow:0 8px 24px rgba(0,0,0,.2);position:relative;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif">
                                <button id="close-login-json" style="position:absolute;right:10px;top:8px;border:0;background:transparent;font-size:20px;line-height:1;cursor:pointer">&times;</button>
                                <h3 style="margin-top:0">Last login (console)</h3>
                                <pre style="background:#f7f7f7;padding:12px;border-radius:4px;overflow:auto;font-family:monospace;">$console_escaped</pre>
                        </div>
                </div>
                <script>
                (function(){
                        var btn = document.getElementById('close-login-json');
                        var modal = document.getElementById('login-json-modal');
                        if(btn && modal){
                                btn.addEventListener('click', function(){ modal.parentNode.removeChild(modal); });
                        }
                        document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ var m=document.getElementById('login-json-modal'); if(m) m.parentNode.removeChild(m); } });
                })();
                </script>
                HTML;
}

require_once "footer.php";
?>
