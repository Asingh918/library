<?php
session_start();
require_once('connect.php');

// Validate and sanitize ID from GET
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id || $id <= 0):
    die("Invalid book ID.");
endif;

// Fetch the book with author and category info
$query = "SELECT b.*, a.name AS author_name, a.bio AS author_bio, c.name AS category_name, c.description AS category_desc
          FROM books b
          LEFT JOIN authors a ON b.author_id = a.id
          LEFT JOIN categories c ON b.category_id = c.id
          WHERE b.id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book):
    die("Book not found.");
endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> - City Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .book-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        .book-details-card {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .info-label {
            font-weight: bold;
            color: #667eea;
        }
        .comment-card {
            border-left: 3px solid #667eea;
            background: #f8f9fa;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
        }
        .comment-form-section {
            background: #fff;
            border: 2px solid #667eea;
            border-radius: 0.5rem;
            padding: 1.5rem;
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
    <div class="book-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb text-white">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Book Details</li>
                </ol>
            </nav>
            <h1 class="display-4"><?= htmlspecialchars($book['title']) ?></h1>
            <p class="lead mb-0">by <?= htmlspecialchars($book['author_name']) ?></p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <?php if ($book['image_url'] && file_exists($book['image_url'])): ?>
                    <div class="card mb-4">
                        <img src="<?= htmlspecialchars($book['image_url']) ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($book['title']) ?> cover"
                             style="max-height: 400px; object-fit: contain; background: #f8f9fa; padding: 1rem;">
                    </div>
                <?php endif; ?>
                
                <div class="card book-details-card mb-4">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Book Information</h3>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <span class="info-label">Title:</span>
                            </div>
                            <div class="col-md-8">
                                <?= htmlspecialchars($book['title']) ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <span class="info-label">Author:</span>
                            </div>
                            <div class="col-md-8">
                                <?= htmlspecialchars($book['author_name']) ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <span class="info-label">Category:</span>
                            </div>
                            <div class="col-md-8">
                                <span class="badge bg-info text-dark">
                                    <?= htmlspecialchars($book['category_name']) ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($book['isbn']): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <span class="info-label">ISBN:</span>
                            </div>
                            <div class="col-md-8">
                                <?= htmlspecialchars($book['isbn']) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($book['publication_year']): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <span class="info-label">Publication Year:</span>
                            </div>
                            <div class="col-md-8">
                                <?= htmlspecialchars($book['publication_year']) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <span class="info-label">Availability:</span>
                            </div>
                            <div class="col-md-8">
                                <?php if ($book['availability'] === 'Available'): ?>
                                    <span class="badge bg-success fs-6">✓ Available</span>
                                <?php elseif ($book['availability'] === 'Checked Out'): ?>
                                    <span class="badge bg-danger fs-6">✗ Checked Out</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark fs-6">⏱ Reserved</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($book['description']): ?>
                        <hr class="my-4">
                        <h4>Description</h4>
                        <div class="lead"><?= $book['description'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($book['author_bio']): ?>
                <div class="card book-details-card mb-4">
                    <div class="card-body">
                        <h4>About the Author</h4>
                        <p><?= nl2br(htmlspecialchars($book['author_bio'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Comments Section -->
                <div class="card book-details-card mb-4">
                    <div class="card-body">
                        <h4 class="mb-4">📖 Reader Reviews & Comments</h4>
                        
                        <?php
                        // Fetch approved comments for this book
                        try {
                            $query = "SELECT r.*, u.username 
                                      FROM reviews r
                                      LEFT JOIN users u ON r.user_id = u.id
                                      WHERE r.book_id = :book_id AND r.status = 'approved'
                                      ORDER BY r.created_at DESC";
                            $stmt = $db->prepare($query);
                            $stmt->bindValue(':book_id', $id, PDO::PARAM_INT);
                            $stmt->execute();
                            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $comments = [];
                        }
                        ?>

                        <!-- Display Existing Comments -->
                        <?php if (empty($comments)): ?>
                            <div class="alert alert-info">
                                <strong>No reviews yet!</strong> Be the first to share your thoughts about this book.
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <h5 class="mb-3">Reviews (<?= count($comments) ?>)</h5>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-card">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong><?= htmlspecialchars($comment['username'] ?? 'Anonymous') ?></strong>
                                                <?php if ($comment['rating']): ?>
                                                    <span class="text-warning ms-2">
                                                        <?= str_repeat('★', $comment['rating']) ?>
                                                        <?= str_repeat('☆', 5 - $comment['rating']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?= date('M j, Y', strtotime($comment['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <hr class="my-4">
                        
                        <!-- Comment Form - ALWAYS VISIBLE -->
                        <div class="comment-form-section">
                            <h5 class="mb-3">✍️ Leave Your Review</h5>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <div class="alert alert-success mb-3">
                                    <small>Posting as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></small>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="submit_comment.php" id="commentForm">
                                <input type="hidden" name="book_id" value="<?= $id ?>">
                                
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Enter your name" required>
                                    <div class="form-text">
                                        💡 <a href="login.php">Login</a> or <a href="register.php">Register</a> for a faster experience!
                                    </div>
                                </div>
                                <?php else: ?>
                                <input type="hidden" name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="rating" class="form-label">Your Rating *</label>
                                    <select class="form-select form-select-lg" id="rating" name="rating" required>
                                        <option value="">-- How would you rate this book? --</option>
                                        <option value="5">⭐⭐⭐⭐⭐ Excellent (5 stars)</option>
                                        <option value="4">⭐⭐⭐⭐☆ Very Good (4 stars)</option>
                                        <option value="3">⭐⭐⭐☆☆ Good (3 stars)</option>
                                        <option value="2">⭐⭐☆☆☆ Fair (2 stars)</option>
                                        <option value="1">⭐☆☆☆☆ Poor (1 star)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="comment" class="form-label">Your Review *</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="5" 
                                              placeholder="Share your thoughts about this book... What did you like? Would you recommend it?" 
                                              required></textarea>
                                    <div class="form-text">
                                        Minimum 10 characters. Your review will be moderated before appearing on the page.
                                    </div>
                                </div>

                                <!-- CAPTCHA Verification -->
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
                                           placeholder="Enter the code shown above" required autocomplete="off">
                                    <div class="form-text">
                                        Please enter the characters you see in the image above to verify you're human.
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        📝 Submit Review
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-outline-primary">← Back to All Books</a>
                            <a href="category.php?id=<?= $book['category_id'] ?>" class="btn btn-outline-info">
                                More in <?= htmlspecialchars($book['category_name']) ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Book Statistics</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <strong>Added:</strong><br>
                                <small class="text-muted"><?= date('F j, Y', strtotime($book['created_at'])) ?></small>
                            </li>
                            <li>
                                <strong>Last Updated:</strong><br>
                                <small class="text-muted"><?= date('F j, Y', strtotime($book['updated_at'])) ?></small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> City Library, Winnipeg, Manitoba. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Function to refresh CAPTCHA image
        function refreshCaptcha() {
            const captchaImg = document.getElementById('captcha-image');
            captchaImg.src = 'captcha.php?' + new Date().getTime();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>