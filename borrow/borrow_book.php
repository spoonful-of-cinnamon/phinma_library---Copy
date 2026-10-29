
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentId = filter_input(INPUT_POST, "student_id", FILTER_VALIDATE_INT);
    $bookId = filter_input(INPUT_POST, "book_id", FILTER_VALIDATE_INT);
    $dueDate = trim($_POST["due_date"] ?? "");

    $validDate = DateTime::createFromFormat("!Y-m-d", $dueDate);

    if (
        !$studentId || $studentId < 1 ||
        !$bookId || $bookId < 1 ||
        !$validDate || $validDate->format("Y-m-d") !== $dueDate ||
        $dueDate < date("Y-m-d")
    ) {
        $error = "Please select a student, book, and valid due date.";
    } else {
        try {
            $conn->begin_transaction();

            // Check that the student exists.
            $stmt = $conn->prepare(
                "SELECT id FROM students WHERE id = ?"
            );
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$student) {
                throw new Exception("Student not found.");
            }

            // Lock the book row to prevent over-borrowing.
            $stmt = $conn->prepare(
                "SELECT available_quantity
                 FROM books WHERE id = ? FOR UPDATE"
            );
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $book = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$book) {
                throw new Exception("Book not found.");
            }

            if ((int)$book["available_quantity"] < 1) {
                throw new Exception("This book is currently unavailable.");
            }

            $borrowDate = date("Y-m-d");
            $status = "Borrowed";
            $userId = (int)$_SESSION["user_id"];

            // Create the borrowing record.
            $stmt = $conn->prepare(
                "INSERT INTO borrow_records
                 (student_id, book_id, borrow_date, due_date, status, processed_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "iisssi",
                $studentId,
                $bookId,
                $borrowDate,
                $dueDate,
                $status,
                $userId
            );
            $stmt->execute();
            $stmt->close();

            // Decrease the available stock.
            $stmt = $conn->prepare(
                "UPDATE books
                 SET available_quantity = available_quantity - 1
                 WHERE id = ? AND available_quantity > 0"
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
                urlencode("Book borrowed successfully."));
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e instanceof mysqli_sql_exception
                ? "Unable to record borrowing. Please check the database."
                : $e->getMessage();
        }
    }
}

$students = $conn->query(
    "SELECT id, student_id, full_name
     FROM students ORDER BY full_name"
);

$books = $conn->query(
    "SELECT id, title, author, available_quantity
     FROM books WHERE available_quantity > 0
     ORDER BY title"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow a Book</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<h1>Borrow a Book</h1>

<?php if ($error !== ""): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label for="student_id">Student:</label><br>
    <select name="student_id" id="student_id" required>
        <option value="">Select Student</option>
        <?php while ($student = $students->fetch_assoc()): ?>
            <option value="<?= (int)$student["id"] ?>"
                <?= (($_POST["student_id"] ?? "") == $student["id"]) ? "selected" : "" ?>>
                <?= htmlspecialchars(
                    $student["student_id"] . " - " . $student["full_name"]
                ) ?>
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label for="book_id">Book:</label><br>
    <select name="book_id" id="book_id" required>
        <option value="">Select Book</option>
        <?php while ($book = $books->fetch_assoc()): ?>
            <option value="<?= (int)$book["id"] ?>"
                <?= (($_POST["book_id"] ?? "") == $book["id"]) ? "selected" : "" ?>>
                <?= htmlspecialchars($book["title"] . " - " . $book["author"]) ?>
                (Available: <?= (int)$book["available_quantity"] ?>)
            </option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label for="due_date">Due Date:</label><br>
    <input type="date" id="due_date" name="due_date"
           min="<?= date("Y-m-d") ?>"
           value="<?= htmlspecialchars($_POST["due_date"] ?? "") ?>"
           required>
    <br><br>

    <button type="submit">Confirm Borrowing</button>
    <a href="index.php">Cancel</a>
</form>

</body>
</html>
