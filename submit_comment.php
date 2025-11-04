<?php
require_once('connect.php');

$errors = [];
$success = false;

if ($_POST):
    // Sanitize and validate input
    $book_id = filter_var($_POST['book_id'] ?? 0, FILTER_VALIDATE_INT);
    $username = trim($_POST['username'] ?? '');
    $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment'] ?? '');

    // Validation
    if (!$book_id || $book_id <= 0):
        $errors[] = "Invalid book ID.";
    endif;

    if (empty($username)):
        $errors[] = "Name is required.";
    elseif (strlen($username) > 100):
        $errors[] = "Name must be less than 100 characters.";
    endif;

    if (!$rating || $rating < 1 || $rating > 5):
        $errors[] = "Please select a valid rating (1-5 stars).";
    endif;

    if (empty($comment)):
        $errors[] = "Review comment is required.";
    elseif (strlen($comment) < 10):
        $errors[] = "Review must be at least 10 characters long.";
    endif;

    // If no errors, insert comment
    if (empty($errors)):
        // Create a temporary user or use guest user
        // For simplicity, we'll create/reuse a guest user entry
        $guest_query = "INSERT INTO users (username, password, email, role) 
                        VALUES (:username, '', :email, 'user') 
                        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
        $stmt = $db->prepare($guest_query);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':email', $username . '@guest.local');
        $stmt->execute();
        $user_id = $db->lastInsertId();

        // If update occurred, get the existing user_id
        if ($user_id == 0):
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $stmt->bindValue(':username', $username);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $result['id'];
        endif;

        // Insert the review
        $query = "INSERT INTO reviews (book_id, user_id, rating, comment, status) 
                  VALUES (:book_id, :user_id, :rating, :comment, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':book_id', $book_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindValue(':comment', $comment);

        if ($stmt->execute()):
            $success = true;
        else:
            $errors[] = "Database error occurred while submitting your review.";
        endif;
    endif;
endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review - City Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        footer {
            background: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container">
            <h1>📝 Submit Review</h1>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4>✅ Review Submitted Successfully!</h4>
                        <p>Thank you for your review! Your comment has been submitted and is pending moderation. It will appear on the book page once approved by our administrators.</p>
                        <a href="book.php?id=<?= htmlspecialchars($book_id) ?>" class="btn btn-primary">Back to Book</a>
                        <a href="index.php" class="btn btn-secondary">Browse More Books</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4>❌ Error Submitting Review</h4>
                        <p>Please fix the following errors:</p>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="book.php?id=<?= htmlspecialchars($book_id) ?>" class="btn btn-secondary">Go Back</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> City Library, Winnipeg, Manitoba. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>