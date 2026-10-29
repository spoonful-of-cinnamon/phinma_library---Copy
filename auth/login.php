<?php
session_start();

require_once __DIR__ . "/../config/connection.php";

if (isset($_SESSION["user_id"])) {
    header("Location: ../dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, username, password, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            header("Location: ../dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHINMA Library - Login</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <main class="login-page">
        <div class="login-card">

            <div class="login-logo">P</div>

            <h1>PHINMA Library</h1>
            <p class="subtitle">
                Library Management System
            </p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    autocomplete="username"
                    required
                >

                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button type="submit" class="btn-block">
                    Login to Dashboard
                </button>
            </form>

            <p class="subtitle" style="text-align: center; margin-top: 24px;">
                PHINMA Library Management System
            </p>

        </div>
    </main>

</body>
</html>
