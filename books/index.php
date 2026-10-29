
<?php
require_once __DIR__ . "/../config/connection.php";

$sql = "SELECT books.*, categories.category_name
        FROM books
        LEFT JOIN categories ON books.category_id = categories.id
        ORDER BY books.id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to load books.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Books</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>


<h1>Library Books</h1>


<p><a href="add.php">+ Add New Book</a> |
<a href="../dashboard.php"> Back to Dashboard</a>
</p>


<?php if (isset($_GET['message'])): ?>
    <p><?= htmlspecialchars($_GET['message']) ?></p>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>ISBN</th>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Available</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($book = $result->fetch_assoc()): ?>
        <tr>
            <td><?= (int) $book['id'] ?></td>
            <td><?= htmlspecialchars($book['isbn'] ?? '') ?></td>
            <td><?= htmlspecialchars($book['title']) ?></td>
            <td><?= htmlspecialchars($book['author']) ?></td>
            <td><?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?></td>
            <td><?= (int) $book['quantity'] ?></td>
            <td><?= (int) $book['available_quantity'] ?></td>
            <td>
                <a href="edit.php?id=<?= (int) $book['id'] ?>">Edit</a>

                <form action="delete.php" method="POST"
                      onsubmit="return confirm('Are you sure you want to delete this book?');"
                      style="display:inline;">
                    <input type="hidden" name="id"
                           value="<?= (int) $book['id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
