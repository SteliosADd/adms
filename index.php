<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SneakZone - Premium Sneaker Store with latest sneaker collections">
    <meta name="keywords" content="sneakers, shoes, premium, store, fashion">
    <meta name="author" content="SneakZone">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#ff6b35">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <!-- Chrome compatibility fixes -->
    <script>
        // Polyfill for older browsers
        if (!Element.prototype.matches) {
            Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
        }
        
        // Force Chrome to use hardware acceleration
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.transform = 'translateZ(0)';
            document.body.style.webkitTransform = 'translateZ(0)';
        });
    </script>
    <link rel="icon" href="./img/sneakers.png" type="image/png">
    <title>SneakZone - Premium Sneaker Store</title>
</head>
<body>
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="./img/sneakers.png" alt="SneakZone" class="loader-logo">
            <div class="loader-text">SneakZone</div>
            <div class="loader-spinner"></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="page-transition" id="mainContent">


<?php
session_start();
include('include/connect.php');
include('include/header.php');
?>


    <div class="slider"id="Home">
        <div class="sliderWrapper">
            <div class="sliderItem">
                <img src="./img/air.png" alt="" class="sliderImg">
                <div class="sliderBg"></div>
                <h1 class="sliderTitle">AIR FORCE 1</br> WHITE</br> COLLECTION</h1>
                <h2 class="sliderPrice">From $120</h2>
                <a href="#Product">
                    <button class="buyButton">SHOP NOW</button>
                </a>
            </div>
            <div class="sliderItem">
                <img src="./img/air2.png" alt="" class="sliderImg">
                <div class="sliderBg"></div>
                <h2 class="sliderPrice">From $120</h2>
                <h1 class="sliderTitle">CLASSIC </br> WHITE</br> STYLE</h1>
                <a href="#Product">
                    <button class="buyButton">SHOP NOW</button>
                </a>

                </div>
    
            </div>
        </div>
    </div>
   
    <div class="features">
        <div class="feature">
            <img src="./img/shipping.png" alt="" class="featureIcon">
            <span class="featureTitle">FREE SHIPPING</span>
            <span class="featureDesc">Free shipping on orders over $150. Fast delivery worldwide.</span>
        </div>
        <div class="feature">
            <img class="featureIcon" src="./img/return.png" alt="">
            <span class="featureTitle">AUTHENTIC GUARANTEE</span>
            <span class="featureDesc">100% authentic sneakers with 30-day return policy.</span>
        </div>
        <div class="feature">
            <img class="featureIcon" src="./img/gift.png" alt="">
            <span class="featureTitle">EXCLUSIVE DROPS</span>
            <span class="featureDesc">Get early access to limited edition releases.</span>
        </div>
        <div class="feature">
            <img class="featureIcon" src="./img/contact.png" alt="">
            <span class="featureTitle">SNEAKER EXPERTS</span>
            <span class="featureDesc">Professional advice from our sneaker specialists.</span>
        </div>
    </div>

    

    <div class="Product" id="Product">
        <div class="products-header">
            <h1 class="products-title">Our Premium Collection</h1>
            <p class="products-subtitle">Discover the latest and greatest sneakers from top brands</p>
        </div>
        
        <div class="single-product-container">
            <?php
            // Fetch Air Force 1 White specifically from database
            $products_query = "SELECT p.id, p.name, p.description, p.price, p.photo as image 
                              FROM products p 
                              WHERE p.name = 'Air Force 1 White'
                              LIMIT 1";
            $products_result = $conn->query($products_query);
            
            if ($products_result && $products_result->num_rows > 0) {
                $product = $products_result->fetch_assoc();
                $product_id = $product['id'];
                $product_name = htmlspecialchars($product['name']);
                $product_price = htmlspecialchars($product['price']);
                $product_desc = htmlspecialchars($product['description']);
                $product_image = !empty($product['image']) ? htmlspecialchars($product['image']) : './img/air.png';
            } else {
                // Show default Air Force 1 White if none found in database
                $product_id = 1;
                $product_name = 'Air Force 1 White';
                $product_price = '120';
                $product_desc = 'Classic Nike Air Force 1 in pristine white. A timeless sneaker that goes with everything.';
                $product_image = './img/air.png';
            }
            ?>
            <div class="featured-product-card">
                <div class="premium-badge">Featured</div>
                <div class="product-image-container">
                    <img src="<?php echo $product_image; ?>" alt="<?php echo $product_name; ?>" class="product-image">
                    <div class="product-overlay">
                        <div class="product-actions">
                            <form action="cart_action.php" method="POST" class="action-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="action-btn cart-btn" title="Add to Cart">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </form>
                            
                            <form action="add_wishlist.php" method="POST" class="action-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="hidden" name="redirect" value="index.php#Product">
                                <button type="submit" class="action-btn wishlist-btn" title="Add to Wishlist">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="product-info">
                    <h3 class="product-name"><?php echo $product_name; ?></h3>
                    <p class="product-description"><?php echo $product_desc; ?></p>
                    <div class="product-price">$<?php echo $product_price; ?></div>
                    
                    <div class="product-sizes">
                        <span class="size-label">Available Sizes:</span>
                        <div class="sizes-list">
                            <span class="size" data-size="38">38</span>
                            <span class="size" data-size="39">39</span>
                            <span class="size" data-size="40">40</span>
                            <span class="size" data-size="41">41</span>
                            <span class="size" data-size="42">42</span>
                            <span class="size" data-size="43">43</span>
                            <span class="size" data-size="44">44</span>
                            <span class="size" data-size="45">45</span>
                        </div>
                    </div>
                    
                    <div class="product-actions-bottom">
                        <button class="premium-btn add-to-cart-btn" data-product-id="<?php echo $product_id; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Add to Cart & Go</span>
                        </button>
                        <form action="add_wishlist.php" method="POST" class="action-form" style="display: inline;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <input type="hidden" name="redirect" value="index.php#Product">
                            <button type="submit" class="premium-btn-outline wishlist-btn-large" title="Add to Wishlist">
                                <i class="fas fa-heart"></i>
                                <span>Add to Wishlist</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="view-all-section">
            <a href="product_list.php" class="view-all-btn">
                <span>View All Products</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="payment">
            <h1 class="payTitle">Personal Information</h1>
            <label>Name and Surname</label>
            <input type="text" placeholder="John Doe" class="payInput">
            <label>Phone Number</label>
            <input type="text" placeholder="+1 234 5678" class="payInput">
            <label>Address</label>
            <input type="text" placeholder="Elton St 21 22-145" class="payInput">
            <h1 class="payTitle">Card Information</h1>
            <div class="cardIcons">
                <img src="./img/visa.png" width="40" alt="" class="cardIcon">
                <img src="./img/master.png" alt="" width="40" class="cardIcon">
            </div>
            <input type="password" class="payInput" placeholder="Card Number">
            <div class="cardInfo">
                <input type="text" placeholder="mm" class="payInput sm">
                <input type="text" placeholder="yyyy" class="payInput sm">
                <input type="text" placeholder="cvv" class="payInput sm">
            </div>

            <button class="payButton">Checkout!</button>
            <span class="close">X</span>
        </div>
    </div>
    <div class="gallery">
        <div class="galleryItem">
            <h1 class="galleryTitle">Step Into Style</h1>
            <img src="https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg?auto=compress&cs=tinysrgb&dpr=2&w=500"
                alt="" class="galleryImg">
        </div>
        <div class="galleryItem">
            <img src="https://images.pexels.com/photos/1598505/pexels-photo-1598505.jpeg?auto=compress&cs=tinysrgb&dpr=2&w=500"
                alt="" class="galleryImg">
            <h1 class="galleryTitle">Premium Quality, Unmatched Comfort</h1>
        </div>
        <div class="galleryItem">
            <h1 class="galleryTitle">Walk Your Way!</h1>
            <img src="https://images.pexels.com/photos/1456706/pexels-photo-1456706.jpeg?auto=compress&cs=tinysrgb&dpr=2&w=500"
                alt="" class="galleryImg">
        </div>
    </div>
    
