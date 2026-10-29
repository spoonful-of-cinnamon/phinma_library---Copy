
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/connection.php";

$sql = "SELECT * FROM students ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students - PHINMA Library</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h1>Student Records</h1>

    <a href="add.php">Add New Student</a> |
    <a href="../dashboard.php">Back to Dashboard</a>

    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Student Number</th>
            <th>Full Name</th>
            <th>Course</th>
            <th>Year Level</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>

        <?php while ($student = $result->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$student["id"] ?></td>
            <td><?= htmlspecialchars($student["student_id"]) ?></td>
            <td><?= htmlspecialchars($student["full_name"]) ?></td>
            <td><?= htmlspecialchars($student["course"]) ?></td>
            <td><?= htmlspecialchars($student["year_level"]) ?></td>
            <td><?= htmlspecialchars($student["email"] ?? "") ?></td>
            <td>
                <a href="edit.php?id=<?= (int)$student["id"] ?>">Edit</a>

                <form action="delete.php" method="POST"
                      style="display:inline;"
                      onsubmit="return confirm('Delete this student?');">
                    <input type="hidden" name="id"
                           value="<?= (int)$student["id"] ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
