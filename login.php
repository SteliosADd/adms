<?php
include('include/connect.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("SQL error: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Check for redirect parameter
            $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : null;
            
            // Validate redirect URL to prevent open redirect attacks
            if ($redirect_url && !filter_var($redirect_url, FILTER_VALIDATE_URL)) {
                // If it's a relative URL, it's safe to use
                if (strpos($redirect_url, '/') === 0 || !preg_match('/^https?:\/\//', $redirect_url)) {
                    header("Location: " . $redirect_url);
                    exit();
                }
            }
            
            // Redirect based on user role if no valid redirect URL
            if ($_SESSION['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($_SESSION['role'] == 'seller') {
                header("Location: seller_dashboard.php");
            } else {
                // Default for customers/buyers
                header("Location: index.php");
            }
            exit();
        } else {
            echo '<script>alert("Invalid password."); window.location.href = "login.php";</script>';
        }
    } else {
        echo '<script>alert("Invalid username."); window.location.href = "login.php";</script>';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        input[type="text"],
        input[type="password"],
        select {
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
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        
        .login-message {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .login-message p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .login-message a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-message a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #f5c6cb;
            font-weight: 500;
        }
        .social-login {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .social-login p {
            margin-bottom: 15px;
            color: #6c757d;
            font-weight: 500;
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
            width: 50px;
            height: 50px;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .social-icons a.facebook {
            background: #1877f2;
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
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        /* Role Information Styling */
        .role-info-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .role-info-section h4 {
            color: #2c3e50;
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 600;
        }
        
        .role-badges {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .role-badge.customer {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(41, 128, 185, 0.1));
            color: #2980b9;
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .role-badge.seller {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(39, 174, 96, 0.1));
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        
        .role-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .page-content {
                padding: 20px 15px;
            }
            
            .login-container {
                padding: 30px 25px;
            }
            
            h2 {
                font-size: 1.8rem;
            }
            
            .role-badges {
                flex-direction: column;
                align-items: center;
            }
            
            .role-badge {
                width: 100%;
                justify-content: center;
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>
<div class="page-content">
    <div class="login-container">
        <h2>Login to Your Account</h2>
        
        <div id="error-message" class="error-message"></div>
        
        <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST" class="login-form" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username:</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>
            <div class="form-group">
                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </button>
            </div>
            <div class="login-message">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <div class="role-info-section">
                    <h4>Account Types Available:</h4>
                    <div class="role-badges">
                        <span class="role-badge customer">🛍️ Customer</span>
                        <span class="role-badge seller">🏪 Seller</span>
                    </div>
                </div>
            </div>
            <div class="social-login">
                <p>Or login with:</p>
                <div class="social-icons">
                    <a href="#" class="facebook" title="Login with Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="instagram" title="Login with Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="google" title="Login with Google"><i class="fab fa-google"></i></a>
                </div>
            </div>
        </form>
        </div>
    </div>

<?php include('include/footer.php'); ?>
</body>
</html>
<script>
    function validateForm() {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        const errorMessage = document.getElementById('error-message');
        
        // Clear previous error messages
        errorMessage.textContent = '';
        
        // Check if fields are empty
        if (username === '' || password === '') {
            errorMessage.textContent = 'Username and Password are required.';
            return false; // Prevent form submission
        }
        
        // Check username length
        if (username.length < 3) {
            errorMessage.textContent = 'Username must be at least 3 characters.';
            return false; // Prevent form submission
        }
        
        // Check password length
        if (password.length < 6) {
            errorMessage.textContent = 'Password must be at least 6 characters.';
            return false; // Prevent form submission
        }
        
        // If all checks pass, allow form submission
        return true;
    }
</script>
