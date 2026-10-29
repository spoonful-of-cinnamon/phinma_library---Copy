
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

require_once __DIR__ . "/config/connection.php";

// Get library statistics
$totalBooks = (int)($conn->query(
    "SELECT COALESCE(SUM(quantity), 0) AS total FROM books"
)->fetch_assoc()["total"] ?? 0);

$availableBooks = (int)($conn->query(
    "SELECT COALESCE(SUM(available_quantity), 0) AS total FROM books"
)->fetch_assoc()["total"] ?? 0);

$totalStudents = (int)($conn->query(
    "SELECT COUNT(*) AS total FROM students"
)->fetch_assoc()["total"] ?? 0);

$borrowedBooks = (int)($conn->query(
    "SELECT COUNT(*) AS total FROM borrow_records
     WHERE status = 'Borrowed'"
)->fetch_assoc()["total"] ?? 0);

// Get recent borrowing records
$recentRecords = $conn->query(
    "SELECT br.id, s.full_name, b.title,
            br.borrow_date, br.due_date, br.status
     FROM borrow_records br
     JOIN students s ON br.student_id = s.id
     JOIN books b ON br.book_id = b.id
     ORDER BY br.id DESC
     LIMIT 5"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PHINMA Library</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<aside class="sidebar">
    <a href="dashboard.php" class="brand">PHINMA Library</a>

    <nav class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="books/index.php">Books</a>
        <a href="students/index.php">Students</a>
        <a href="borrow/index.php">Borrowing Records</a>
        <a href="auth/logout.php">Logout</a>
    </nav>
</aside>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p class="subtitle">
                Welcome back,
                <?= htmlspecialchars($_SESSION["full_name"] ?? $_SESSION["username"] ?? "User") ?>!
                Here's your library overview.
            </p>
        </div>

        <span class="badge badge-info">Library Management</span>
    </div>

    <div class="stats-grid">

        <div class="stat-card">
            <p class="stat-label">Total Book Copies</p>
            <p class="stat-value"><?= $totalBooks ?></p>
        </div>

        <div class="stat-card">
            <p class="stat-label">Available Copies</p>
            <p class="stat-value"><?= $availableBooks ?></p>
        </div>

        <div class="stat-card">
            <p class="stat-label">Registered Students</p>
            <p class="stat-value"><?= $totalStudents ?></p>
        </div>

        <div class="stat-card">
            <p class="stat-label">Currently Borrowed</p>
            <p class="stat-value"><?= $borrowedBooks ?></p>
        </div>

    </div>

    <section class="section">
        <h2>Quick Actions</h2>
        <p class="subtitle">Manage your library records.</p>

        <div class="actions">
            <a href="books/add.php" class="btn">+ Add Book</a>
            <a href="students/add.php" class="btn">+ Add Student</a>
            <a href="borrow/borrow_book.php" class="btn btn-secondary">
                Record a Borrowing
            </a>
        </div>
    </section>

    <section class="section">
        <h2>Recent Borrowing Activity</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Book</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($recentRecords && $recentRecords->num_rows > 0): ?>
                    <?php while ($record = $recentRecords->fetch_assoc()): ?>
                        <?php
                        $status = $record["status"];

                        if ($status === "Borrowed" &&
                            $record["due_date"] < date("Y-m-d")) {
                            $status = "Overdue";
                        }

                        $statusClass = match ($status) {
                            "Returned" => "status-returned",
                            "Overdue" => "status-overdue",
                            default => "status-borrowed"
                        };
                        ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($record["full_name"]) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($record["title"]) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($record["borrow_date"]) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($record["due_date"]) ?>
                            </td>
                            <td>
                                <span class="status <?= $statusClass ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No borrowing records yet.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p style="margin-top: 20px;">
            <a href="borrow/index.php" class="btn btn-secondary">
                View All Borrowing Records
            </a>
        </p>
    </section>

    <footer class="footer">
        PHINMA Library Management System
    </footer>

</main>

</body>
</html>
