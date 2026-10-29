
<?php
require_once __DIR__ . "/config/connection.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($full_name === "" || $username === "" || $password === "") {
        $message = "Please fill in all fields.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        try {
            $check = $conn->prepare(
                "SELECT id FROM users WHERE username = ? LIMIT 1"
            );
            $check->bind_param("s", $username);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $message = "That username already exists. Try another.";
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $role = "admin";

                $stmt = $conn->prepare(
                    "INSERT INTO users (full_name, username, password, role)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssss",
                    $full_name,
                    $username,
                    $hashedPassword,
                    $role
                );

                $stmt->execute();
                $stmt->close();

                $message = "Admin account created! You can now log in.";
                $success = true;
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Could not create account. Check your users table.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - PHINMA Library</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="login-page">
        <div class="login-card">
            <div class="login-logo">P</div>

            <h1>Create Admin Account</h1>
            <p class="subtitle">
                Set up your PHINMA Library administrator.
            </p>

            <?php if ($message !== ""): ?>
                <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        required
                    >

                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        autocomplete="username"
                        required
                    >

                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button type="submit" class="btn-block">
                        Create Admin
                    </button>
                </form>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-block">
                    Go to Login
                </a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
