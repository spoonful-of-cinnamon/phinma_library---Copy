
<?php
require_once __DIR__ . "/../config/connection.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid book ID.");
}

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    exit("Book not found.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isbn = trim($_POST["isbn"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $publisher = trim($_POST["publisher"] ?? "");
    $yearInput = trim($_POST["publication_year"] ?? "");
    $categoryInput = $_POST["category_id"] ?? "";
    $quantityInput = $_POST["quantity"] ?? "";
    $shelf = trim($_POST["shelf_location"] ?? "");

    if ($title === "" || $author === "") {
        $error = "Title and author are required.";
    } elseif (
        ($yearInput !== "" &&
            (!ctype_digit($yearInput) ||
             (int)$yearInput < 1901 ||
             (int)$yearInput > 2155))
        || !ctype_digit((string)$quantityInput)
        || (int)$quantityInput < 0
        || ($categoryInput !== "" &&
            !ctype_digit((string)$categoryInput))
    ) {
        $error = "Please enter valid year, category, and quantity values.";
    } else {
        $year = $yearInput === "" ? null : (int)$yearInput;
        $categoryId = $categoryInput === "" ? null : (int)$categoryInput;
        $quantity = (int)$quantityInput;

        if ($categoryId !== null) {
            $check = $conn->prepare(
                "SELECT id FROM categories WHERE id = ?"
            );
            $check->bind_param("i", $categoryId);
            $check->execute();
            $validCategory = $check->get_result()->num_rows > 0;
            $check->close();

            if (!$validCategory) {
                $error = "Please select a valid category.";
            }
        }

        if ($error === "") {
            // Preserve the number of books currently on loan.
            $borrowed = (int)$book['quantity']
                      - (int)$book['available_quantity'];

            if ($quantity < $borrowed) {
                $error = "Quantity cannot be less than the number of borrowed books.";
            } else {
                $available = $quantity - $borrowed;

                $sql = "UPDATE books SET
                    isbn = ?, title = ?, author = ?, publisher = ?,
                    publication_year = ?, category_id = ?,
                    quantity = ?, available_quantity = ?,
                    shelf_location = ?
                    WHERE id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssiiiisi",
                    $isbn,
                    $title,
                    $author,
                    $publisher,
                    $year,
                    $categoryId,
                    $quantity,
                    $available,
                    $shelf,
                    $id
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: index.php?message=" .
                        urlencode("Book updated successfully."));
                    exit;
                } else {
                    $error = "Unable to update book. Check whether the ISBN already exists.";
                }

                $stmt->close();
            }
        }
    }

    // Keep the submitted values in the form if validation fails.
    $book = array_merge($book, [
        "isbn" => $isbn,
        "title" => $title,
        "author" => $author,
        "publisher" => $publisher,
        "publication_year" => $yearInput,
        "category_id" => $categoryInput,
        "quantity" => $quantityInput,
        "shelf_location" => $shelf
    ]);
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
    <title>Edit Book</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<h1>Edit Book</h1>

<?php if ($error !== ""): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>ISBN:</label><br>
    <input type="text" name="isbn"
        value="<?= htmlspecialchars($book['isbn'] ?? '') ?>"><br><br>

    <label>Title:</label><br>
    <input type="text" name="title" required
        value="<?= htmlspecialchars($book['title']) ?>"><br><br>

    <label>Author:</label><br>
    <input type="text" name="author" required
        value="<?= htmlspecialchars($book['author']) ?>"><br><br>

    <label>Publisher:</label><br>
    <input type="text" name="publisher"
        value="<?= htmlspecialchars($book['publisher'] ?? '') ?>"><br><br>

    <label>Publication Year:</label><br>
    <input type="number" name="publication_year"
        min="1901" max="2155"
        value="<?= htmlspecialchars((string)($book['publication_year'] ?? '')) ?>"><br><br>

    <label>Category:</label><br>
    <select name="category_id">
        <option value="">Uncategorized</option>
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?= (int)$cat['id'] ?>"
                <?= ((string)($book['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category_name']) ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Quantity:</label><br>
    <input type="number" name="quantity" min="0" required
        value="<?= htmlspecialchars((string)$book['quantity']) ?>"><br><br>

    <label>Shelf Location:</label><br>
    <input type="text" name="shelf_location"
        value="<?= htmlspecialchars($book['shelf_location'] ?? '') ?>"><br><br>

    <button type="submit">Update Book</button>
    <a href="index.php">Cancel</a>
</form>

</body>
</html>
