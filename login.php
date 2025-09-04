<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    body {
      background: #0d1b2a; /* Dark Blue background */
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: 'Poppins', sans-serif;
    }
    .login-card {
      background: #1b263b;
      color: #fff;
      border-radius: 15px;
      box-shadow: 0px 4px 15px rgba(0,0,0,0.3);
      padding: 40px;
      width: 100%;
      max-width: 400px;
    }
    .login-card h2 {
      margin-bottom: 25px;
      font-weight: 600;
      color: #00b4d8; /* Neon blue */
    }
    .form-control {
      background: #0d1b2a;
      border: 1px solid #415a77;
      color: #fff;
    }
    .form-control:focus {
      border-color: #00b4d8;
      box-shadow: none;
    }
    .btn-login {
      background: #00b4d8;
      color: #fff;
      font-weight: 600;
      border-radius: 8px;
      transition: 0.3s;
    }
    .btn-login:hover {
      background: #0096c7;
    }
    .company-logo {
      font-size: 3rem;
      color: #00b4d8;
    }
    label {
      margin-left: 5px;
    }
  </style>
</head>
<body>

  <?php
    require_once __DIR__ . '/db/db.php';
    require_once __DIR__ . '/includes/auth.php';
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = 'Please enter username and password';
        } else {
            $stmt = $conn->prepare('SELECT id, username, password_hash, full_name, role, blocked FROM users WHERE username=?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                // Allow both password_hash() and seeded SHA2 for initial admin
                $valid = password_verify($password, $user['password_hash']) ||
                         hash('sha256', $password) === $user['password_hash'];
                if ($valid) {
                    login_user($user);
                    header('Location: index.php');
                    exit();
                }
            }
            $error = 'Invalid credentials';
        }
    }
  ?>
  <div class="login-card text-center">
    <div class="mb-4">
      <span class="company-logo"><i class="fa-brands fa-android"></i></span>
      <h4 class="mt-2">Your Company Logo</h4>
    </div>
    <h2>Log In</h2>
    <?php if (isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
      <div class="alert alert-success py-2" role="alert">Registration successful. Please sign in.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <div class="mb-3">
        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
      </div>
      <div class="mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
      </div>
      <div class="d-flex align-items-center mb-3">
        <input type="checkbox" id="remember_me">
        <label for="remember_me" class="text-light">Remember Me!</label>
      </div>
      <button type="submit" class="btn btn-login w-100 mb-2">Sign in</button>
    </form>
    <div class="mt-2"><a href="register.php" class="text-decoration-none" style="color:#00b4d8">Create an account</a></div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