<?php include('include/footer.php'); ?>

    <!-- Floating Action Button -->
    <a href="#Home" class="fab" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    </div> <!-- End Main Content -->



    <style>
        /* Wishlist Button Large Styling */
        .wishlist-btn-large {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
            color: white !important;
            border: 2px solid transparent !important;
            transition: all 0.3s ease !important;
        }
        
        .wishlist-btn-large:hover {
            background: linear-gradient(135deg, #c0392b, #a93226) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.4) !important;
        }
        
        .wishlist-btn-large i {
            color: white !important;
        }
            }
            
            .view360-controls {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .control-btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
    </style>

    <script>
        // Page Loader
        window.addEventListener('load', function() {
            const loader = document.getElementById('pageLoader');
            const mainContent = document.getElementById('mainContent');
            
            setTimeout(() => {
                loader.classList.add('hidden');
                mainContent.classList.add('loaded');
            }, 1000);
        });

        // Back to Top Button
        window.addEventListener('scroll', function() {
            const fab = document.getElementById('backToTop');
            if (window.scrollY > 300) {
                fab.classList.add('show');
            } else {
                fab.classList.remove('show');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading states to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<div class="loading"></div> Processing...';
                    submitBtn.disabled = true;
                }
            });
        });

        // Size Selection Functionality
        document.querySelectorAll('.size').forEach(size => {
            size.addEventListener('click', function() {
                // Remove active class from siblings
                this.parentNode.querySelectorAll('.size').forEach(s => s.classList.remove('active'));
                // Add active class to clicked size
                this.classList.add('active');
                
                // Store selected size
                const productCard = this.closest('.product-card');
                productCard.setAttribute('data-selected-size', this.getAttribute('data-size'));
            });
        });

        // Premium Add to Cart Functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const productCard = this.closest('.featured-product-card') || this.closest('.product-card');
                const selectedSize = productCard ? productCard.getAttribute('data-selected-size') : null;
                
                if (!selectedSize) {
                    // Show size selection prompt
                    const sizeLabel = productCard ? productCard.querySelector('.size-label') : null;
                    if (sizeLabel) {
                        sizeLabel.style.color = '#e74c3c';
                        sizeLabel.textContent = 'Please select a size:';
                        
                        setTimeout(() => {
                            sizeLabel.style.color = '#666';
                            sizeLabel.textContent = 'Available Sizes:';
                        }, 2000);
                    } else {
                        alert('Please select a size before adding to cart');
                    }
                    return;
                }
                
                // Add loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Adding...</span>';
                this.disabled = true;
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('product_id', productId);
                formData.append('quantity', 1);
                
                // Send to cart_action.php
                fetch('cart_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    this.innerHTML = '<i class="fas fa-check"></i><span>Added!</span>';
                    this.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
                    
                    setTimeout(() => {
                        // Redirect to add to cart page
                        window.location.href = 'add_to_cart.php';
                    }, 1000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding product to cart');
                    this.innerHTML = '<i class="fas fa-shopping-cart"></i><span>Add to Cart & Go</span>';
                    this.disabled = false;
                });
            });
        });



        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.feature, .product-card, .galleryItem').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>

</body>
</html>