<?php
include('include/connect.php');

session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // We'll hash it later
    $fullname = trim($_POST['fullname']);
    $role = trim($_POST['role']);
   
    if (!$conn) {
        $error = "Database connection failed: " . mysqli_connect_error();
    } else {
        // Check if the username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt) {
            $error = "SQL SELECT Error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Username already exists. Please choose another.";
            } else {
                // Check if email already exists
                $stmt->close();
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $error = "Email already exists. Please use another email address.";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Insert the new user
                    $stmt->close();
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, fullname, role) VALUES (?, ?, ?, ?, ?)");
                    if (!$stmt) {
                        $error = "SQL INSERT Error: " . $conn->error;
                    } else {
                        $stmt->bind_param("sssss", $username, $email, $hashed_password, $fullname, $role);

                        if ($stmt->execute()) {
                            $success = "Registration successful! Please log in.";
                            // Redirect after a short delay
                            header("Refresh: 2; URL=login.php");
                        } else {
                            $error = "Error during registration: " . $stmt->error;
                        }
                    }
                }
            }
            $stmt->close();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Store</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .page-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .register-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }
        
        .input-group input:focus + .input-icon {
            color: #667eea;
        }
        button {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        button:active {
            transform: translateY(0);
        }
        .error-message {
            color: #f44336;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
        }
        .success-message {
            color: #4CAF50;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        .social-register {
            margin-top: 20px;
            text-align: center;
        }
        .social-register p {
            margin-bottom: 10px;
            color: #555;
        }
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .social-icons a.facebook {
            background-color: #3b5998;
            color: white;
        }
        .social-icons a.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: white;
        }
        
        .social-icons a.google {
            background: #db4437;
            color: white;
        }
        .social-icons a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2d3748;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        /* Enhanced form styling */
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        /* Role Selection Styling */
        .role-selection {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 10px;
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .role-label {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
            gap: 15px;
        }
        
        .role-label:hover {
            border-color: #667eea;
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        
        .role-option input[type="radio"]:checked + .role-label {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .role-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .role-info h4 {
            margin: 0 0 5px 0;
            color: #2d3748;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .role-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .role-option input[type="radio"]:checked + .role-label .role-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .role-option input[type="radio"]:checked + .role-label .role-info h4 {
            color: #667eea;
        }
        
        .role-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .role-option:hover .role-icon {
            transform: scale(1.1);
        }
        
        .role-option.selected {
            border-color: #3498db;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(41, 128, 185, 0.05));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.2);
        }
        
        .role-option.selected .role-icon {
            color: #3498db;
            transform: scale(1.1);
        }
        
        .role-option.selected h4 {
            color: #2980b9;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .page-content {
                padding: 20px 15px;
            }
            
            .container {
                padding: 30px 25px;
            }
            
            h2 {
                font-size: 1.8rem;
            }
            
            .role-label {
                padding: 15px;
                gap: 12px;
            }
            
            .role-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .role-info h4 {
                font-size: 1.1rem;
            }
            
            .role-info p {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
<div class="page-content">
    <div class="container">
        <form action="register.php" method="POST" class="register-form" onsubmit="return validateForm()">
            <h2>Create an Account</h2>
            
            <?php if($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username:</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" required placeholder="Choose a username">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <div class="input-group">
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="fullname"><i class="fas fa-id-card"></i> Full Name:</label>
                <div class="input-group">
                    <input type="text" id="fullname" name="fullname" required placeholder="Enter your full name">
                    <i class="fas fa-id-card input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="role">Account Type:</label>
                <div class="role-selection">
                    <div class="role-option" data-role="buyer">
                        <input type="radio" id="role-buyer" name="role" value="buyer" checked>
                        <label for="role-buyer" class="role-label">
                            <div class="role-icon">🛍️</div>
                            <div class="role-info">
                                <h4>Customer</h4>
                                <p>Browse and purchase products</p>
                            </div>
                        </label>
                    </div>
                    <div class="role-option" data-role="seller">
                        <input type="radio" id="role-seller" name="role" value="seller">
                        <label for="role-seller" class="role-label">
                            <div class="role-icon">🏪</div>
                            <div class="role-info">
                                <h4>Seller</h4>
                                <p>Sell your products online</p>
                            </div>
                        </label>
                    </div>
                    <?php if (isset($_GET['admin_key']) && $_GET['admin_key'] === 'admin123'): ?>
                    <div class="role-option" data-role="admin">
                        <input type="radio" id="role-admin" name="role" value="admin">
                        <label for="role-admin" class="role-label">
                            <div class="role-icon">⚙️</div>
                            <div class="role-info">
                                <h4>Administrator</h4>
                                <p>Manage the entire system</p>
                            </div>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <button type="submit">
                <i class="fas fa-user-plus"></i>
                <span>Create Account</span>
            </button>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
            
            <div class="social-register">
                <p>Or register with:</p>
                <div class="social-icons">
                    <a href="#" class="facebook" title="Register with Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="instagram" title="Register with Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="google" title="Register with Google"><i class="fab fa-google"></i></a>
                </div>
            </div>
        </form>
        </div>
    </div>

<?php include 'include/footer.php'; ?>
    
    <script>
        // Enhanced role selection interaction
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option');
            const roleInputs = document.querySelectorAll('input[name="role"]');
            
            // Add click event to role options for better UX
            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        updateRoleSelection();
                    }
                });
            });
            
            // Update role selection visual feedback
            function updateRoleSelection() {
                roleOptions.forEach(option => {
                    const radio = option.querySelector('input[type="radio"]');
                    if (radio.checked) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                });
            }
            
            // Initialize role selection
            updateRoleSelection();
            
            // Add change event listeners to radio buttons
            roleInputs.forEach(input => {
                input.addEventListener('change', updateRoleSelection);
            });
        });
        
        function validateForm() {
            var username = document.getElementById('username').value;
            var email = document.getElementById('email').value;
            var password = document.getElementById('password').value;
            var fullname = document.getElementById('fullname').value;
            var selectedRole = document.querySelector('input[name="role"]:checked');
            
            if (username.length < 3) {
                alert('Username must be at least 3 characters long');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            if (fullname.trim() === '') {
                alert('Full name is required');
                return false;
            }
            
            if (!selectedRole) {
                alert('Please select an account type');
                return false;
            }
            
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            
            // Show success message for role selection
            const roleName = selectedRole.nextElementSibling.querySelector('h4').textContent;
            console.log(`Registering as: ${roleName}`);
            
            return true;
        }
    </script>
</body>
</html>