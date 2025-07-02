<!DOCTYPE html>
<?php
include('include/connect.php');
session_start();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $messageText = mysqli_real_escape_string($conn, $_POST['message']);
    
    if (!empty($firstName) && !empty($lastName) && !empty($email) && !empty($subject) && !empty($messageText)) {
        // Here you would typically save to database or send email
        $message = 'Thank you for your message! We\'ll get back to you within 24 hours.';
        $messageType = 'success';
    } else {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SneakZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include('include/header.php'); ?>
    
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="hero-content">
            <h1>Get in Touch</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
        <div class="hero-decoration">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="contact-grid">
                <!-- Contact Information -->
                <div class="contact-info">
                    <div class="info-header">
                        <h2>Let's Connect</h2>
                        <p>Ready to take your sneaker game to the next level? We're here to help!</p>
                    </div>
                    
                    <div class="contact-methods">
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="method-content">
                                <h3>Visit Our Store</h3>
                                <p>123 Sneaker Street<br>Fashion District, NY 10001<br>United States</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="method-content">
                                <h3>Call Us</h3>
                                <p>+1 (555) 123-4567<br>Mon-Fri: 9AM-8PM<br>Sat-Sun: 10AM-6PM</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="method-content">
                                <h3>Email Us</h3>
                                <p>hello@sneakzone.com<br>support@sneakzone.com<br>We reply within 24 hours</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="method-content">
                                <h3>Business Hours</h3>
                                <p>Monday - Friday: 9:00 AM - 8:00 PM<br>Saturday: 10:00 AM - 6:00 PM<br>Sunday: 12:00 PM - 5:00 PM</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <h3>Follow Us</h3>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form-container">
                    <div class="form-header">
                        <h2>Send us a Message</h2>
                        <p>Have a question about our products or need assistance? We're here to help!</p>
                    </div>
                    
                    <form class="contact-form" method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name *</label>
                                <input type="text" id="firstName" name="firstName" required>
                                <span class="form-icon"><i class="fas fa-user"></i></span>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name *</label>
                                <input type="text" id="lastName" name="lastName" required>
                                <span class="form-icon"><i class="fas fa-user"></i></span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                                <span class="form-icon"><i class="fas fa-envelope"></i></span>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone">
                                <span class="form-icon"><i class="fas fa-phone"></i></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <select id="subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="general">General Inquiry</option>
                                <option value="product">Product Question</option>
                                <option value="order">Order Support</option>
                                <option value="return">Returns & Exchanges</option>
                                <option value="partnership">Business Partnership</option>
                                <option value="feedback">Feedback & Suggestions</option>
                            </select>
                            <span class="form-icon"><i class="fas fa-tag"></i></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" placeholder="Tell us how we can help you..." required></textarea>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="newsletter" value="1">
                                <span class="checkmark"></span>
                                Subscribe to our newsletter for exclusive offers and updates
                            </label>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <span class="btn-text">Send Message</span>
                            <span class="btn-icon"><i class="fas fa-paper-plane"></i></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <div class="map-overlay">
                <div class="map-info">
                    <h3>Visit Our Flagship Store</h3>
                    <p>Experience our full collection in person at our flagship location in the heart of the fashion district.</p>
                    <a href="#" class="directions-btn">
                        <i class="fas fa-directions"></i>
                        Get Directions
                    </a>
                </div>
            </div>
            <!-- You can replace this with an actual Google Maps embed -->
            <div class="map-placeholder">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.9663095343008!2d-74.00425878459418!3d40.74844097932681!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259bf5c1654f3%3A0xc80f9cfce5383d5d!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2s!4v1635959542834!5m2!1sen!2s" 
                    width="100%" 
                    height="400" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <p>Quick answers to common questions about SneakZone</p>
            </div>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What are your shipping options?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer free standard shipping on orders over $75, express shipping (2-3 days), and next-day delivery in select areas. International shipping is available to most countries.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What is your return policy?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We accept returns within 30 days of purchase. Items must be in original condition with tags attached. Free returns are available for defective items.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Do you authenticate all sneakers?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, every sneaker goes through our rigorous authentication process by certified experts to ensure 100% authenticity before shipping to you.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How can I track my order?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Once your order ships, you'll receive a tracking number via email. You can also track your order in your account dashboard or our order tracking page.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fafafa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Hero Section */
        .contact-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
        
        .hero-decoration {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        /* Contact Section */
        .contact-section {
            padding: 80px 0;
            background: white;
        }
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }
        
        /* Contact Info */
        .contact-info {
            background: #f8f9fa;
            padding: 40px;
            border-radius: 20px;
            height: fit-content;
        }
        
        .info-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .info-header p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 40px;
        }
        
        .contact-methods {
            margin-bottom: 40px;
        }
        
        .contact-method {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .contact-method:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .method-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .method-content h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .method-content p {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .social-links h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
        }
        
        .social-icon {
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .social-icon:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Contact Form */
        .contact-form-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .form-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .form-header p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 40px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 50px 15px 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            padding-right: 15px;
        }
        
        .form-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        
        .form-group:has(textarea) .form-icon {
            top: 45px;
            transform: none;
        }
        
        .checkbox-group {
            margin: 30px 0;
        }
        
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .checkbox-label input[type="checkbox"] {
            display: none;
        }
        
        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #e9ecef;
            border-radius: 4px;
            position: relative;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        /* Map Section */
        .map-section {
            background: #f8f9fa;
            padding: 80px 0;
        }
        
        .map-container {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .map-overlay {
            position: absolute;
            top: 30px;
            left: 30px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            max-width: 350px;
            z-index: 10;
        }
        
        .map-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .map-info p {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .directions-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .directions-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        /* FAQ Section */
        .faq-section {
            padding: 80px 0;
            background: white;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .section-header p {
            font-size: 1.2rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .faq-grid {
            display: grid;
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .faq-item {
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .faq-item:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .faq-question {
            padding: 25px 30px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            transition: all 0.3s ease;
        }
        
        .faq-question:hover {
            background: #f8f9fa;
        }
        
        .faq-question h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .faq-question i {
            color: #667eea;
            transition: transform 0.3s ease;
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(45deg);
        }
        
        .faq-answer {
            padding: 0 30px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .faq-item.active .faq-answer {
            padding: 0 30px 25px;
            max-height: 200px;
        }
        
        .faq-answer p {
            color: #6c757d;
            line-height: 1.6;
            margin: 0;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .contact-info,
            .contact-form-container {
                padding: 30px 20px;
            }
            
            .map-overlay {
                position: static;
                margin: 20px;
                max-width: none;
            }
            
            .section-header h2 {
                font-size: 2.5rem;
            }
            
            .container {
                padding: 0 15px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .contact-hero {
                padding: 80px 0 60px;
            }
            
            .contact-section,
            .map-section,
            .faq-section {
                padding: 60px 0;
            }
        }
    </style>
    
    <script>
        // FAQ Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                
                question.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');
                    
                    // Close all FAQ items
                    faqItems.forEach(faq => faq.classList.remove('active'));
                    
                    // Open clicked item if it wasn't active
                    if (!isActive) {
                        item.classList.add('active');
                    }
                });
            });
        });
        
        // Form Enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.contact-form');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
    
    <?php include('include/footer.php'); ?>
</body>
</html>