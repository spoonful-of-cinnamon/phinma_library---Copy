
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/connection.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id FROM borrow_records WHERE student_id = ? LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$hasRecords = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($hasRecords) {
    echo "This student cannot be deleted because borrowing records exist. ";
    echo '<a href="index.php">Return to students</a>';
    exit;
}

$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: index.php");
exit;
?>
