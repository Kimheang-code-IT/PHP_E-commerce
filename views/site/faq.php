<?php
// Set the page title for the header
$pageTitle = 'Help & FAQs';

// Include the website header
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section with Gradient Background -->
<section class="hero-faq bg-primary bg-gradient text-white py-5">
    <div class="container text-center py-4">
        <h1 class="display-4 fw-bold mb-3">How can we help you?</h1>
        <p class="lead mb-4">Quick answers to your questions about orders, shipping, returns, and more.</p>
        
        <!-- Search Bar -->
        <div class="col-lg-8 mx-auto mb-4">
            <div class="input-group input-group-lg shadow">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-0" placeholder="Search help articles..." aria-label="Search help articles">
                <button class="btn btn-dark px-4" type="button">Search</button>
            </div>
        </div>
        
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="#shipping" class="btn btn-light btn-sm rounded-pill px-3">Shipping</a>
            <a href="#returns" class="btn btn-light btn-sm rounded-pill px-3">Returns</a>
            <a href="#payments" class="btn btn-light btn-sm rounded-pill px-3">Payments</a>
            <a href="#account" class="btn btn-light btn-sm rounded-pill px-3">Account</a>
            <a href="#orders" class="btn btn-light btn-sm rounded-pill px-3">Orders</a>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container my-5">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="card shadow-sm sticky-top" style="top: 100px;">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Help Topics</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#shipping" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-truck me-2"></i> Shipping Info
                    </a>
                    <a href="#orders" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-cart-check me-2"></i> Order Management
                    </a>
                    <a href="#returns" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-arrow-left-right me-2"></i> Returns & Refunds
                    </a>
                    <a href="#payments" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-credit-card me-2"></i> Payments
                    </a>
                    <a href="#account" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-person-circle me-2"></i> Account Help
                    </a>
                    <a href="#product" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-box-seam me-2"></i> Product Questions
                    </a>
                </div>
            </div>
        </div>
        
        <!-- FAQ Content -->
        <div class="col-lg-9">
            <!-- Shipping Section -->
            <div class="card shadow-sm mb-4" id="shipping">
                <div class="card-header bg-white">
                    <h2 class="h4 mb-0"><i class="bi bi-truck text-primary me-2"></i> Shipping Information</h2>
                </div>
                <div class="card-body">
                    <div class="accordion" id="shippingAccordion">
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="shippingHeadingOne">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#shippingCollapseOne" aria-expanded="false" aria-controls="shippingCollapseOne">
                                    How long does shipping take?
                                </button>
                            </h3>
                            <div id="shippingCollapseOne" class="accordion-collapse collapse" aria-labelledby="shippingHeadingOne" data-bs-parent="#shippingAccordion">
                                <div class="accordion-body">
                                    <p><strong>Standard Shipping:</strong> 3-5 business days for domestic orders.</p>
                                    <p><strong>Express Shipping:</strong> 1-2 business days for domestic orders (additional fee applies).</p>
                                    <p><strong>International Shipping:</strong> 7-21 business days depending on destination and customs processing.</p>
                                    <p>You'll receive a tracking number via email as soon as your order ships. Track your package directly through our website for real-time updates.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="shippingHeadingTwo">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#shippingCollapseTwo" aria-expanded="false" aria-controls="shippingCollapseTwo">
                                    Do you offer international shipping?
                                </button>
                            </h3>
                            <div id="shippingCollapseTwo" class="accordion-collapse collapse" aria-labelledby="shippingHeadingTwo" data-bs-parent="#shippingAccordion">
                                <div class="accordion-body">
                                    <p>Yes, we ship to over 100 countries worldwide. International shipping rates and delivery times vary by destination. During checkout, you'll see the available shipping options and costs for your location.</p>
                                    <p>Please note that international orders may be subject to customs fees, import duties, and taxes which are the responsibility of the recipient.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="shippingHeadingThree">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#shippingCollapseThree" aria-expanded="false" aria-controls="shippingCollapseThree">
                                    How can I track my order?
                                </button>
                            </h3>
                            <div id="shippingCollapseThree" class="accordion-collapse collapse" aria-labelledby="shippingHeadingThree" data-bs-parent="#shippingAccordion">
                                <div class="accordion-body">
                                    <p>Once your order has shipped, you'll receive a confirmation email with a tracking number and link to track your package. You can also:</p>
                                    <ol>
                                        <li>Log in to your account and visit "My Orders"</li>
                                        <li>Click on the order you want to track</li>
                                        <li>Select "Track Package" for real-time updates</li>
                                    </ol>
                                    <p>If you're having trouble tracking your package, please contact our support team with your order number.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Returns Section -->
            <div class="card shadow-sm mb-4" id="returns">
                <div class="card-header bg-white">
                    <h2 class="h4 mb-0"><i class="bi bi-arrow-return-left text-primary me-2"></i> Returns & Refunds</h2>
                </div>
                <div class="card-body">
                    <div class="accordion" id="returnsAccordion">
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="returnsHeadingOne">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#returnsCollapseOne" aria-expanded="false" aria-controls="returnsCollapseOne">
                                    What is your return policy?
                                </button>
                            </h3>
                            <div id="returnsCollapseOne" class="accordion-collapse collapse" aria-labelledby="returnsHeadingOne" data-bs-parent="#returnsAccordion">
                                <div class="accordion-body">
                                    <p>We offer a 30-day return policy for most items. To be eligible for a return:</p>
                                    <ul>
                                        <li>Item must be unused and in original condition</li>
                                        <li>Original packaging must be intact</li>
                                        <li>Proof of purchase is required</li>
                                    </ul>
                                    <p>Some items are final sale and not eligible for return (e.g., clearance items, personalized products). These will be clearly marked on the product page.</p>
                                    <p>To initiate a return, visit the "My Orders" section of your account or contact our support team.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="returnsHeadingTwo">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#returnsCollapseTwo" aria-expanded="false" aria-controls="returnsCollapseTwo">
                                    How long do refunds take to process?
                                </button>
                            </h3>
                            <div id="returnsCollapseTwo" class="accordion-collapse collapse" aria-labelledby="returnsHeadingTwo" data-bs-parent="#returnsAccordion">
                                <div class="accordion-body">
                                    <p>Once we receive your return, please allow:</p>
                                    <ul>
                                        <li><strong>3-5 business days</strong> for us to process the return</li>
                                        <li><strong>5-10 business days</strong> for the refund to appear in your original payment method</li>
                                    </ul>
                                    <p>You'll receive an email confirmation when your return is processed and again when the refund is issued. The exact timing depends on your financial institution.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Orders Section -->
            <div class="card shadow-sm mb-4" id="orders">
                <div class="card-header bg-white">
                    <h2 class="h4 mb-0"><i class="bi bi-cart-check text-primary me-2"></i> Order Management</h2>
                </div>
                <div class="card-body">
                    <div class="accordion" id="ordersAccordion">
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="ordersHeadingOne">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#ordersCollapseOne" aria-expanded="false" aria-controls="ordersCollapseOne">
                                    Can I change or cancel my order?
                                </button>
                            </h3>
                            <div id="ordersCollapseOne" class="accordion-collapse collapse" aria-labelledby="ordersHeadingOne" data-bs-parent="#ordersAccordion">
                                <div class="accordion-body">
                                    <p>We process orders quickly to get them to you as soon as possible. If you need to make changes:</p>
                                    <ul>
                                        <li><strong>Before shipping:</strong> Contact us immediately. We'll do our best to accommodate your request if the order hasn't been processed.</li>
                                        <li><strong>After shipping:</strong> You'll need to wait for the package to arrive and then initiate a return if eligible.</li>
                                    </ul>
                                    <p>To check your order status, log in to your account and visit "My Orders".</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="ordersHeadingTwo">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#ordersCollapseTwo" aria-expanded="false" aria-controls="ordersCollapseTwo">
                                    How do I check my order status?
                                </button>
                            </h3>
                            <div id="ordersCollapseTwo" class="accordion-collapse collapse" aria-labelledby="ordersHeadingTwo" data-bs-parent="#ordersAccordion">
                                <div class="accordion-body">
                                    <p>You can check your order status in several ways:</p>
                                    <ol>
                                        <li><strong>Account:</strong> Log in and visit "My Orders" for complete order history and status</li>
                                        <li><strong>Email:</strong> Check your inbox for order confirmation and shipping notifications</li>
                                        <li><strong>Guest Orders:</strong> Use the order number and email from your confirmation email</li>
                                    </ol>
                                    <p>If you're having trouble locating your order information, please contact our support team.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payments Section -->
            <div class="card shadow-sm mb-4" id="payments">
                <div class="card-header bg-white">
                    <h2 class="h4 mb-0"><i class="bi bi-credit-card text-primary me-2"></i> Payment Information</h2>
                </div>
                <div class="card-body">
                    <div class="accordion" id="paymentsAccordion">
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="paymentsHeadingOne">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#paymentsCollapseOne" aria-expanded="false" aria-controls="paymentsCollapseOne">
                                    What payment methods do you accept?
                                </button>
                            </h3>
                            <div id="paymentsCollapseOne" class="accordion-collapse collapse" aria-labelledby="paymentsHeadingOne" data-bs-parent="#paymentsAccordion">
                                <div class="accordion-body">
                                    <p>We accept the following payment methods:</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul>
                                                <li>Visa</li>
                                                <li>Mastercard</li>
                                                <li>American Express</li>
                                                <li>Discover</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul>
                                                <li>PayPal</li>
                                                <li>Apple Pay</li>
                                                <li>Google Pay</li>
                                                <li>Shop Pay</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p>All transactions are processed through secure payment gateways with encryption to protect your information.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="paymentsHeadingTwo">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#paymentsCollapseTwo" aria-expanded="false" aria-controls="paymentsCollapseTwo">
                                    Is my payment information secure?
                                </button>
                            </h3>
                            <div id="paymentsCollapseTwo" class="accordion-collapse collapse" aria-labelledby="paymentsHeadingTwo" data-bs-parent="#paymentsAccordion">
                                <div class="accordion-body">
                                    <p>Yes, we take security seriously. Our payment processing includes:</p>
                                    <ul>
                                        <li>PCI-DSS compliance for all payment processing</li>
                                        <li>256-bit SSL encryption for all transactions</li>
                                        <li>Tokenization for stored payment methods</li>
                                        <li>Regular security audits</li>
                                    </ul>
                                    <p>We never store complete credit card numbers on our servers. For added security, we recommend using payment services like PayPal or Apple Pay that don't share your financial details with merchants.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Section -->
            <div class="card shadow-sm mb-4" id="account">
                <div class="card-header bg-white">
                    <h2 class="h4 mb-0"><i class="bi bi-person-circle text-primary me-2"></i> Account Help</h2>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accountAccordion">
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="accountHeadingOne">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#accountCollapseOne" aria-expanded="false" aria-controls="accountCollapseOne">
                                    How do I reset my password?
                                </button>
                            </h3>
                            <div id="accountCollapseOne" class="accordion-collapse collapse" aria-labelledby="accountHeadingOne" data-bs-parent="#accountAccordion">
                                <div class="accordion-body">
                                    <p>To reset your password:</p>
                                    <ol>
                                        <li>Click "Login" at the top of any page</li>
                                        <li>Select "Forgot Password?"</li>
                                        <li>Enter the email address associated with your account</li>
                                        <li>Check your email for a password reset link (valid for 24 hours)</li>
                                        <li>Follow the instructions to create a new password</li>
                                    </ol>
                                    <p>If you don't receive the email within 10 minutes, please check your spam folder. Still having trouble? Contact our support team for assistance.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0">
                            <h3 class="accordion-header" id="accountHeadingTwo">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#accountCollapseTwo" aria-expanded="false" aria-controls="accountCollapseTwo">
                                    How do I update my account information?
                                </button>
                            </h3>
                            <div id="accountCollapseTwo" class="accordion-collapse collapse" aria-labelledby="accountHeadingTwo" data-bs-parent="#accountAccordion">
                                <div class="accordion-body">
                                    <p>To update your account details:</p>
                                    <ol>
                                        <li>Log in to your account</li>
                                        <li>Click on your name in the top right corner and select "Account Settings"</li>
                                        <li>Update your personal information, shipping addresses, or payment methods</li>
                                        <li>Click "Save Changes" to confirm your updates</li>
                                    </ol>
                                    <p>Note: Some information (like your email address) may require verification before changes take effect.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Popular Articles -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h4 mb-0"><i class="bi bi-star-fill text-warning me-2"></i> Popular Help Articles</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100">
                                <h3 class="h5"><a href="#" class="text-decoration-none">How to Track Your Order</a></h3>
                                <p class="text-muted small">Step-by-step guide to tracking your package from shipment to delivery.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100">
                                <h3 class="h5"><a href="#" class="text-decoration-none">Understanding Return Shipping Costs</a></h3>
                                <p class="text-muted small">Learn who pays for return shipping in different scenarios.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100">
                                <h3 class="h5"><a href="#" class="text-decoration-none">Creating a Wishlist</a></h3>
                                <p class="text-muted small">Save items for later and share with friends and family.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100">
                                <h3 class="h5"><a href="#" class="text-decoration-none">Gift Wrapping Options</a></h3>
                                <p class="text-muted small">How to add gift wrapping and include a personalized message.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact CTA -->
    <div class="text-center mt-5 p-5 bg-light bg-gradient rounded-3 shadow-sm">
        <div class="p-3">
            <h2 class="display-6 fw-bold mb-3">Still Need Help?</h2>
            <p class="lead mb-4">Our customer support team is available 24/7 to answer your questions.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="contact.php" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-envelope-fill me-2"></i> Contact Support
                </a>
                <a href="tel:+18005551234" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="bi bi-telephone-fill me-2"></i> Call Us
                </a>
                <button class="btn btn-outline-dark btn-lg px-4" data-bs-toggle="modal" data-bs-target="#liveChatModal">
                    <i class="bi bi-chat-dots-fill me-2"></i> Live Chat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Live Chat Modal (hidden by default) -->
<div class="modal fade" id="liveChatModal" tabindex="-1" aria-labelledby="liveChatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="liveChatModalLabel">Live Chat Support</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4">
                    <i class="bi bi-chat-square-text-fill display-4 text-primary mb-3"></i>
                    <h4>Chat with Us</h4>
                    <p class="text-muted">Our support team is available 24/7 to assist you.</p>
                    
                    <div class="mt-4">
                        <button class="btn btn-primary px-4 me-2">
                            <i class="bi bi-facebook me-2"></i> Facebook Messenger
                        </button>
                        <button class="btn btn-success px-4">
                            <i class="bi bi-whatsapp me-2"></i> WhatsApp
                        </button>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        <p class="small text-muted">Prefer email? <a href="contact.php">Send us a message</a> and we'll respond within 24 hours.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the website footer
require_once __DIR__ . '/../includes/footer.php';
?>

<style>
    .hero-faq {
        background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
        border-radius: 0 0 20px 20px;
    }
    
    .accordion-button:not(.collapsed) {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(13, 110, 253, 0.25);
    }
    
    .card-header {
        padding: 1rem 1.5rem;
    }
    
    .sticky-top {
        position: -webkit-sticky;
        position: sticky;
        top: 100px;
    }
</style>