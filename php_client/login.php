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
<div style="max-width:420px;margin:28px auto;padding:20px;border-radius:8px;border:1px solid #e3e3e3;background:#ffffff;box-shadow:0 6px 18px rgba(0,0,0,0.04);font-family:Segoe UI,Roboto,Arial,sans-serif">
    <h1 style="margin:0 0 14px 0;font-size:22px">Login</h1>

    <?php if($flash): ?>
        <div style="padding: 10px; background: #ffe6e6; border: 1px solid #f5b7b7; margin-bottom: 15px;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <form method="post" style="display:flex;flex-direction:column;gap:10px">
        <label>Email
            <input type="email" name="email" placeholder="Enter email"
                    style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-top:6px;">
        </label>

            <label style="width:90px;">Password</label>

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

        <script>
        document.getElementById("togglePassword").addEventListener("click", function () {
            const field = document.getElementById("passwordField");
            field.type = field.type === "password" ? "text" : "password";
        });
        </script>


        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
            <a href="register.php" style="font-size:13px;color:#0073e6;text-decoration:none">Register</a>
            <button type="submit"
                    style="background:#0073e6;color:#fff;border:0;padding:10px 16px;border-radius:6px; cursor: pointer;">Login</button>
        </div>
    </form>
</div>

<?php require_once "footer.php"; ?>
