
<?php
require_once __DIR__ . "/../config/connection.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php?message=" .
        urlencode("Invalid book ID."));
    exit;
}

// Do not delete books that have borrowing history.
$check = $conn->prepare(
    "SELECT id FROM borrow_records WHERE book_id = ? LIMIT 1"
);
$check->bind_param("i", $id);
$check->execute();
$hasRecords = $check->get_result()->num_rows > 0;
$check->close();

if ($hasRecords) {
    header("Location: index.php?message=" .
        urlencode("This book has borrowing records and cannot be deleted."));
    exit;
}

$stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$deleted = $stmt->affected_rows > 0;
$stmt->close();

$message = $deleted
    ? "Book deleted successfully."
    : "Book not found.";

header("Location: index.php?message=" . urlencode($message));
exit;
?>
