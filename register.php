<?php
session_start();
require_once('connect.php');

$errors = [];
$success = false;

// Redirect if already logged in
if (isset($_SESSION['user_id'])):
    header('Location: index.php');
    exit();
endif;

if ($_POST):
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validation
    if (empty($username)):
        $errors[] = "Username is required.";
    elseif (strlen($username) < 3):
        $errors[] = "Username must be at least 3 characters.";
    endif;
    
    if (empty($email)):
        $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $errors[] = "Invalid email format.";
    endif;
    
    if (empty($password)):
        $errors[] = "Password is required.";
    elseif (strlen($password) < 6):
        $errors[] = "Password must be at least 6 characters.";
    endif;
    
    if ($password !== $password_confirm):
        $errors[] = "Passwords do not match.";
    endif;
    
    // Check if username exists
    if (empty($errors)):
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        if ($stmt->fetch()):
            $errors[] = "Username is already taken.";
        endif;
    endif;
    
    // Check if email exists
    if (empty($errors)):
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()):
            $errors[] = "Email is already registered.";
        endif;
    endif;
    
    // Insert user
    if (empty($errors)):
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'user')";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password', $hashed_password);
        
        if ($stmt->execute()):
            $success = true;
        else:
            $errors[] = "Error creating account. Please try again.";
        endif;
    endif;
endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - City Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        .register-card {
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
    <div class="register-header">
        <div class="container text-center">
            <h1>📝 Register</h1>
            <p class="mb-0">Create your City Library account</p>
        </div>
    </div>

    <div class="container">
        <div class="card register-card">
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4>✅ Registration Successful!</h4>
                        <p>Your account has been created. You can now log in.</p>
                        <a href="login.php" class="btn btn-success">Go to Login</a>
                    </div>
                <?php else: ?>
                    <h3 class="card-title text-center mb-4">Create Account</h3>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                            <div class="form-text">Minimum 3 characters</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-2">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-secondary">Login Here</a>
                    </div>

                    <div class="text-center mt-3">
                        <a href="index.php" class="text-muted">← Back to Homepage</a>
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