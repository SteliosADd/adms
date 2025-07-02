<?php
include('include/connect.php');
session_start();

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'name_asc';

// Pagination settings
$items_per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1
$offset = ($current_page - 1) * $items_per_page;

// Fetch categories
$category_query = "SELECT DISTINCT type FROM products ORDER BY type";
$category_result = $conn->query($category_query);
$categories = [];

// Check if products table exists
$table_check = $conn->query("SHOW TABLES LIKE 'products'");
if ($table_check->num_rows == 0) {
    die("Products table does not exist. Please run the database setup first.");
}

// Determine sort order
$order_clause = "p.name ASC";
switch ($sort_by) {
    case 'price_asc':
        $order_clause = "p.price ASC, p.name ASC";
        break;
    case 'price_desc':
        $order_clause = "p.price DESC, p.name ASC";
        break;
    case 'name_desc':
        $order_clause = "p.name DESC";
        break;
    case 'newest':
        $order_clause = "p.id DESC";
        break;
    default: // name_asc
        $order_clause = "p.name ASC";
        break;
}

// Count total products for pagination
$count_query = "
    SELECT COUNT(*) as total 
    FROM products p
    LEFT JOIN users u ON p.seller_id = u.id
    WHERE (p.name LIKE ? OR p.description LIKE ?)
    AND (? = '' OR p.type = ?)
";
$count_stmt = $conn->prepare($count_query);
if (!$count_stmt) {
    die("Prepare failed: " . $conn->error);
}
$search_param = "%$search%";
$count_stmt->bind_param("ssss", $search_param, $search_param, $filter_category, $filter_category);
if (!$count_stmt->execute()) {
    die("Execute failed: " . $count_stmt->error);
}
$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $items_per_page);

// Fetch products with pagination
$query = "
    SELECT p.id, p.name, p.description, p.price, p.type as category, p.photo as image, COALESCE(u.fullname, 'Unknown Seller') AS seller_name 
    FROM products p
    LEFT JOIN users u ON p.seller_id = u.id
    WHERE (p.name LIKE ? OR p.description LIKE ?)
    AND (? = '' OR p.type = ?)
    ORDER BY $order_clause
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$search_param = "%$search%";

?>
<?php
// Execute the query
$stmt->bind_param("ssssii", $search_param, $search_param, $filter_category, $filter_category, $items_per_page, $offset);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
if (!$result) {
    die("Get result failed: " . $stmt->error);
}

// Include header
include('include/header.php');
?>

