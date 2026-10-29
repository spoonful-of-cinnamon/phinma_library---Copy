
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/connection.php";

$sql = "SELECT br.id, br.borrow_date, br.due_date,
               br.return_date, br.status,
               s.full_name AS student_name,
               s.student_id AS student_number,
               b.title AS book_title
        FROM borrow_records br
        JOIN students s ON br.student_id = s.id
        JOIN books b ON br.book_id = b.id
        ORDER BY br.id DESC";

$result = $conn->query($sql);

if (!$result) {
    exit("Unable to load borrowing records.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing Records - PHINMA Library</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<h1>Borrowing Records</h1>

<p>Welcome, <?= htmlspecialchars($_SESSION["full_name"]) ?>!</p>

<p>
    <a href="borrow_book.php">+ Borrow a Book</a> |
    <a href="../books/index.php">Manage Books</a> |
    <a href="../dashboard.php"> Back to Dashboard</a>
</p>

<?php if (isset($_GET["message"])): ?>
    <p><?= htmlspecialchars($_GET["message"]) ?></p>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Record ID</th>
            <th>Student ID</th>
            <th>Student Name</th>
            <th>Book</th>
            <th>Borrow Date</th>
            <th>Due Date</th>
            <th>Return Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($record = $result->fetch_assoc()): ?>
        <?php
        $status = $record["status"];

        if (
            $status === "Borrowed" &&
            $record["due_date"] < date("Y-m-d")
        ) {
            $status = "Overdue";
        }
        ?>
        <tr>
            <td><?= (int)$record["id"] ?></td>
            <td><?= htmlspecialchars($record["student_number"]) ?></td>
            <td><?= htmlspecialchars($record["student_name"]) ?></td>
            <td><?= htmlspecialchars($record["book_title"]) ?></td>
            <td><?= htmlspecialchars($record["borrow_date"]) ?></td>
            <td><?= htmlspecialchars($record["due_date"]) ?></td>
            <td><?= htmlspecialchars($record["return_date"] ?? "Not returned") ?></td>
            <td><?= htmlspecialchars($status) ?></td>
            <td>
                <?php if ($record["status"] === "Borrowed"): ?>
                    <form action="return_book.php" method="POST"
                          onsubmit="return confirm('Confirm this book has been returned?');">
                        <input type="hidden" name="id"
                               value="<?= (int)$record["id"] ?>">
                        <button type="submit">Return Book</button>
                    </form>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
