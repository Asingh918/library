<?php
session_start();
require_once('connect.php');

$errors = [];
$success = false;
$captcha_error = false;

// Preserve form data for retry
$form_data = [
    'book_id' => $_POST['book_id'] ?? 0,
    'username' => $_POST['username'] ?? '',
    'rating' => $_POST['rating'] ?? '',
    'comment' => $_POST['comment'] ?? ''
];

if ($_POST):
    // Sanitize and validate input
    $book_id = filter_var($_POST['book_id'] ?? 0, FILTER_VALIDATE_INT);
    $username = trim($_POST['username'] ?? '');
    $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment'] ?? '');
    $captcha_input = trim($_POST['captcha'] ?? '');

    // CAPTCHA VERIFICATION - Check this FIRST
    if (empty($captcha_input)):
        $errors[] = "Please enter the security code.";
        $captcha_error = true;
    elseif (!isset($_SESSION['captcha_code'])):
        $errors[] = "CAPTCHA session expired. Please try again.";
        $captcha_error = true;
    elseif (strtoupper($captcha_input) !== strtoupper($_SESSION['captcha_code'])):
        $errors[] = "Security code is incorrect. Please try again.";
        $captcha_error = true;
    endif;

    // Only validate other fields if CAPTCHA is correct
    if (!$captcha_error):
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
        elseif (strlen($comment) > 5000):
            $errors[] = "Review must be less than 5000 characters.";
        endif;

        // If no errors, insert comment
        if (empty($errors)):
            try {
                $user_id = null;
                
                // Check if user is logged in
                if (isset($_SESSION['user_id'])):
                    $user_id = $_SESSION['user_id'];
                else:
                    // Create/get guest user
                    $guest_email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $username)) . '@guest.local';
                    
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                    $stmt->bindValue(':email', $guest_email);
                    $stmt->execute();
                    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing_user):
                        $user_id = $existing_user['id'];
                    else:
                        // Create new guest user
                        $guest_query = "INSERT INTO users (username, password, email, role) 
                                        VALUES (:username, '', :email, 'user')";
                        $stmt = $db->prepare($guest_query);
                        $stmt->bindValue(':username', $username);
                        $stmt->bindValue(':email', $guest_email);
                        
                        if ($stmt->execute()):
                            $user_id = $db->lastInsertId();
                        else:
                            $errors[] = "Error creating guest user.";
                        endif;
                    endif;
                endif;

                // Insert the review if we have a valid user_id
                if ($user_id && empty($errors)):
                    $query = "INSERT INTO reviews (book_id, user_id, rating, comment, status) 
                              VALUES (:book_id, :user_id, :rating, :comment, 'pending')";
                    $stmt = $db->prepare($query);
                    $stmt->bindValue(':book_id', $book_id, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
                    $stmt->bindValue(':comment', $comment);

                    if ($stmt->execute()):
                        $success = true;
                        // Clear CAPTCHA session after successful submission
                        unset($_SESSION['captcha_code']);
                        // Clear form data
                        $form_data = ['book_id' => $book_id, 'username' => '', 'rating' => '', 'comment' => ''];
                    else:
                        $errors[] = "Database error occurred while submitting your review.";
                    endif;
                endif;
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        endif;
    endif;
    
    // Generate new CAPTCHA for retry (if there was an error)
    if (!$success) {
        // CAPTCHA will be regenerated when form loads
    }
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
            <p class="mb-0">Thank you for sharing your thoughts!</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading">✅ Review Submitted Successfully!</h4>
                        <hr>
                        <p>Thank you for your review! Your comment has been submitted and is pending moderation.</p>
                        <p class="mb-0">It will appear on the book page once approved by our administrators.</p>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <a href="book.php?id=<?= htmlspecialchars($form_data['book_id']) ?>" class="btn btn-primary btn-lg">Back to Book</a>
                        <a href="index.php" class="btn btn-outline-secondary">Browse More Books</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">❌ <?= $captcha_error ? 'Security Check Failed' : 'Error Submitting Review' ?></h4>
                        <hr>
                        <p><strong>Please fix the following errors:</strong></p>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if ($captcha_error): ?>
                        <!-- Show form again with preserved data -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Try Again</h5>
                                <p class="text-muted">Your review has been preserved. Please complete the security check below.</p>
                                
                                <form method="POST" action="submit_comment.php">
                                    <input type="hidden" name="book_id" value="<?= htmlspecialchars($form_data['book_id']) ?>">
                                    <input type="hidden" name="username" value="<?= htmlspecialchars($form_data['username']) ?>">
                                    <input type="hidden" name="rating" value="<?= htmlspecialchars($form_data['rating']) ?>">
                                    <input type="hidden" name="comment" value="<?= htmlspecialchars($form_data['comment']) ?>">
                                    
                                    <div class="mb-3">
                                        <strong>Your Review:</strong>
                                        <div class="border rounded p-3 bg-light">
                                            <p class="mb-1"><strong>Rating:</strong> <?= str_repeat('⭐', $form_data['rating']) ?></p>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($form_data['comment'])) ?></p>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="captcha" class="form-label">Security Check *</label>
                                        <div class="card mb-2" style="width: fit-content;">
                                            <img src="captcha.php?<?= time() ?>" alt="CAPTCHA" id="captcha-image" 
                                                 class="card-img-top" style="border: 2px solid #dee2e6;">
                                            <div class="card-body p-2">
                                                <button type="button" class="btn btn-sm btn-secondary w-100" onclick="refreshCaptcha()">
                                                    🔄 Refresh Code
                                                </button>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" id="captcha" name="captcha" 
                                               placeholder="Enter the code shown above" required autocomplete="off" autofocus>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">Submit Review</button>
                                        <a href="book.php?id=<?= htmlspecialchars($form_data['book_id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-grid gap-2 mt-3">
                            <a href="book.php?id=<?= htmlspecialchars($form_data['book_id']) ?>" class="btn btn-secondary btn-lg">Go Back and Try Again</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> City Library, Winnipeg, Manitoba. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function refreshCaptcha() {
            const captchaImg = document.getElementById('captcha-image');
            captchaImg.src = 'captcha.php?' + new Date().getTime();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>