<div class="container">
  <h1>Product List</h1>
  
  <!-- Search and Filter Form -->
  <div class="search-filter">
    <form method="GET" action="product_list.php" class="search-form">
      <div class="search-input-container">
        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="search-button"><i class="fa fa-search"></i></button>
      </div>
      
      <div class="filter-options">
        <select name="category">
          <option value="">All Categories</option>
          <?php 
          if ($category_result) {
            while ($category = $category_result->fetch_assoc()) {
              $selected = ($filter_category == $category['type']) ? 'selected' : '';
              echo '<option value="' . htmlspecialchars($category['type']) . '" ' . $selected . '>' . 
                   htmlspecialchars($category['type']) . '</option>';
            }
          }
          ?>
        </select>
        
        <select name="sort_by">
          <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
          <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
          <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
          <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
          <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
        </select>
      </div>
    </form>
  </div>
  
  <!-- Results Summary -->
  <div class="results-summary">
    <p>Showing <?php echo min($total_products, $offset + 1); ?>-<?php echo min($total_products, $offset + $items_per_page); ?> of <?php echo $total_products; ?> products</p>
  </div>
  
  <!-- Product Grid -->
  <div class="productList">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($product = $result->fetch_assoc()): ?>
        <div class="productItem">
          <div class="product-image-container">
            <?php 
            $image_path = !empty($product['image']) ? $product['image'] : 'img/air.png';
            ?>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
            <div class="product-category-badge"><?php echo htmlspecialchars($product['category']); ?></div>
          </div>
          
          <div class="product-info">
            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
            <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
            <p class="product-seller">Seller: <?php echo htmlspecialchars($product['seller_name']); ?></p>
          </div>
          
          <div class="product-actions">
            <form action="cart_action.php" method="POST" class="cart-form">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
              <div class="quantity-container">
                <label for="quantity-<?php echo $product['id']; ?>" class="quantity-label">Qty:</label>
                <input type="number" id="quantity-<?php echo $product['id']; ?>" name="quantity" value="1" min="1" max="10" class="quantity-input">
              </div>
              <button type="submit" class="add-to-cart-btn"><i class="fa fa-shopping-cart"></i> Add to Cart</button>
            </form>
            
            <form action="add_wishlist.php" method="POST" class="wishlist-form">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
              <input type="hidden" name="redirect" value="view_wishlist.php">
              <button type="submit" class="wishlist-btn"><i class="fa fa-heart"></i> Wishlist</button>
            </form>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-products">
        <i class="fa fa-search fa-3x"></i>
        <p>No products found matching your criteria.</p>
        <p>Try adjusting your search or browse all categories.</p>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php if ($current_page > 1): ?>
      <a href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&sort_by=<?php echo urlencode($sort_by); ?>&page=<?php echo $current_page - 1; ?>" class="page-link">&laquo; Previous</a>
    <?php endif; ?>
    
    <?php 
    // Calculate range of page numbers to display
    $range = 2; // Display 2 pages before and after current page
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    // Always show first page
    if ($start_page > 1) {
      echo '<a href="?search=' . urlencode($search) . '&category=' . urlencode($filter_category) . '&sort_by=' . urlencode($sort_by) . '&page=1" class="page-link">1</a>';
      if ($start_page > 2) {
        echo '<span class="page-ellipsis">...</span>';
      }
    }
    
    // Display page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
      $active = $i == $current_page ? 'active' : '';
      echo '<a href="?search=' . urlencode($search) . '&category=' . urlencode($filter_category) . '&sort_by=' . urlencode($sort_by) . '&page=' . $i . '" class="page-link ' . $active . '">' . $i . '</a>';
    }
    
    // Always show last page
    if ($end_page < $total_pages) {
      if ($end_page < $total_pages - 1) {
        echo '<span class="page-ellipsis">...</span>';
      }
      echo '<a href="?search=' . urlencode($search) . '&category=' . urlencode($filter_category) . '&sort_by=' . urlencode($sort_by) . '&page=' . $total_pages . '" class="page-link">' . $total_pages . '</a>';
    }
    ?>
    
    <?php if ($current_page < $total_pages): ?>
      <a href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&sort_by=<?php echo urlencode($sort_by); ?>&page=<?php echo $current_page + 1; ?>" class="page-link">Next &raquo;</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php include('include/footer.php'); ?>
<style>
/* Product List Styles */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.search-filter {
  margin-bottom: 20px;
  padding: 20px;
  background-color: #f8f9fa;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.search-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.search-input-container {
  position: relative;
  flex: 1;
}

.search-input-container input[type="text"] {
  width: 100%;
  padding: 12px 50px 12px 15px;
  border: 1px solid #ddd;
  border-radius: 25px;
  font-size: 16px;
  transition: all 0.3s ease;
}

.search-input-container input[type="text"]:focus {
  border-color: #4CAF50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
  outline: none;
}

