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

    $name     = $_POST['name'] ?? "";
    $email    = $_POST['email'] ?? "";
    $password = $_POST['password'] ?? "";

    $res = callAPI("POST", "$authBase/register", [
        "name"     => $name,
        "email"    => $email,
        "password" => $password
    ]);

    if (isset($res['json']['user'])) {

        // store pretty json for login page or debugging
        $_SESSION['last_register_response_json'] =
            json_encode($res['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // redirect to login AFTER successful registration
        header("Location: login.php");
        exit;

    } else {
        $apiErr  = $res['json']['error'] ?? ($res['raw'] ?? "Unknown error");
        $flash   = "Registration Failed — $apiErr";
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
    <h1 style="margin:0 0 14px 0;font-size:22px">Register</h1>

    <?php if($flash): ?>
        <div style="padding: 10px; background: var(--flash-error-bg); border: 1px solid var(--flash-error-border); margin-bottom: 15px; color: var(--flash-text);">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="display:flex;flex-direction:column;gap:10px" id="registerForm">

        <label>Name
            <input type="text" name="name" placeholder="Enter your name"
                    style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-top:6px;">
        </label>

        <label>Email
            <input type="text" name="email" placeholder="Enter email"
                    style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-top:6px;">
        </label>

        <label>Password
        <div style="flex:1;position:relative;">
            <input type="password"
                    id="passwordField"
                    name="password"
                    placeholder="Enter password"
                    style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;">
                <span id="togglePassword"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                        cursor:pointer;color:#555;font-size:14px;">
                    👁
                </span>
        </div>
        </label>

        <div id="strengthStatus" style="font-size:13px;margin-top:4px;min-height:18px;"></div>
        

        <label>Confirm Password
        <div style="flex:1;position:relative;">
            <input type="password"
                    id="confirmPasswordField"
                    placeholder="Confirm password"
                    style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;">
                <span id="toggleConfirmPassword"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                        cursor:pointer;color:#555;font-size:14px;">
                    👁
                </span>
        </div>
        </label>

        <div id="matchStatus" style="font-size:13px;margin-top:-5px;min-height:18px;"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
            <a href="login.php" style="font-size:13px;color:#0073e6;text-decoration:none">Back to Login</a>
            <button type="submit"
                    id="submitBtn"
                    disabled
                    style="background:#0073e6;color:#fff;border:0;padding:10px 16px;border-radius:6px;cursor:pointer;opacity:0.5;">
                <span id="btnText">Create Account</span>
                <span id="spinner" style="display:none;margin-left:6px;vertical-align:middle;">
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

document.getElementById("toggleConfirmPassword").addEventListener("click", function () {
    const field = document.getElementById("confirmPasswordField");
    field.type = field.type === "password" ? "text" : "password";
});

// Handle form submission with button disable
document.getElementById("registerForm").addEventListener("submit", function(e) {
    const loginBtn = document.getElementById("submitBtn");
    const btnText = document.getElementById("btnText");
    const spinner = document.getElementById("spinner");
    
    // Disable button immediately
    loginBtn.disabled = true;
    loginBtn.style.opacity = "0.7";
    loginBtn.style.cursor = "not-allowed";
    
    // Change text and show spinner
    btnText.textContent = "Creating Account...";
    spinner.style.display = "inline-block";
});

function checkPasswordMatch() {
    /**
    Validates password and confirm-password fields in real-time.
    
    Behavior:
    - If confirm password is empty → clears message and disables the submit button.
    - If both passwords match → shows "Passwords match" (green) and enables submit button.
    - If they do not match → shows "Passwords do not match" (red) and disables submit button.
    
    UI Updated:
    - #matchStatus text and color
    - #submitBtn disabled state, opacity, and cursor
     */
    const password = document.getElementById("passwordField").value;
    const confirmPassword = document.getElementById("confirmPasswordField").value;
    const matchStatus = document.getElementById("matchStatus");
    const submitBtn = document.getElementById("submitBtn");

    // If confirm password field is empty, clear status
    if (confirmPassword === "") {
        matchStatus.textContent = "";
        submitBtn.disabled = true;
        submitBtn.style.opacity = "0.5";
        submitBtn.style.cursor = "not-allowed";
        return;
    }

    // Check if passwords match
    if (password === confirmPassword && password !== "") {
        matchStatus.textContent = "Passwords match";
        matchStatus.style.color = "#28a745";
        submitBtn.disabled = false;
        submitBtn.style.opacity = "1";
        submitBtn.style.cursor = "pointer";
    } else {
        matchStatus.textContent = "Passwords do not match";
        matchStatus.style.color = "#dc3545";
        submitBtn.disabled = true;
        submitBtn.style.opacity = "0.5";
        submitBtn.style.cursor = "not-allowed";
    }
}

// Add event listeners for real-time validation
document.getElementById("passwordField").addEventListener("input", checkPasswordMatch);
document.getElementById("confirmPasswordField").addEventListener("input", checkPasswordMatch);

// Prevent form submission if passwords don't match
document.getElementById("registerForm").addEventListener("submit", function(e) {
    const password = document.getElementById("passwordField").value;
    const confirmPassword = document.getElementById("confirmPasswordField").value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert("Passwords do not match!");
    }
});
</script>

<script>
function checkPasswordStrength() {
    /**
    Checks password strength based on character composition:
    - Numbers only or letters only = Very Weak
    - Numbers + letters = Weak
    - Numbers + letters + capital = Moderate
    - Numbers + letters + capital + symbol = Strong
    Updates the #strengthStatus element with color-coded text.
     */
    const password = document.getElementById("passwordField").value;
    const strengthStatus = document.getElementById("strengthStatus");

    if (!password) {
        strengthStatus.textContent = "";
        return;
    }

    let hasNumber  = /[0-9]/.test(password);
    let hasAlpha   = /[a-z]/.test(password);
    let hasCapital = /[A-Z]/.test(password);
    let hasSymbol  = /[!@#$%^&*()\-_=+\[\]{}|;:'",.<>/?]/.test(password);

    let strength = "";
    let color = "";

    // RULES
    if ((hasNumber && !hasAlpha && !hasCapital && !hasSymbol) ||
        (hasAlpha && !hasNumber && !hasCapital && !hasSymbol)) {
        strength = "Very Weak";
        color = "#dc3545"; // red
    }
    else if (hasNumber && hasAlpha && !hasCapital && !hasSymbol) {
        strength = "Weak";
        color = "#dc3545"; // red
    }
    else if (hasNumber && hasAlpha && hasCapital && !hasSymbol) {
        strength = "Moderate";
        color = "#ffc107"; // yellow
    }
    else if (hasNumber && hasAlpha && hasCapital && hasSymbol) {
        strength = "Strong";
        color = "#28a745"; // green
    }
    else {
        // fallback if combination is irregular
        strength = "Weak";
        color = "#dc3545";
    }

    strengthStatus.textContent = "Strength: " + strength;
    strengthStatus.style.color = color;
}

// Connect to password input
document.getElementById("passwordField").addEventListener("input", checkPasswordStrength);
</script>


<?php require_once "footer.php"; ?>