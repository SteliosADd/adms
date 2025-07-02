<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'my_database_user';

echo "<h2>Αυτόματη Εγκατάσταση Βάσης Δεδομένων SneakZone</h2>";

// Create connection without selecting database first
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("<p style='color: red;'>Αποτυχία σύνδεσης: " . $conn->connect_error . "</p>");
}

echo "<p style='color: green;'>✅ Επιτυχής σύνδεση με MySQL</p>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✅ Η βάση δεδομένων '$database' δημιουργήθηκε ή υπάρχει ήδη</p>";
} else {
    echo "<p style='color: red;'>❌ Σφάλμα δημιουργίας βάσης: " . $conn->error . "</p>";
}

// Select database
$conn->select_db($database);

// Read and execute SQL file
$sql_file = 'store.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL commands
    $sql_commands = explode(';', $sql_content);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($sql_commands as $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^\s*--/', $command)) {
            if ($conn->query($command) === TRUE) {
                $success_count++;
            } else {
                $error_count++;
                if (strpos($conn->error, 'Duplicate entry') === false && 
                    strpos($conn->error, 'already exists') === false) {
                    echo "<p style='color: orange;'>⚠️ Προειδοποίηση: " . $conn->error . "</p>";
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Εκτελέστηκαν $success_count εντολές SQL επιτυχώς</p>";
    if ($error_count > 0) {
        echo "<p style='color: orange;'>⚠️ $error_count εντολές παρακάμφθηκαν (πιθανώς υπάρχουν ήδη)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Το αρχείο store.sql δεν βρέθηκε</p>";
}

// Verify installation
echo "<h3>Έλεγχος Εγκατάστασης:</h3>";

// Check tables
$tables = ['users', 'products', 'cart', 'wishlist', 'orders', 'order_details'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Πίνακας '$table' υπάρχει</p>";
    } else {
        echo "<p style='color: red;'>❌ Πίνακας '$table' δεν υπάρχει</p>";
    }
}

// Check sample data
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
echo "<p>👥 Συνολικοί χρήστες: $user_count</p>";

if ($user_count > 0) {
    echo "<h3>Δοκιμαστικοί Λογαριασμοί:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<p><strong>👑 Admin:</strong> username: <code>admin</code>, password: <code>password</code></p>";
    echo "<p><strong>🏪 Seller:</strong> username: <code>seller1</code>, password: <code>password</code></p>";
    echo "<p><strong>🛒 Customer:</strong> username: <code>customer1</code>, password: <code>password</code></p>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔑 Σύνδεση</a>";
    echo "<a href='register.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📝 Εγγραφή</a>";
    echo "<a href='index.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Αρχική</a>";
    echo "</div>";
} else {
    echo "<p style='color: orange;'>⚠️ Δεν υπάρχουν δοκιμαστικοί χρήστες. Μπορείτε να δημιουργήσετε νέο λογαριασμό.</p>";
    echo "<a href='register.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📝 Δημιουργία Λογαριασμού</a>";
}

$conn->close();
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #333;
}

.container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    max-width: 800px;
    margin: 0 auto;
}

h2, h3 {
    color: #2c3e50;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

a {
    display: inline-block;
    transition: transform 0.2s;
}

a:hover {
    transform: translateY(-2px);
}
</style>

<div class='container'>
<!-- Content will be inserted here by PHP -->
</div>