<?php
require_once "config.php";
require_once "api.php";

$flash = null;

// If already logged in, go to dashboard
if (isset($_SESSION['access_token']) && isset($_SESSION['refresh_token'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $res = callAPI("POST", "$authBase/login", [
        "email"    => $_POST['email'] ?? "",
        "password" => $_POST['password'] ?? ""
    ]);

    if (isset($res['json']['access_token'])) {

        // store tokens in session
        $_SESSION['access_token']  = $res['json']['access_token'];
        $_SESSION['refresh_token'] = $res['json']['refresh_token'] ?? null;

        // store the json flash to index.php
        $_SESSION['last_login_response_json'] =
            json_encode($res['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // redirect to dashboard
        header("Location: index.php");
        exit;

    } else {
        $http    = $res['http'] ?? 0;
        $apiErr  = $res['json']['error'] ?? ($res['raw'] ?? "Unknown error");
        $flash   = "Login Failed — $apiErr";
    }
}
?>

<!-- UI -->
<div style="
    max-width:420px;
    margin:28px auto;
    padding:20px;
    background: var(--surface-bg);
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.06);
    font-family: Segoe UI,Roboto,Arial,sans-serif;
">
    <h1 style="margin:0 0 14px 0;font-size:22px">Login</h1>

    <?php if($flash): ?>
        <div style="padding: 10px; background: var(--flash-error-bg); border: 1px solid var(--flash-error-border); margin-bottom: 15px; color: var(--flash-text);">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <form method="post" id="loginForm" style="display:flex;flex-direction:column;gap:10px">
        <label>Email
            <input type="email" name="email" placeholder="Enter email" required
                    style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-top:6px;">
        </label>

        <label>Password
            <div style="position:relative;">
                <input type="password"
                    id="passwordField"
                    name="password"
                    placeholder="Enter password"
                    required
                    style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-top:6px;">

                <span id="togglePassword"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                        cursor:pointer;color:#555;font-size:14px;">
                    👁
                </span>
            </div>
        </label>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
            <a href="register.php" style="font-size:13px;color:#0073e6;text-decoration:none">Register</a>
            <button type="submit"
                    id="loginBtn"
                    style="background:#0073e6;color:#fff;border:0;padding:10px 16px;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:8px;">
                <span id="btnText">Login</span>
                <span id="spinner" style="display:none;">
                    <svg style="width:14px;height:14px;animation:spin 1s linear infinite;" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"/>
                    </svg>
                </span>
            </button>
        </div>
    </form>
</div>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
// Toggle password visibility
document.getElementById("togglePassword").addEventListener("click", function () {
    const field = document.getElementById("passwordField");
    field.type = field.type === "password" ? "text" : "password";
});

// Handle form submission with button disable
document.getElementById("loginForm").addEventListener("submit", function(e) {
    const loginBtn = document.getElementById("loginBtn");
    const btnText = document.getElementById("btnText");
    const spinner = document.getElementById("spinner");
    
    // Disable button immediately
    loginBtn.disabled = true;
    loginBtn.style.opacity = "0.7";
    loginBtn.style.cursor = "not-allowed";
    
    // Change text and show spinner
    btnText.textContent = "Logging in...";
    spinner.style.display = "inline-block";
    
    // Note: Form will continue to submit normally
    // The disabled state prevents double-submission
});
</script>

<?php require_once "footer.php"; ?>
