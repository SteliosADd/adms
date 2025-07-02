<?php
session_start();
include 'include/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$product_id) {
    header('Location: product_list.php');
    exit();
}

// Get product details
$product_query = "SELECT * FROM products WHERE id = ?";
$product_stmt = $conn->prepare($product_query);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

if (!$product) {
    header('Location: product_list.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    
    if ($rating >= 1 && $rating <= 5) {
        // Check if user already reviewed this product
        $check_query = "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $product_id, $user_id);
        $check_stmt->execute();
        $existing_review = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing_review) {
            $message = "You have already reviewed this product.";
            $message_type = "error";
        } else {
            // Insert new review
            $insert_query = "INSERT INTO product_reviews (product_id, user_id, rating, review_text, status) VALUES (?, ?, ?, ?, 'approved')";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiis", $product_id, $user_id, $rating, $review_text);
            
            if ($insert_stmt->execute()) {
                $message = "Thank you for your review!";
                $message_type = "success";
            } else {
                $message = "Error submitting review. Please try again.";
                $message_type = "error";
            }
        }
    } else {
        $message = "Please select a valid rating.";
        $message_type = "error";
    }
}

// Get all reviews for this product
$reviews_query = "SELECT pr.*, u.name as user_name FROM product_reviews pr 
                  JOIN users u ON pr.user_id = u.id 
                  WHERE pr.product_id = ? AND pr.status = 'approved' 
                  ORDER BY pr.review_date DESC";
$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Get average rating
$avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM product_reviews WHERE product_id = ? AND status = 'approved'";
$avg_stmt = $conn->prepare($avg_query);
$avg_stmt->bind_param("i", $product_id);
$avg_stmt->execute();
$avg_result = $avg_stmt->get_result()->fetch_assoc();
$avg_rating = round($avg_result['avg_rating'], 1);
$total_reviews = $avg_result['total_reviews'];

// Check if current user has reviewed this product
$user_review_query = "SELECT * FROM product_reviews WHERE product_id = ? AND user_id = ?";
$user_review_stmt = $conn->prepare($user_review_query);
$user_review_stmt->bind_param("ii", $product_id, $user_id);
$user_review_stmt->execute();
$user_review = $user_review_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reviews - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reviews-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .product-header {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .product-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
        }
        .product-info h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        .stars {
            display: flex;
            gap: 2px;
        }
        .star {
            font-size: 20px;
            color: #ddd;
        }
        .star.filled {
            color: #ffc107;
        }
        .review-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .rating-input {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        .rating-input input[type="radio"] {
            display: none;
        }
        .rating-input label {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-input label:hover,
        .rating-input input[type="radio"]:checked ~ label,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }
        .review-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .reviewer-name {
            font-weight: bold;
            color: #333;
        }
        .review-date {
            color: #666;
            font-size: 14px;
        }
        .review-text {
            color: #555;
            line-height: 1.6;
        }
        .message {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="reviews-container">
        <div class="product-header">
            <img src="<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
            <div class="product-info">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                <div class="rating-summary">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span><?php echo $avg_rating; ?>/5 (<?php echo $total_reviews; ?> reviews)</span>
                </div>
                <a href="product_list.php" class="btn btn-secondary">← Back to Products</a>
            </div>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$user_review): ?>
            <div class="review-form">
                <h3>Write a Review</h3>
                <form method="POST">
                    <div>
                        <label>Rating:</label>
                        <div class="rating-input">
                            <input type="radio" name="rating" value="5" id="star5">
                            <label for="star5">★</label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4">★</label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3">★</label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2">★</label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1">★</label>
                        </div>
                    </div>
                    <div>
                        <label for="review_text">Your Review:</label>
                        <textarea name="review_text" id="review_text" placeholder="Share your experience with this product..."></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn">Submit Review</button>
                </form>
            </div>
        <?php else: ?>
            <div class="review-form">
                <h3>Your Review</h3>
                <div class="rating-summary">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= $user_review['rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span>Your rating: <?php echo $user_review['rating']; ?>/5</span>
                </div>
                <?php if ($user_review['review_text']): ?>
                    <p class="review-text"><?php echo nl2br(htmlspecialchars($user_review['review_text'])); ?></p>
                <?php endif; ?>
                <p><em>Thank you for your review!</em></p>
            </div>
        <?php endif; ?>
        
        <div class="reviews-list">
            <h3>Customer Reviews (<?php echo $total_reviews; ?>)</h3>
            
            <?php if ($total_reviews > 0): ?>
                <?php while ($review = $reviews_result->fetch_assoc()): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div>
                                <span class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span class="review-date"><?php echo date('M j, Y', strtotime($review['review_date'])); ?></span>
                        </div>
                        <?php if ($review['review_text']): ?>
                            <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No reviews yet. Be the first to review this product!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'include/footer.php'; ?>
    
    <script>
        // Rating input functionality
        const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
        const ratingLabels = document.querySelectorAll('.rating-input label');
        
        ratingLabels.forEach((label, index) => {
            label.addEventListener('mouseover', () => {
                ratingLabels.forEach((l, i) => {
                    if (i >= index) {
                        l.style.color = '#ffc107';
                    } else {
                        l.style.color = '#ddd';
                    }
                });
            });
            
            label.addEventListener('mouseout', () => {
                const checkedInput = document.querySelector('.rating-input input[type="radio"]:checked');
                if (checkedInput) {
                    const checkedIndex = Array.from(ratingInputs).indexOf(checkedInput);
                    ratingLabels.forEach((l, i) => {
                        if (i >= checkedIndex) {
                            l.style.color = '#ffc107';
                        } else {
                            l.style.color = '#ddd';
                        }
                    });
                } else {
                    ratingLabels.forEach(l => l.style.color = '#ddd');
                }
            });
        });
    </script>
</body>
</html>