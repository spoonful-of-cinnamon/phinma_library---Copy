
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
    header("Location: index.php?message=" .
        urlencode("Invalid borrowing record."));
    exit;
}

try {
    $conn->begin_transaction();

    // Lock the borrowing record.
    $stmt = $conn->prepare(
        "SELECT book_id, status
         FROM borrow_records
         WHERE id = ?
         FOR UPDATE"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$record) {
        throw new Exception("Borrowing record not found.");
    }

    if ($record["status"] !== "Borrowed") {
        throw new Exception("This book has already been returned.");
    }

    $bookId = (int)$record["book_id"];

    // Mark the borrowing record as returned.
    $returnDate = date("Y-m-d");

    $stmt = $conn->prepare(
        "UPDATE borrow_records
         SET status = 'Returned', return_date = ?
         WHERE id = ? AND status = 'Borrowed'"
    );
    $stmt->bind_param("si", $returnDate, $id);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new Exception("Unable to update the borrowing record.");
    }

    $stmt->close();

    // Restore the book's available quantity.
    $stmt = $conn->prepare(
        "UPDATE books
         SET available_quantity = available_quantity + 1
         WHERE id = ? AND available_quantity < quantity"
    );
    $stmt->bind_param("i", $bookId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new Exception("Unable to update book availability.");
    }

    $stmt->close();
    $conn->commit();

    header("Location: index.php?message=" .
        urlencode("Book returned successfully."));
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    $message = $e instanceof mysqli_sql_exception
        ? "A database error occurred while returning the book."
        : $e->getMessage();

    header("Location: index.php?message=" . urlencode($message));
    exit;
}
?>
