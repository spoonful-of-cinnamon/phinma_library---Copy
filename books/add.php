<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isbn = trim($_POST["isbn"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $publisher = trim($_POST["publisher"] ?? "");
    $publication_year = trim($_POST["publication_year"] ?? "");
    $category_id = (int) ($_POST["category_id"] ?? 0);
    $quantity = (int) ($_POST["quantity"] ?? 0);
    $shelf_location = trim($_POST["shelf_location"] ?? "");

    if ($title === "" || $author === "") {
        $error = "Book title and author are required.";
    } elseif ($quantity < 0) {
        $error = "Quantity cannot be negative.";
    } else {
        $isbn = $isbn === "" ? null : $isbn;
        $publisher = $publisher === "" ? null : $publisher;
        $publication_year = $publication_year === ""
            ? null : (int) $publication_year;
        $category_id = $category_id > 0 ? $category_id : null;
        $shelf_location = $shelf_location === ""
            ? null : $shelf_location;

        try {
            $stmt = $conn->prepare(
                "INSERT INTO books
                (isbn, title, author, publisher, publication_year,
                 category_id, quantity, available_quantity, shelf_location)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $available_quantity = $quantity;

            $stmt->bind_param(
                "ssssiiiis",
                $isbn,
                $title,
                $author,
                $publisher,
                $publication_year,
                $category_id,
                $quantity,
                $available_quantity,
                $shelf_location
            );

            $stmt->execute();
            $stmt->close();

            header("Location: index.php?success=added");
            exit;

        } catch (mysqli_sql_exception $e) {
            $error = "Could not add the book. Check the ISBN and database fields.";
        }
    }
}

$categories = $conn->query(
    "SELECT id, category_name FROM categories ORDER BY category_name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book - PHINMA Library</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<main class="main-content">


    <section class="section">
        <h1>Add New Book</h1>
        <p>Enter the book details below.</p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="isbn">ISBN</label>
            <input type="text" id="isbn" name="isbn"
                value="<?= htmlspecialchars($_POST["isbn"] ?? "") ?>">

            <label for="title">Book Title *</label>
            <input type="text" id="title" name="title" required
                value="<?= htmlspecialchars($_POST["title"] ?? "") ?>">

            <label for="author">Author *</label>
            <input type="text" id="author" name="author" required
                value="<?= htmlspecialchars($_POST["author"] ?? "") ?>">

            <label for="publisher">Publisher</label>
            <input type="text" id="publisher" name="publisher"
                value="<?= htmlspecialchars($_POST["publisher"] ?? "") ?>">

            <label for="publication_year">Publication Year</label>
            <input type="number" id="publication_year"
                name="publication_year" min="1000"
                max="<?= date("Y") + 1 ?>"
                value="<?= htmlspecialchars($_POST["publication_year"] ?? "") ?>">

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="0">No category</option>

                <?php while ($category = $categories->fetch_assoc()): ?>
                    <option value="<?= (int) $category["id"] ?>"
                        <?= (($_POST["category_id"] ?? "0") == $category["id"])
                            ? "selected" : "" ?>>
                        <?= htmlspecialchars($category["category_name"]) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="quantity">Quantity *</label>
            <input type="number" id="quantity" name="quantity"
                min="0" required
                value="<?= htmlspecialchars($_POST["quantity"] ?? "1") ?>">

            <label for="shelf_location">Shelf Location</label>
            <input type="text" id="shelf_location" name="shelf_location"
                placeholder="Example: Shelf A-01"
                value="<?= htmlspecialchars($_POST["shelf_location"] ?? "") ?>">

            <button type="submit">Add Book</button>
            <a href="index.php">Cancel</a>
        </form>
    </section>

</main>

</body>
</html>
