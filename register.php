<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/auth.php';

// Password strength validation function
function isStrongPassword($password) {
    // At least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // At least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // At least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // At least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // At least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($username === '' || $full_name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!isStrongPassword($password)) {
        $error = 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username=? OR email=?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            $error = 'Username or email already in use.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare('INSERT INTO users (username, password_hash, full_name, role, email) VALUES (?,?,?,?,?)');
            $role = 'customer';
            $insert->bind_param('sssss', $username, $hash, $full_name, $role, $email);
            if ($insert->execute()) {
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register</title>
  
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
    .register-card {
      background: #1b263b;
      color: #fff;
      border-radius: 15px;
      box-shadow: 0px 4px 15px rgba(0,0,0,0.3);
      padding: 40px;
      width: 100%;
      max-width: 450px;
    }
    .register-card h2 {
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
    .form-control::placeholder {
      color: #adb5bd;
    }
    .btn-register {
      background: #00b4d8;
      color: #fff;
      font-weight: 600;
      border-radius: 8px;
      transition: 0.3s;
    }
    .btn-register:hover {
      background: #0096c7;
    }
    .company-logo {
      font-size: 3rem;
      color: #00b4d8;
    }
    label {
      margin-left: 5px;
      color: #e9ecef;
    }
    .form-label {
      color: #e9ecef;
      font-weight: 500;
    }
    .password-strength {
      margin-top: 5px;
      font-size: 0.875rem;
    }
    .strength-weak { color: #dc3545; }
    .strength-medium { color: #ffc107; }
    .strength-strong { color: #28a745; }
    .strength-requirements {
      font-size: 0.8rem;
      margin-top: 5px;
      display: none;
    }
    .strength-requirements.show {
      display: block;
    }
    .requirement {
      display: flex;
      align-items: center;
      margin: 2px 0;
    }
    .requirement.met {
      color: #28a745;
    }
    .requirement.unmet {
      color: #dc3545;
    }
    .requirement i {
      margin-right: 5px;
      font-size: 0.75rem;
    }
  </style>
</head>
<body>

  <div class="register-card text-center">
    <div class="mb-4">
      <span class="company-logo"><i class="fa-brands fa-android"></i></span>
      <h4 class="mt-2">Your Company Logo</h4>
    </div>
    <h2>Create Account</h2>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="register.php" novalidate>
      <div class="mb-3">
        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
      </div>
      <div class="mb-3">
        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name" required>
      </div>
      <div class="mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
      </div>
      <div class="mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password " required>
        <div class="password-strength" id="password-strength"></div>
        <div class="strength-requirements" id="password-requirements">
          <div class="requirement" id="req-length">
            <i class="fas fa-circle"></i>
            <span>At least 8 characters</span>
          </div>
          <div class="requirement" id="req-uppercase">
            <i class="fas fa-circle"></i>
            <span>One uppercase letter</span>
          </div>
          <div class="requirement" id="req-lowercase">
            <i class="fas fa-circle"></i>
            <span>One lowercase letter</span>
          </div>
          <div class="requirement" id="req-number">
            <i class="fas fa-circle"></i>
            <span>One number</span>
          </div>
          <div class="requirement" id="req-special">
            <i class="fas fa-circle"></i>
            <span>One special character</span>
          </div>
        </div>
      </div>
      <div class="mb-3">
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
        <div class="password-strength" id="confirm-strength"></div>
      </div>
      <button type="submit" class="btn btn-register w-100 mb-2">Create Account</button>
    </form>
    <div class="mt-2"><a href="login.php" class="text-decoration-none" style="color:#00b4d8">Already have an account? Login</a></div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const passwordInput = document.getElementById('password');
      const confirmInput = document.getElementById('confirm_password');
      const strengthDiv = document.getElementById('password-strength');
      const confirmDiv = document.getElementById('confirm-strength');
      
      // Password strength validation
      function checkPasswordStrength(password) {
        let score = 0;
        let requirements = {
          length: password.length >= 8,
          uppercase: /[A-Z]/.test(password),
          lowercase: /[a-z]/.test(password),
          number: /[0-9]/.test(password),
          special: /[^A-Za-z0-9]/.test(password)
        };
        
        // Update requirement indicators
        updateRequirement('req-length', requirements.length);
        updateRequirement('req-uppercase', requirements.uppercase);
        updateRequirement('req-lowercase', requirements.lowercase);
        updateRequirement('req-number', requirements.number);
        updateRequirement('req-special', requirements.special);
        
        // Calculate score
        Object.values(requirements).forEach(met => {
          if (met) score++;
        });
        
        // Update strength display
        if (password.length === 0) {
          strengthDiv.textContent = '';
          strengthDiv.className = 'password-strength';
        } else if (score < 3) {
          strengthDiv.textContent = 'Weak Password';
          strengthDiv.className = 'password-strength strength-weak';
        } else if (score < 5) {
          strengthDiv.textContent = 'Medium Password';
          strengthDiv.className = 'password-strength strength-medium';
        } else {
          strengthDiv.textContent = 'Strong Password';
          strengthDiv.className = 'password-strength strength-strong';
        }
        
        return score === 5;
      }
      
      function updateRequirement(id, met) {
        const element = document.getElementById(id);
        const icon = element.querySelector('i');
        
        if (met) {
          element.className = 'requirement met';
          icon.className = 'fas fa-check-circle';
        } else {
          element.className = 'requirement unmet';
          icon.className = 'fas fa-circle';
        }
      }
      
      function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (confirm.length === 0) {
          confirmDiv.textContent = '';
          confirmDiv.className = 'password-strength';
        } else if (password === confirm) {
          confirmDiv.textContent = 'Passwords match';
          confirmDiv.className = 'password-strength strength-strong';
        } else {
          confirmDiv.textContent = 'Passwords do not match';
          confirmDiv.className = 'password-strength strength-weak';
        }
      }
      
      // Show/hide requirements on focus/blur
      passwordInput.addEventListener('focus', function() {
        document.getElementById('password-requirements').classList.add('show');
      });
      
      passwordInput.addEventListener('blur', function() {
        // Keep requirements visible if there's content and not all requirements are met
        const password = this.value;
        if (password.length > 0) {
          const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password)
          };
          
          const allMet = Object.values(requirements).every(met => met);
          if (!allMet) {
            document.getElementById('password-requirements').classList.add('show');
          } else {
            // Hide requirements if all are met
            document.getElementById('password-requirements').classList.remove('show');
          }
        } else {
          // Hide requirements if field is empty
          document.getElementById('password-requirements').classList.remove('show');
        }
      });
      
      // Event listeners
      passwordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
        checkPasswordMatch();
        
        // Show requirements if user starts typing
        if (this.value.length > 0) {
          document.getElementById('password-requirements').classList.add('show');
        }
      });
      
      confirmInput.addEventListener('input', checkPasswordMatch);
      
      // Form validation
      document.querySelector('form').addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (!checkPasswordStrength(password)) {
          e.preventDefault();
          alert('Please ensure your password meets all requirements.');
          return false;
        }
        
        if (password !== confirm) {
          e.preventDefault();
          alert('Passwords do not match.');
          return false;
        }
      });
    });
  </script>
</body>
</html>