.search-button {
  position: absolute;
  right: 5px;
  top: 50%;
  transform: translateY(-50%);
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.search-button:hover {
  background-color: #45a049;
}

.filter-options {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.filter-options select {
  flex: 1;
  min-width: 150px;
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 25px;
  background-color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-options select:focus {
  border-color: #4CAF50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
  outline: none;
}

.results-summary {
  margin-bottom: 20px;
  color: #666;
  font-size: 14px;
  text-align: right;
}

.productList {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 25px;
  margin-bottom: 30px;
}

.productItem {
  border: 1px solid #eee;
  border-radius: 8px;
  background-color: white;
  overflow: hidden;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.productItem:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.product-image-container {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.productItem:hover .product-image {
  transform: scale(1.05);
}

.product-category-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background-color: rgba(0, 0, 0, 0.6);
  color: white;
  padding: 5px 10px;
  border-radius: 15px;
  font-size: 12px;
  font-weight: bold;
}

.product-info {
  padding: 15px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.product-title {
  margin: 0 0 10px 0;
  font-size: 18px;
  font-weight: bold;
  color: #333;
  line-height: 1.3;
  height: 47px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
}

.product-description {
  color: #666;
  margin-bottom: 15px;
  font-size: 14px;
  line-height: 1.4;
  flex-grow: 1;
  height: 60px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
}

.product-price {
  font-weight: bold;
  font-size: 22px;
  color: #4CAF50;
  margin: 5px 0;
}

.product-seller {
  font-size: 13px;
  color: #888;
  margin-bottom: 10px;
}

.product-actions {
  padding: 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-top: 1px solid #dee2e6;
  border-radius: 0 0 12px 12px;
}

.cart-form, .wishlist-form {
  margin-bottom: 12px;
}

.quantity-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 15px;
  padding: 10px;
  background-color: white;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.quantity-label {
  font-size: 14px;
  font-weight: 600;
  color: #495057;
  margin-right: 10px;
}

.quantity-input {
  width: 70px;
  padding: 8px 12px;
  border: 2px solid #e9ecef;
  border-radius: 6px;
  text-align: center;
  font-weight: 600;
  color: #495057;
  transition: all 0.3s ease;
  background-color: #f8f9fa;
}

.quantity-input:focus {
  outline: none;
  border-color: #007bff;
  background-color: white;
  box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.add-to-cart-btn, .wishlist-btn {
  width: 100%;
  padding: 12px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.add-to-cart-btn {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  border: 2px solid transparent;
}

.add-to-cart-btn:hover {
  background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.add-to-cart-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 10px rgba(40, 167, 69, 0.3);
}

.wishlist-btn {
  background: white;
  color: #dc3545;
  border: 2px solid #dc3545;
  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
}

.wishlist-btn:hover {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
}

.wishlist-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
}

.add-to-cart-btn i, .wishlist-btn i {
  font-size: 16px;
  transition: transform 0.3s ease;
}

.add-to-cart-btn:hover i {
  transform: scale(1.1);
}

.wishlist-btn:hover i {
  transform: scale(1.1) rotate(5deg);
}

.no-products {
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px 20px;
  background-color: #f9f9f9;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
  color: #666;
}

.no-products i {
  color: #ccc;
  margin-bottom: 10px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 30px 0;
  flex-wrap: wrap;
  gap: 5px;
}

.page-link {
  display: inline-block;
  padding: 8px 12px;
  margin: 0 2px;
  border: 1px solid #ddd;
  color: #333;
  text-decoration: none;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.page-link:hover {
  background-color: #f5f5f5;
}

.page-link.active {
  background-color: #4CAF50;
  color: white;
  border-color: #4CAF50;
}

.page-ellipsis {
  padding: 8px 12px;
  color: #666;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .productList {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  }
}

@media (max-width: 480px) {
  .productList {
    grid-template-columns: 1fr;
  }
  
  .search-form {
    flex-direction: column;
  }
}

/* -------------------------------- 

File#: _3_product
Title: Product
Descr: Product info section with CTA
Usage: codyhouse.co/license

-------------------------------- */
/* reset */
*, *::after, *::before {
  box-sizing: border-box;
}

* {
  font: inherit;
  margin: 0;
  padding: 0;
  border: 0;
}

body {
  background-color: hsl(0, 0%, 100%);
  font-family: system-ui, sans-serif;
  color: hsl(230, 7%, 23%);
  font-size: 1rem;
}

h1, h2, h3, h4 {
  line-height: 1.2;
  color: hsl(230, 13%, 9%);
  font-weight: 700;
}

h1 {
  font-size: 2.0736rem;
}

h2 {
  font-size: 1.728rem;
}

h3 {
  font-size: 1.25rem;
}

h4 {
  font-size: 1.2rem;
}

ol, ul, menu {
  list-style: none;
}

button, input, textarea, select {
  background-color: transparent;
  border-radius: 0;
  color: inherit;
  line-height: inherit;
  appearance: none;
}

textarea {
  resize: vertical;
  overflow: auto;
  vertical-align: top;
}

a {
  color: hsl(250, 84%, 54%);
}

table {
  border-collapse: collapse;
  border-spacing: 0;
}

img, video, svg {
  display: block;
  max-width: 100%;
}

@media (min-width: 64rem) {
  body {
    font-size: 1.25rem;
  }

  h1 {
    font-size: 3.051rem;
  }

    h2 {
    font-size: 2.44rem;
  }

    h3 {
    font-size: 1.75rem;
  }

    h4 {
    font-size: 1.5625rem;
  }
}

/* variables */
:root {
  /* colors */
  --pu0-color-primary-hsl: 250, 84%, 54%;
  --pu0-color-bg-hsl: 0, 0%, 100%;
  --pu0-color-contrast-high-hsl: 230, 7%, 23%;
  --pu0-color-contrast-higher-hsl: 230, 13%, 9%;
  --pu0-color-contrast-low-hsl: 240, 4%, 65%;
  --pu0-color-contrast-medium-hsl: 225, 4%, 47%;
  --pu0-color-bg-dark-hsl: 240, 4%, 95%;
  --pu0-color-white-hsl: 0, 0%, 100%;
  --pu0-color-primary-darker-hsl: 250, 84%, 38%;
  --pu0-color-primary-light-hsl: 250, 84%, 60%;
  --pu0-color-contrast-lower-hsl: 240, 4%, 85%;
  --pu0-color-accent-hsl: 342, 89%, 48%;

  /* spacing */
  --pu0-space-2xs: 0.375rem;
  --pu0-space-xs: 0.5rem;
  --pu0-space-sm: 0.75rem;
  --pu0-space-md: 1.25rem;

  /* typography */
  --pu0-text-md: 1.2rem;
  --pu0-text-sm: 0.833rem;
  --pu0-text-xs: 0.694rem;
  --pu0-text-sm: 0.833rem;
}

@media(min-width: 64rem){
  :root {
    /* spacing */
    --pu0-space-2xs: 0.5625rem;
    --pu0-space-xs: 0.75rem;
    --pu0-space-sm: 1.125rem;
    --pu0-space-md: 2rem;

    /* typography */
    --pu0-text-md: 1.5625rem;
    --pu0-text-sm: 1rem;
    --pu0-text-xs: 0.8rem;
    --pu0-text-sm: 1rem;
  }
}

/* buttons */
.pu0-btn {
  position: relative;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  font-size: 1em;
  white-space: nowrap;
  text-decoration: none;
  background: hsl(var(--pu0-color-bg-dark-hsl));
  color: hsl(var(--pu0-color-contrast-higher-hsl));
  cursor: pointer;
  text-decoration: none;
  line-height: 1.2;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  transition: all 0.2s ease;
  will-change: transform;
  padding: var(--pu0-space-2xs) var(--pu0-space-sm);
  border-radius: 0.25em;
}

.pu0-btn:focus-visible {
  box-shadow: 0px 0px 0px 2px hsl(var(--pu0-color-bg-hsl)), 0px 0px 0px 4px hsla(var(--pu0-color-contrast-higher-hsl), 0.15);
  outline: none;
}

.pu0-btn:active {
  transform: translateY(2px);
}

.pu0-btn--primary {
  background: hsl(var(--pu0-color-primary-hsl));
  color: hsl(var(--pu0-color-white-hsl));
  box-shadow: inset 0px 1px 0px hsla(var(--pu0-color-white-hsl), 0.15), 0px 1px 3px hsla(var(--pu0-color-primary-darker-hsl), 0.25), 0px 2px 6px hsla(var(--pu0-color-primary-darker-hsl), 0.1), 0px 6px 10px -2px hsla(var(--pu0-color-primary-darker-hsl), 0.25);
}

.pu0-btn--primary:hover {
  background: hsl(var(--pu0-color-primary-light-hsl));
  box-shadow: inset 0px 1px 0px hsla(var(--pu0-color-white-hsl), 0.15), 0px 1px 2px hsla(var(--pu0-color-primary-darker-hsl), 0.25), 0px 1px 4px hsla(var(--pu0-color-primary-darker-hsl), 0.1), 0px 3px 6px -2px hsla(var(--pu0-color-primary-darker-hsl), 0.25);
}

.pu0-btn--primary:focus {
  box-shadow: inset 0px 1px 0px hsla(var(--pu0-color-white-hsl), 0.15), 0px 1px 2px hsla(var(--pu0-color-primary-darker-hsl), 0.25), 0px 1px 4px hsla(var(--pu0-color-primary-darker-hsl), 0.1), 0px 3px 6px -2px hsla(var(--pu0-color-primary-darker-hsl), 0.25), 0px 0px 0px 2px hsl(var(--pu0-color-bg-hsl)), 0px 0px 0px 4px hsl(var(--pu0-color-primary-hsl));
}

/* form elements */
.pu0-form-control {
  font-size: 1em;
  padding: var(--pu0-space-2xs) var(--pu0-space-xs);
  background: hsl(var(--pu0-color-bg-dark-hsl));
  line-height: 1.2;
  box-shadow: inset 0px 0px 0px 1px hsl(var(--pu0-color-contrast-lower-hsl));
  transition: all 0.2s ease;
  border-radius: 0.25em;
}

.pu0-form-control::placeholder {
  opacity: 1;
  color: hsl(var(--pu0-color-contrast-low-hsl));
}

.pu0-form-control:focus, .pu0-form-control:focus-within {
  background: hsl(var(--pu0-color-bg-hsl));
  box-shadow: inset 0px 0px 0px 1px hsla(var(--pu0-color-contrast-lower-hsl), 0), 0px 0px 0px 2px hsl(var(--pu0-color-primary-hsl)), 0 0.3px 0.4px rgba(0, 0, 0, 0.025),0 0.9px 1.5px rgba(0, 0, 0, 0.05), 0 3.5px 6px rgba(0, 0, 0, 0.1);
  outline: none;
}

.pu0-form-label {
  display: inline-block;
  font-size: var(--pu0-text-sm);
}

/* icons */
.pu0-icon {
  height: var(--pu0-size, 1em);
  width: var(--pu0-size, 1em);
  display: inline-block;
  color: inherit;
  fill: currentColor;
  line-height: 1;
  flex-shrink: 0;
  max-width: initial;
}

/* component */


/* utility classes */
.pu0-flex-grow {
  flex-grow: 1;
}

.pu0-sr-only {
  position: absolute;
  clip: rect(1px, 1px, 1px, 1px);
  clip-path: inset(50%);
  width: 1px;
  height: 1px;
  overflow: hidden;
  padding: 0;
  border: 0;
  white-space: nowrap;
}

.pu0-gap-xs {
  gap: var(--pu0-space-xs);
}

.pu0-flex {
  display: flex;
}

.pu0-inline-flex {
  display: inline-flex;
}

.pu0-margin-bottom-md {
  margin-bottom: var(--pu0-space-md);
}

.pu0-text-decoration-none {
  text-decoration: none;
}

.pu0-margin-right-2xs {
  margin-right: var(--pu0-space-2xs);
}

.pu0-color-contrast-medium {
  --pu0-color-o: 1;
  color: hsla(var(--pu0-color-contrast-medium-hsl), var(--pu0-color-o, 1));
}

.pu0-text-line-through {
  text-decoration: line-through;
}

.pu0-text-md {
  font-size: var(--pu0-text-md);
}

.pu0-margin-y-md {
  margin-top: var(--pu0-space-md);
  margin-bottom: var(--pu0-space-md);
}

.pu0-text-gap-md {
  --pu0-space-multiplier: 1.25;
}

.pu0-text-component :where(h1, h2, h3, h4) {
  line-height: var(--pu0-heading-line-height, 1.2);
  margin-top: calc(var(--pu0-space-md) * var(--pu0-space-multiplier, 1));
  margin-bottom: calc(var(--pu0-space-sm) * var(--pu0-space-multiplier, 1));
}

.pu0-text-component :where(p, blockquote, ul li, ol li) {
  line-height: var(--pu0-body-line-height, 1.4);
}

.pu0-text-component :where(ul, ol, p, blockquote, .pu0-text-component__block) {
  margin-bottom: calc(var(--pu0-space-sm) * var(--pu0-space-multiplier, 1));
}

.pu0-text-component :where(ul, ol) {
  padding-left: 1.25em;
}

.pu0-text-component ul :where(ul, ol), .pu0-text-component ol :where(ul, ol) {
  padding-left: 1em;
  margin-bottom: 0;
}

.pu0-text-component ul {
  list-style-type: disc;
}

.pu0-text-component ol {
  list-style-type: decimal;
}

.pu0-text-component img {
  display: block;
  margin: 0 auto;
}

.pu0-text-component figcaption {
  margin-top: calc(var(--pu0-space-xs) * var(--pu0-space-multiplier, 1));
  font-size: var(--pu0-text-sm);
  text-align: center;}

.pu0-text-component em {
  font-style: italic;
}

.pu0-text-component strong {
  font-weight: bold;
}

.pu0-text-component s {
  text-decoration: line-through;
}

.pu0-text-component u {
  text-decoration: underline;
}

.pu0-text-component mark {
  background-color: hsla(var(--pu0-color-accent-hsl), 0.2);
  color: inherit;
}

.pu0-text-component blockquote {
  padding-left: 1em;
  border-left: 4px solid hsl(var(--pu0-color-contrast-lower-hsl));
  font-style: italic;
}

.pu0-text-component hr {
  margin: calc(var(--pu0-space-md) * var(--pu0-space-multiplier, 1)) auto;
  background: hsl(var(--pu0-color-contrast-lower-hsl));
  height: 1px;
}

.pu0-text-component > *:first-child {
  margin-top: 0;
}

.pu0-text-component > *:last-child {
  margin-bottom: 0;
}

.pu0-text-component.pu0-line-height-xs {
  --pu0-heading-line-height: 1;
  --pu0-body-line-height: 1.1;
}

.pu0-text-component.pu0-line-height-sm {
  --pu0-heading-line-height: 1.1;
  --pu0-body-line-height: 1.2;
}

.pu0-text-component.pu0-line-height-md {
  --pu0-heading-line-height: 1.15;
  --pu0-body-line-height: 1.4;
}

.pu0-text-component.pu0-line-height-lg {
  --pu0-heading-line-height: 1.22;
  --pu0-body-line-height: 1.58;
}

.pu0-text-component.pu0-line-height-xl {
  --pu0-heading-line-height: 1.3;
  --pu0-body-line-height: 1.72;
}

.pu0-color-inherit {
  color: inherit;
}

.pu0-text-sm {
  font-size: var(--pu0-text-sm);
}

.pu0-margin-left-2xs {
  margin-left: var(--pu0-space-2xs);
}

.pu0-text-xs {
  font-size: var(--pu0-text-xs);
}

.pu0-margin-bottom-xs {
  margin-bottom: var(--pu0-space-xs);
}

.pu0-gap-md {
  gap: var(--pu0-space-md);
}

.pu0-grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
}

.pu0-grid > * {
  min-width: 0;
  grid-column-end: span 12;
}

.pu0-color-contrast-low {
  --pu0-color-o: 1;
  color: hsla(var(--pu0-color-contrast-low-hsl), var(--pu0-color-o, 1));
}

.pu0-gap-2xs {
  gap: var(--pu0-space-2xs);
}

.pu0-flex-wrap {
  flex-wrap: wrap;
}

.pu0-margin-bottom-sm {
  margin-bottom: var(--pu0-space-sm);
}

@media(min-width: 64rem){
  .pu0-col-6\@md {
    grid-column-end: span 6;
  }
}

@media(min-width: 80rem){
  .pu0-col-7\@lg {
    grid-column-end: span 7;
  }

  .pu0-col-5\@lg {
    grid-column-end: span 5;
  }
}
</style>