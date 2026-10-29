
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/connection.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: index.php");
    exit;
}

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
            "UPDATE students
             SET student_id = ?, full_name = ?, course = ?,
                 year_level = ?, email = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssssi",
            $student_id,
            $full_name,
            $course,
            $year_level,
            $email,
            $id
        );

        try {
            $stmt->execute();
            $stmt->close();
            header("Location: index.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            $error = "Could not update student. Check if the student number is already used.";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h1>Edit Student</h1>

    <?php if ($error !== ""): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Student Number *</label><br>
        <input type="text" name="student_id"
               value="<?= htmlspecialchars($student["student_id"]) ?>" required><br><br>

        <label>Full Name *</label><br>
        <input type="text" name="full_name"
               value="<?= htmlspecialchars($student["full_name"]) ?>" required><br><br>

        <label>Course *</label><br>
        <input type="text" name="course"
               value="<?= htmlspecialchars($student["course"]) ?>" required><br><br>

        <label>Year Level *</label><br>
        <select name="year_level" required>
            <?php for ($year = 1; $year <= 5; $year++): ?>
                <option value="<?= $year ?>"
                    <?= (string)$student["year_level"] === (string)$year ? "selected" : "" ?>>
                    <?= $year ?><?= $year === 1 ? "st" : ($year === 2 ? "nd" : ($year === 3 ? "rd" : "th")) ?> Year
                </option>
            <?php endfor; ?>
        </select><br><br>

        <label>Email (optional)</label><br>
        <input type="email" name="email"
               value="<?= htmlspecialchars($student["email"] ?? "") ?>"><br><br>

        <button type="submit">Update Student</button>
        <a href="index.php">Cancel</a>
    </form>
</body>
</html>
