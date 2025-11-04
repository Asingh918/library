<?php
require_once('../authenticate.php');
require_once('../connect.php');

// Get sorting parameter
$sort = $_GET['sort'] ?? 'title';
$allowed_sorts = ['title', 'created_at', 'updated_at'];
if (!in_array($sort, $allowed_sorts)):
    $sort = 'title';
endif;

// Fetch all books with author and category info
$query = "SELECT b.*, a.name AS author_name, c.name AS category_name 
          FROM books b
          LEFT JOIN authors a ON b.author_id = a.id
          LEFT JOIN categories c ON b.category_id = c.id
          ORDER BY b.$sort ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library CMS - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .sort-indicator {
            background: #198754;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>📚 Library CMS - Admin Dashboard</h1>
            <p class="mb-0">Manage your library's book collection</p>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Books Management</h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="create.php" class="btn btn-success">+ Add New Book</a>
                <a href="categories.php" class="btn btn-primary">Manage Categories</a>
                <a href="authors.php" class="btn btn-info">Manage Authors</a>
                <a href="comments.php" class="btn btn-warning">Moderate Reviews</a>
            </div>
        </div>

        <!-- Sorting Options -->
        <div class="card mb-4">
            <div class="card-body">
                <strong>Sort by:</strong>
                <a href="?sort=title" class="btn btn-sm <?= $sort === 'title' ? 'btn-success' : 'btn-outline-secondary' ?>">
                    Title <?php if ($sort === 'title'): ?><span class="sort-indicator">Active</span><?php endif; ?>
                </a>
                <a href="?sort=created_at" class="btn btn-sm <?= $sort === 'created_at' ? 'btn-success' : 'btn-outline-secondary' ?>">
                    Created Date <?php if ($sort === 'created_at'): ?><span class="sort-indicator">Active</span><?php endif; ?>
                </a>
                <a href="?sort=updated_at" class="btn btn-sm <?= $sort === 'updated_at' ? 'btn-success' : 'btn-outline-secondary' ?>">
                    Updated Date <?php if ($sort === 'updated_at'): ?><span class="sort-indicator">Active</span><?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Books List -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Year</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= htmlspecialchars($book['id']) ?></td>
                        <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                        <td><?= htmlspecialchars($book['author_name']) ?></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($book['category_name']) ?></span></td>
                        <td><?= htmlspecialchars($book['isbn'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($book['publication_year'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($book['availability'] === 'Available'): ?>
                                <span class="badge bg-success">Available</span>
                            <?php elseif ($book['availability'] === 'Checked Out'): ?>
                                <span class="badge bg-danger">Checked Out</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Reserved</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this book?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info mt-4">
            <strong>Total Books:</strong> <?= count($books) ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>