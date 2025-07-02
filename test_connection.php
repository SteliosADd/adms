<?php
include('include/connect.php');

// Test database connection
echo "<h2>Έλεγχος Σύνδεσης Βάσης Δεδομένων</h2>";

if ($conn->connect_error) {
    echo "<p style='color: red;'>Αποτυχία σύνδεσης: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>Επιτυχής σύνδεση με τη βάση δεδομένων!</p>";
}

// Check if users table exists
$table_check = "SHOW TABLES LIKE 'users'";
$result = $conn->query($table_check);

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>Ο πίνακας 'users' υπάρχει.</p>";
    
    // Count users
    $count_query = "SELECT COUNT(*) as user_count FROM users";
    $count_result = $conn->query($count_query);
    $count = $count_result->fetch_assoc();
    
    echo "<p>Συνολικοί χρήστες: " . $count['user_count'] . "</p>";
    
    // Show all users
    $users_query = "SELECT id, username, role, email FROM users";
    $users_result = $conn->query($users_query);
    
    if ($users_result->num_rows > 0) {
        echo "<h3>Λίστα Χρηστών:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th></tr>";
        
        while ($user = $users_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>Δεν υπάρχουν χρήστες στη βάση δεδομένων.</p>";
        echo "<p><a href='register.php'>Κάντε κλικ εδώ για εγγραφή νέου χρήστη</a></p>";
    }
} else {
    echo "<p style='color: red;'>Ο πίνακας 'users' δεν υπάρχει. Πρέπει να εκτελέσετε το store.sql αρχείο.</p>";
    echo "<p><a href='setup_database.php'>Κάντε κλικ εδώ για εγκατάσταση βάσης δεδομένων</a></p>";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
table {
    background-color: white;
    margin: 10px 0;
}
th, td {
    padding: 10px;
    text-align: left;
}
th {
    background-color: #667eea;
    color: white;
}
</style>