<?php
require_once('connect.php');

// Get and sanitize search query and category filter
$search_query = trim($_GET['q'] ?? '');
$category_filter = filter_var($_GET['category'] ?? 0, FILTER_VALIDATE_INT);
$books = [];

if (!empty($search_query)):
    // Build query based on whether category filter is applied
    if ($category_filter && $category_filter > 0):
        $query = "SELECT b.*, a.name AS author_name, c.name AS category_name 
                  FROM books b
                  LEFT JOIN authors a ON b.author_id = a.id
                  LEFT JOIN categories c ON b.category_id = c.id
                  WHERE (b.title LIKE :search 
                     OR a.name LIKE :search 
                     OR b.description LIKE :search 
                     OR b.isbn LIKE :search)
                  AND b.category_id = :category_id
                  ORDER BY b.title ASC";
        $stmt = $db->prepare($query);
        $search_param = '%' . $search_query . '%';
        $stmt->bindValue(':search', $search_param);
        $stmt->bindValue(':category_id', $category_filter, PDO::PARAM_INT);
    else:
        $query = "SELECT b.*, a.name AS author_name, c.name AS category_name 
                  FROM books b
                  LEFT JOIN authors a ON b.author_id = a.id
                  LEFT JOIN categories c ON b.category_id = c.id
                  WHERE b.title LIKE :search 
                     OR a.name LIKE :search 
                     OR b.description LIKE :search 
                     OR b.isbn LIKE :search
                  ORDER BY b.title ASC";
        $stmt = $db->prepare($query);
        $search_param = '%' . $search_query . '%';
        $stmt->bindValue(':search', $search_param);
    endif;
    
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
endif;

// Fetch all categories for the sidebar and dropdown
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - City Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .search-box {
            max-width: 700px;
            margin: 0 auto;
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
                    <li class="breadcrumb-item active" aria-current="page">Search</li>
                </ol>
            </nav>
            <h1 class="display-4">🔍 Search Books</h1>
            
            <div class="search-box mt-4">
                <form method="GET" action="search.php">
                    <div class="row g-2">
                        <div class="col-md-7">
                            <input type="text" class="form-control form-control-lg" 
                                   name="q" placeholder="Search by title, author, ISBN..." 
                                   value="<?= htmlspecialchars($search_query) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-lg" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-light btn-lg w-100">Search</button>
                        </div>
                    </div>
                </form>
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
                        <a href="index.php" class="list-group-item list-group-item-action">
                            All Books
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="category.php?id=<?= $cat['id'] ?>" class="list-group-item list-group-item-action">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content - Search Results -->
            <div class="col-md-9">
                <?php if (empty($search_query)): ?>
                    <div class="alert alert-info">
                        <h4>Start Your Search</h4>
                        <p>Enter keywords to search for books by title, author, description, or ISBN.</p>
                        <p><strong>Tip:</strong> Use the category dropdown to narrow your search results!</p>
                    </div>
                <?php elseif (empty($books)): ?>
                    <div class="alert alert-warning">
                        <h4>No Results Found</h4>
                        <p>No books matched your search for "<strong><?= htmlspecialchars($search_query) ?></strong>"
                        <?php if ($category_filter): ?>
                            in category "<strong><?php 
                                $filtered_cat = array_filter($categories, fn($c) => $c['id'] == $category_filter);
                                echo htmlspecialchars(reset($filtered_cat)['name'] ?? '');
                            ?></strong>"
                        <?php endif; ?>.</p>
                        <p>Try different keywords or <a href="search.php">search all categories</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-4">
                        Found <strong><?= count($books) ?></strong> result(s) for "<strong><?= htmlspecialchars($search_query) ?></strong>"
                        <?php if ($category_filter): ?>
                            in <strong><?php 
                                $filtered_cat = array_filter($categories, fn($c) => $c['id'] == $category_filter);
                                echo htmlspecialchars(reset($filtered_cat)['name'] ?? '');
                            ?></strong>
                        <?php endif; ?>
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