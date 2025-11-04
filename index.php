<?php
require_once('connect.php');

// Fetch all books with author and category info
$query = "SELECT b.*, a.name AS author_name, c.name AS category_name 
          FROM books b
          LEFT JOIN authors a ON b.author_id = a.id
          LEFT JOIN categories c ON b.category_id = c.id
          ORDER BY b.title ASC";
$stmt = $db->prepare($query);
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
    <title>City Library - Winnipeg, MB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
        }
        .admin-link {
            position: absolute;
            top: 10px;
            right: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .hero-section:hover .admin-link {
            opacity: 1;
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
        <a href="admin/index.php" class="admin-link btn btn-light btn-sm">Admin Login</a>
        <div class="container">
            <h1 class="display-3">📚 City Library</h1>
            <p class="lead">Winnipeg, Manitoba</p>
            <p class="mb-3">Explore our extensive collection of books</p>
            
            <!-- Search Bar -->
            <div class="row justify-content-center mt-4">
                <div class="col-md-6">
                    <form method="GET" action="search.php" class="d-flex">
                        <input type="text" class="form-control form-control-lg me-2" 
                               name="q" placeholder="Search books by title, author, ISBN..." required>
                        <button type="submit" class="btn btn-light btn-lg">🔍 Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Sidebar with Categories -->
            <div class="col-md-3 mb-4">
                <div class="category-sidebar">
                    <h4 class="mb-3">Browse by Genre</h4>
                    <div class="list-group">
                        <a href="index.php" class="list-group-item list-group-item-action active">
                            All Books
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="category.php?id=<?= $category['id'] ?>" class="list-group-item list-group-item-action">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content - Books Grid -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>All Books (<?= count($books) ?>)</h2>
                </div>

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
                                    <p class="mb-2">
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($book['category_name']) ?>
                                        </span>
                                    </p>
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