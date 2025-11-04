<?php
require_once('connect.php');

// Validate and sanitize category ID from GET
$category_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$category_id || $category_id <= 0):
    die("Invalid category ID.");
endif;

// Fetch the category info
$query = "SELECT * FROM categories WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
$stmt->execute();
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category):
    die("Category not found.");
endif;

// Fetch all books in this category
$query = "SELECT b.*, a.name AS author_name, c.name AS category_name 
          FROM books b
          LEFT JOIN authors a ON b.author_id = a.id
          LEFT JOIN categories c ON b.category_id = c.id
          WHERE b.category_id = :category_id
          ORDER BY b.title ASC";
$stmt = $db->prepare($query);
$stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories for the sidebar
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['name']) ?> - City Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .book-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .availability-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .category-sidebar {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
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
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb text-white">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($category['name']) ?></li>
                </ol>
            </nav>
            <h1 class="display-3">📚 <?= htmlspecialchars($category['name']) ?></h1>
            <?php if ($category['description']): ?>
                <p class="lead mb-0"><?= htmlspecialchars($category['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Sidebar with Categories -->
            <div class="col-md-3 mb-4">
                <div class="category-sidebar">
                    <h4 class="mb-3">Browse by Genre</h4>
                    <div class="list-group">
                        <a href="index.php" class="list-group-item list-group-item-action">
                            All Books
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="category.php?id=<?= $cat['id'] ?>" 
                               class="list-group-item list-group-item-action <?= $cat['id'] == $category_id ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content - Books Grid -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Books in <?= htmlspecialchars($category['name']) ?> (<?= count($books) ?>)</h2>
                </div>

                <?php if (empty($books)): ?>
                    <div class="alert alert-info">
                        <h4>No Books Found</h4>
                        <p>There are currently no books in this category. Check back later!</p>
                        <a href="index.php" class="btn btn-primary">Browse All Books</a>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($books as $book): ?>
                            <div class="col">
                                <div class="card book-card">
                                    <?php if ($book['availability'] === 'Available'): ?>
                                        <span class="badge bg-success availability-badge">Available</span>
                                    <?php elseif ($book['availability'] === 'Checked Out'): ?>
                                        <span class="badge bg-danger availability-badge">Checked Out</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark availability-badge">Reserved</span>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="book.php?id=<?= $book['id'] ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($book['title']) ?>
                                            </a>
                                        </h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            by <?= htmlspecialchars($book['author_name']) ?>
                                        </h6>
                                        <?php if ($book['description']): ?>
                                            <p class="card-text small">
                                                <?= htmlspecialchars(substr($book['description'], 0, 100)) ?>
                                                <?= strlen($book['description']) > 100 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <?php if ($book['isbn']): ?>
                                                <small class="text-muted">ISBN: <?= htmlspecialchars($book['isbn']) ?></small>
                                            <?php endif; ?>
                                            <?php if ($book['publication_year']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($book['publication_year']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="book.php?id=<?= $book['id'] ?>" class="btn btn-primary btn-sm mt-3 w-100">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> City Library, Winnipeg, Manitoba. All rights reserved.</p>
            <p class="mb-0 small">Serving the community with knowledge and inspiration.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>