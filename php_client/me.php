<?php
require_once "header.php";

$flash = null;

// get profile
$res = callAPI("GET", "$authBase/me");
$profile = $res['json'] ?? null;

// update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $update = [
        "name"  => $_POST['name'] ?? "",
        "email" => $_POST['email'] ?? "",
    ];

    // allow empty password - don't show original password
    if (!empty($_POST['password'])) {
        $update["password"] = $_POST["password"];
    }

    $uRes = callAPI("PUT", "$authBase/me", $update);

    if (isset($uRes['json']['user'])) {
        $flash = $uRes['json']['message'] ?? "Profile updated successfully.";

        // Refresh profile
        $res = callAPI("GET", "$authBase/me");
        $profile = $res['json'] ?? $profile;

    } else {
        $flash = "Update failed: " . ($uRes['json']['error'] ?? $uRes['raw'] ?? "Unknown error");
    }
}
?>


<!-- UI -->
<div style="
    max-width:600px;
    margin:20px auto;
    padding:20px;
    background: var(--surface-bg);
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.06);
    font-family: Segoe UI,Roboto,Arial,sans-serif;
">

    <h2>My Profile</h2>

    <?php if ($flash): ?>
    <div style="padding:10px;margin-bottom:12px;border-radius:6px;
            border:1px solid <?= strpos($flash, 'failed') !== false ? 'var(--flash-error-border)' : 'var(--flash-success-border)' ?>;
            background: <?= strpos($flash, 'failed') !== false ? 'var(--flash-error-bg)' : 'var(--flash-success-bg)' ?>;
            color: var(--flash-text);
        ">
        <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>


    <?php if ($profile): ?>
        <div style="margin-bottom:20px;font-size:14px;color:var(--text-muted);">
            <p><strong>ID:</strong> <?= htmlspecialchars($profile['id']) ?></p>
            <p><strong>Created:</strong> <?= htmlspecialchars($profile['created_at']) ?></p>
            <p><strong>Updated:</strong> <?= htmlspecialchars($profile['updated_at']) ?></p>
        </div>

        <!-- Editable section -->
        <form method="post" style="display:flex;flex-direction:column;gap:18px;">
        <input type="hidden" name="update" value="1">

        <!-- Name -->
        <div style="display:flex;align-items:center;gap:20px;">
            <label style="width:90px;">Name</label>
            <input type="text" name="name", id="nameField"
                value="<?= htmlspecialchars($profile['name']) ?>"
                style="flex:1;padding:10px;border:1px solid #ccc;border-radius:6px;">
        </div>

        <!-- Email -->
        <div style="display:flex;align-items:center;gap:20px;">
            <label style="width:90px;">Email</label>
            <input type="text" name="email"
                value="<?= htmlspecialchars($profile['email']) ?>"
                style="flex:1;padding:10px;border:1px solid #ccc;border-radius:6px;">
        </div>

        <!-- Password -->
        <div style="display:flex;align-items:center;gap:20px;">
            <label style="width:90px;">Password</label>

            <div style="flex:1;position:relative;">
                <input type="password"
                    id="passwordField"
                    name="password"
                    placeholder="Enter new password"
                    style="width:95%;padding:10px;border:1px solid #ccc;border-radius:6px;">

                <span id="togglePassword"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                        cursor:pointer;color:#555;font-size:14px;">
                    👁
                </span>
            </div>
        </div>

        <script>
        document.getElementById("togglePassword").addEventListener("click", function () {
            const field = document.getElementById("passwordField");
            field.type = field.type === "password" ? "text" : "password";
        });
        </script>


        <button type="submit"
                style="background:#0073e6;color:white;padding:10px;border:0;border-radius:6px;width:150px; cursor: pointer;">
            Update Profile
        </button>
    </form>
        
    <?php endif; ?>
</div>

<script>
document.querySelector("form").addEventListener("submit", function () {
    const nameField = document.getElementById("nameField");

    let v = nameField.value.trim().toLowerCase();

    // Capitalize first letter + letters after spaces
    v = v.charAt(0).toUpperCase() + v.slice(1);
    v = v.replace(/\s+\w/g, s => s.toUpperCase());

    nameField.value = v;
});
</script>



<?php require_once "footer.php"; ?>
