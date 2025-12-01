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
<div style="max-width:420px;margin:28px auto;padding:20px;border-radius:8px;border:1px solid #e3e3e3;background:#ffffff;box-shadow:0 6px 18px rgba(0,0,0,0.04);font-family:Segoe UI,Roboto,Arial,sans-serif">
    <h1 style="margin:0 0 14px 0;font-size:22px">Register</h1>

    <?php if($flash): ?>
        <div style="padding:10px;background:#ffe6e6;border:1px solid #f5b7b7;margin-bottom:15px;">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="display:flex;flex-direction:column;gap:10px">

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

        <script>
        document.getElementById("togglePassword").addEventListener("click", function () {
            const field = document.getElementById("passwordField");
            field.type = field.type === "password" ? "text" : "password";
        });
        </script>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
            <a href="login.php" style="font-size:13px;color:#0073e6;text-decoration:none">Back to Login</a>
            <button type="submit"
                    style="background:#0073e6;color:#fff;border:0;padding:10px 16px;border-radius:6px;cursor:pointer;">
                Register
            </button>
        </div>
    </form>
</div>

<?php require_once "footer.php"; ?>