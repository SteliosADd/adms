<link rel="stylesheet" href="style5.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin: 0 8px;
        text-decoration: none;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        color: white;
        font-size: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .social-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: scale(0);
        transition: transform 0.3s ease;
    }
    
    .social-link:hover::before {
        transform: scale(1);
    }
    
    .social-link.facebook {
        background: linear-gradient(135deg, #3b5998 0%, #4c70ba 100%);
        box-shadow: 0 4px 15px rgba(59, 89, 152, 0.3);
    }
    
    .social-link.twitter {
        background: linear-gradient(135deg, #1da1f2 0%, #0d8bd9 100%);
        box-shadow: 0 4px 15px rgba(29, 161, 242, 0.3);
    }
    
    .social-link.instagram {
        background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        box-shadow: 0 4px 15px rgba(225, 48, 108, 0.3);
    }
    
    .social-link.whatsapp {
        background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
    }
    
    .social-link:hover {
        transform: translateY(-5px) scale(1.1);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }
    
    /* Enhanced Footer Styling */
    .fList {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .fListItem {
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }
    
    .fListItem a {
        color: #ccc;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
        display: block;
        padding: 4px 0;
    }
    
    .fListItem a:hover {
        color: #fff;
        padding-left: 8px;
        transform: translateX(4px);
    }
    
    .company-info, .contact-info, .product-highlights {
        margin-top: 20px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        border-left: 3px solid #007bff;
    }
    
    .company-info p, .contact-info p, .product-highlights p {
        color: #bbb;
        font-size: 13px;
        margin: 8px 0;
        line-height: 1.5;
    }
    
    .contact-info p i {
        color: #007bff;
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }
    
    .product-highlights p i {
        color: #28a745;
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }
    
    .fMenuTitle {
        color: #fff;
        font-size: 20px;
        margin-bottom: 20px;
        font-weight: 700;
        position: relative;
        padding-bottom: 12px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .fMenuTitle::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border-radius: 2px;
        transition: width 0.3s ease;
    }
    
    .fMenuTitle:hover::after {
        width: 80px;
    }
    
    .footerMenu {
        margin-bottom: 35px;
        padding: 25px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 15px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease;
        backdrop-filter: blur(5px);
    }
    
    .footerMenu:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.12);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    /* Newsletter Styling */
    .fMail {
        display: flex;
        margin-top: 15px;
        border-radius: 50px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .fMail:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(0, 123, 255, 0.5);
    }
    
    .fInput {
        flex: 1;
        padding: 15px 20px;
        border: none;
        background: transparent;
        color: #fff;
        font-size: 14px;
        outline: none;
        font-family: inherit;
    }
    
    .fInput::placeholder {
        color: rgba(255, 255, 255, 0.6);
        transition: color 0.3s ease;
    }
    
    .fInput:focus::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }
    
    .fButton {
        padding: 15px 25px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .fButton::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .fButton:hover::before {
        left: 100%;
    }
    
    .fButton:hover {
        background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
    }
    
    .fIcons {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 20px 0;
        padding: 20px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .copyright {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .copyright p {
        margin: 5px 0;
        color: #999;
        font-size: 13px;
    }
    
    .copyright p:first-child {
         color: #fff;
         font-weight: 600;
     }
     
     /* Newsletter Benefits */
     .newsletter-description {
         color: #bbb;
         font-size: 13px;
         margin-top: 15px;
         text-align: center;
         padding: 10px;
         background: rgba(0, 123, 255, 0.1);
         border-radius: 8px;
         border-left: 3px solid #007bff;
     }
     
     .newsletter-description i {
         color: #007bff;
         margin-right: 8px;
     }
     
     .newsletter-benefits {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
         gap: 15px;
         margin-top: 20px;
     }
     
     .benefit-item {
         display: flex;
         flex-direction: column;
         align-items: center;
         padding: 15px 10px;
         background: rgba(255, 255, 255, 0.05);
         border-radius: 10px;
         border: 1px solid rgba(255, 255, 255, 0.1);
         transition: all 0.3s ease;
         text-align: center;
     }
     
     .benefit-item:hover {
         background: rgba(255, 255, 255, 0.08);
         border-color: rgba(0, 123, 255, 0.3);
         transform: translateY(-3px);
     }
     
     .benefit-item i {
         font-size: 20px;
         color: #007bff;
         margin-bottom: 8px;
         transition: all 0.3s ease;
     }
     
     .benefit-item:hover i {
         transform: scale(1.2);
         color: #0056b3;
     }
     
     .benefit-item span {
         font-size: 12px;
         color: #ccc;
         font-weight: 500;
         line-height: 1.3;
     }
     
     /* Footer Right Menu Styling */
     .footerRightMenu {
         margin-bottom: 30px;
         padding: 25px;
         background: rgba(255, 255, 255, 0.03);
         border-radius: 15px;
         border: 1px solid rgba(255, 255, 255, 0.08);
         transition: all 0.3s ease;
         backdrop-filter: blur(5px);
     }
     
     .footerRightMenu:hover {
         background: rgba(255, 255, 255, 0.05);
         border-color: rgba(255, 255, 255, 0.12);
         transform: translateY(-2px);
         box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
     }
     
     .newsletter-section {
         border-left: 3px solid #007bff;
     }
     
     .social-section {
         border-left: 3px solid #28a745;
     }

    footer {
        background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
        color: #ccc;
        padding: 50px 20px 30px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        position: relative;
        overflow: hidden;
    }
    
    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="0.5" fill="%23ffffff" opacity="0.02"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
        pointer-events: none;
    }
    
    .footerLeft {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        margin-bottom: 40px;
        position: relative;
        z-index: 1;
    }
    
    .footerRight {
        position: relative;
        z-index: 1;
    }
    
    @media (max-width: 768px) {
        footer {
            padding: 30px 15px 20px;
        }
        
        .footerLeft {
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .company-info, .contact-info, .product-highlights {
            padding: 10px;
        }
        
        .fMail {
            flex-direction: column;
            border-radius: 15px;
        }
        
        .fButton {
            border-radius: 0 0 15px 15px;
        }
        
        .fInput {
            border-radius: 15px 15px 0 0;
        }
    }
</style>
<footer>
        <div class="footerLeft">
            <div class="footerMenu" id="About">
                <h1 class="fMenuTitle">About SneakZone</h1>
                <ul class="fList">
                    <li class="fListItem"><a href="#about-company">Our Story</a></li>
                    <li class="fListItem"><a href="#careers">Careers</a></li>
                    <li class="fListItem"><a href="#sustainability">Sustainability</a></li>
                    <li class="fListItem"><a href="#press">Press & Media</a></li>
                    <li class="fListItem"><a href="#investors">Investor Relations</a></li>
                    <li class="fListItem"><a href="#partnerships">Partnerships</a></li>
                </ul>
                <div class="company-info">
                    <p>Premium sneakers and streetwear for the modern lifestyle. Quality, style, and comfort since 2020.</p>
                </div>
            </div>
            <div class="footerMenu">
                <h1 class="fMenuTitle">Customer Support</h1>
                <ul class="fList">
                    <li class="fListItem"><a href="contactUS.php">Contact Us</a></li>
                    <li class="fListItem"><a href="#support">Help Center</a></li>
                    <li class="fListItem"><a href="#returns">Returns & Exchanges</a></li>
                    <li class="fListItem"><a href="#shipping">Shipping Info</a></li>
                    <li class="fListItem"><a href="#size-guide">Size Guide</a></li>
                    <li class="fListItem"><a href="#faq">FAQ</a></li>
                </ul>
                <div class="contact-info">
                    <p><i class="fas fa-phone"></i> 1-800-SNEAKS</p>
                    <p><i class="fas fa-envelope"></i> support@sneakzone.com</p>
                    <p><i class="fas fa-clock"></i> Mon-Fri 9AM-6PM EST</p>
                </div>
            </div>
            <div class="footerMenu">
                <h1 class="fMenuTitle" id="Product">Our Products</h1>
                <ul class="fList">
                    <li class="fListItem"><a href="product_list.php?category=sneakers">Sneakers</a></li>
                    <li class="fListItem"><a href="product_list.php?category=running">Running Shoes</a></li>
                    <li class="fListItem"><a href="product_list.php?category=basketball">Basketball</a></li>
                    <li class="fListItem"><a href="product_list.php?category=lifestyle">Lifestyle</a></li>
                    <li class="fListItem"><a href="product_list.php?category=limited">Limited Edition</a></li>
                    <li class="fListItem"><a href="product_list.php?sale=true">Sale Items</a></li>
                </ul>
                <div class="product-highlights">
                    <p><i class="fas fa-star"></i> New Arrivals Weekly</p>
                    <p><i class="fas fa-truck"></i> Free Shipping Over $75</p>
                    <p><i class="fas fa-shield-alt"></i> Authentic Guarantee</p>
                </div>
            </div>
        </div>
        <div class="footerRight">
            <div class="footerRightMenu newsletter-section">
                <h1 class="fMenuTitle">Subscribe to our newsletter</h1>
                <div class="fMail">
                    <input type="email" placeholder="your@email.com" class="fInput" required>
                    <button class="fButton" aria-label="Join Newsletter">
                        <i class="fas fa-paper-plane"></i> Join!
                    </button>
                </div>
                <p class="newsletter-description">
                    <i class="fas fa-gift"></i> Get exclusive deals and early access to new releases!
                </p>
                <div class="newsletter-benefits">
                    <div class="benefit-item">
                        <i class="fas fa-percent"></i>
                        <span>Exclusive Discounts</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-rocket"></i>
                        <span>Early Access</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-star"></i>
                        <span>VIP Treatment</span>
                    </div>
                </div>
            </div>
            <div class="footerRightMenu social-section">
                <h1 class="fMenuTitle">Follow Us</h1>
                <div class="fIcons">
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="social-link facebook" title="Follow us on Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.twitter.com/" target="_blank" rel="noopener noreferrer" class="social-link twitter" title="Follow us on Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" class="social-link instagram" title="Follow us on Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://api.whatsapp.com/send?phone=YOUR_PHONE_NUMBER" target="_blank" rel="noopener noreferrer" class="social-link whatsapp" title="Contact us on WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
                <div class="copyright">
                    <p>&copy; 2024 SneakZone. All rights reserved.</p>
                    <p>Premium sneakers for the modern lifestyle.</p>
                </div>
            </div>
        </div>
</footer>
