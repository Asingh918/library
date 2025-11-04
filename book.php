<?php
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
                        <p class="lead"><?= nl2br(htmlspecialchars($book['description'])) ?></p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>