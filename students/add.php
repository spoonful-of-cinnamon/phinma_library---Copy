
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = trim($_POST["student_id"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year_level = trim($_POST["year_level"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($student_id === "" || $full_name === "" ||
        $course === "" || $year_level === "") {
        $error = "Please fill in all required fields.";
    } elseif (!in_array($year_level, ["1", "2", "3", "4", "5"])) {
        $error = "Please select a valid year level.";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO students
            (student_id, full_name, course, year_level, email)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssss",
            $student_id,
            $full_name,
            $course,
            $year_level,
            $email
        );

        try {
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Could not add student. Check if the student number already exists.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h1>Add Student</h1>

    <?php if ($error !== ""): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Student Number *</label><br>
        <input type="text" name="student_id" required><br><br>

        <label>Full Name *</label><br>
        <input type="text" name="full_name" required><br><br>

        <label>Course *</label><br>
        <input type="text" name="course" required><br><br>

        <label>Year Level *</label><br>
        <select name="year_level" required>
            <option value="">Select year level</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
        </select><br><br>

        <label>Email (optional)</label><br>
        <input type="email" name="email"><br><br>

        <button type="submit">Save Student</button>
        <a href="index.php">Cancel</a>
    </form>
</body>
</html>
