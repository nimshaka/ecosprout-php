<?php
/**
 * EcoSprout – Landing Page (index.php)
 * Public-facing homepage for the plant nursery & gardening services.
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

// Redirect already-logged-in users to their dashboard
if (isLoggedIn()) {
    $dest = match($_SESSION['user_role']) {
        'admin' => '/admin/dashboard.php',
        'staff' => '/staff/dashboard.php',
        default => '/customer/dashboard.php',
    };
    redirect(getBaseUrl() . $dest);
}

// Fetch a small selection of featured plants for the homepage strip
$pdo = getPDO();
$stmt = $pdo->query("SELECT id, plant_name, botanical_name, category, price, stock_quantity, image
                     FROM plants ORDER BY RAND() LIMIT 8");
$featuredPlants = $stmt->fetchAll();

$base = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EcoSprout – Sri Lanka's premier plant nursery and professional gardening services in Kegalle. Discover plants, book services, and join workshops.">
    <title>EcoSprout – Plant Nursery & Gardening Services | Kegalle, Sri Lanka</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>

<!-- ══ Navigation ══════════════════════════════════════════════ -->
<nav class="landing-navbar navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">
            <span class="brand-icon me-2" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:var(--eco-accent);border-radius:10px;font-size:1.1rem;color:#fff;">
                <i class="bi bi-tree-fill"></i>
            </span>
            EcoSprout
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav">
            <span style="color:#fff;font-size:1.5rem;"><i class="bi bi-list"></i></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNav">
            <ul class="navbar-nav mx-auto gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="#plants">Plants</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#workshops">Workshops</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <div class="d-flex gap-2 mt-3 mt-lg-0">
                <a href="<?= $base ?>/login.php" class="btn btn-outline-light btn-sm px-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
                <a href="<?= $base ?>/register.php" class="btn btn-sm px-3" style="background:var(--eco-accent);color:#fff;border:none;">
                    <i class="bi bi-person-plus me-1"></i> Register
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ══ Hero Section ════════════════════════════════════════════ -->
<section class="hero-section" id="home">
    <!-- Decorative floating leaves -->
    <span class="leaf-float" style="top:10%;right:5%;"><i class="bi bi-tree-fill"></i></span>
    <span class="leaf-float" style="top:60%;right:15%;"><i class="bi bi-flower1"></i></span>
    <span class="leaf-float" style="bottom:15%;left:3%;"><i class="bi bi-flower2"></i></span>

    <div class="container hero-content">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="bi bi-geo-alt-fill"></i>
                    Kegalle, Sri Lanka
                </div>
                <h1 class="hero-title">
                    Grow Your <span>Green</span><br>Paradise
                </h1>
                <p class="hero-subtitle">
                    EcoSprout is your trusted plant nursery and professional gardening services partner. 
                    Discover hundreds of plant varieties, book expert gardening services, and join our 
                    educational workshops — all in one place.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= $base ?>/register.php"
                       class="btn btn-lg px-4 py-2 fw-semibold"
                       style="background:#fff;color:var(--eco-primary);border:none;">
                        <i class="bi bi-sprout me-2"></i> Get Started Free
                    </a>
                    <a href="#plants"
                       class="btn btn-lg px-4 py-2 fw-semibold"
                       style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.4);">
                        <i class="bi bi-search me-2"></i> Browse Plants
                    </a>
                </div>

                <!-- Trust stats -->
                <div class="d-flex flex-wrap gap-4 mt-4">
                    <div style="color:rgba(255,255,255,0.9)">
                        <div style="font-size:1.75rem;font-weight:800;font-family:'Poppins',sans-serif;">200+</div>
                        <div style="font-size:0.8rem;opacity:.7;">Plant Varieties</div>
                    </div>
                    <div style="color:rgba(255,255,255,0.9)">
                        <div style="font-size:1.75rem;font-weight:800;font-family:'Poppins',sans-serif;">50+</div>
                        <div style="font-size:0.8rem;opacity:.7;">Happy Customers</div>
                    </div>
                    <div style="color:rgba(255,255,255,0.9)">
                        <div style="font-size:1.75rem;font-weight:800;font-family:'Poppins',sans-serif;">6</div>
                        <div style="font-size:0.8rem;opacity:.7;">Expert Services</div>
                    </div>
                </div>
            </div>

            <!-- Right: decorative plant emoji collage -->
            <div class="col-lg-6 d-none d-lg-flex justify-content-center">
                <div style="position:relative;width:420px;height:420px;">
                    <!-- Central circle -->
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                                width:300px;height:300px;border-radius:50%;
                                background:rgba(255,255,255,0.12);border:2px solid rgba(255,255,255,0.2);
                                display:flex;align-items:center;justify-content:center;font-size:8rem;">
                        🌿
                    </div>
                    <!-- Orbiting elements -->
                    <div style="position:absolute;top:10%;left:10%;font-size:3.5rem;animation:floatLeaf 5s ease-in-out infinite;">🌺</div>
                    <div style="position:absolute;top:10%;right:10%;font-size:3rem;animation:floatLeaf 7s ease-in-out infinite 1s;">🌱</div>
                    <div style="position:absolute;bottom:10%;left:15%;font-size:3rem;animation:floatLeaf 6s ease-in-out infinite 2s;">🌻</div>
                    <div style="position:absolute;bottom:10%;right:15%;font-size:3.5rem;animation:floatLeaf 8s ease-in-out infinite 0.5s;">🪴</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Feature Strip ═══════════════════════════════════════════ -->
<section class="feature-strip">
    <div class="container">
        <div class="row justify-content-center g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="feature-item justify-content-center">
                    <i class="bi bi-truck fs-5"></i>
                    <span>Free Delivery Over LKR 2,000</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item justify-content-center">
                    <i class="bi bi-shield-check fs-5"></i>
                    <span>Healthy Plant Guarantee</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item justify-content-center">
                    <i class="bi bi-headset fs-5"></i>
                    <span>Expert Care Advice</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item justify-content-center">
                    <i class="bi bi-leaf fs-5"></i>
                    <span>Eco-Friendly Practices</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Featured Plants ═════════════════════════════════════════ -->
<section class="py-5 bg-white" id="plants">
    <div class="container">
        <div class="section-header">
            <span class="section-label"><i class="bi bi-flower1 me-1"></i> Our Collection</span>
            <h2>Featured Plants</h2>
            <p class="text-muted">From air-purifying indoor plants to edible herbs for your kitchen garden</p>
        </div>

        <?php if (empty($featuredPlants)): ?>
        <p class="text-center text-muted">No plants available yet. Check back soon!</p>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featuredPlants as $plant): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="plant-card">
                    <?php if (!empty($plant['image']) && file_exists(__DIR__ . '/assets/images/plants/' . $plant['image'])): ?>
                        <img src="<?= $base ?>/assets/images/plants/<?= htmlspecialchars($plant['image']) ?>"
                             alt="<?= htmlspecialchars($plant['plant_name']) ?>"
                             class="plant-card-img">
                    <?php else: ?>
                        <div class="plant-card-img-placeholder">
                            <?= match($plant['category']) {
                                'indoor'     => '🪴',
                                'outdoor'    => '🌿',
                                'ornamental' => '🌺',
                                'edible'     => '🌿',
                                default      => '🌱'
                            } ?>
                        </div>
                    <?php endif; ?>
                    <div class="plant-card-body">
                        <?= categoryBadge($plant['category']) ?>
                        <div class="plant-card-title mt-2"><?= htmlspecialchars($plant['plant_name']) ?></div>
                        <div class="plant-botanical"><?= htmlspecialchars($plant['botanical_name'] ?? '') ?></div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="plant-price"><?= formatLKR($plant['price']) ?></div>
                            <?php if ($plant['stock_quantity'] > 0): ?>
                                <span class="badge bg-success-subtle text-success">In Stock</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $base ?>/register.php" class="btn btn-eco-primary btn-sm w-100 mt-3">
                            <i class="bi bi-cart-plus me-1"></i> Add to Cart
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= $base ?>/register.php" class="btn btn-eco-primary px-4">
                <i class="bi bi-grid me-1"></i> View Full Catalogue
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══ Services Section ════════════════════════════════════════ -->
<section class="py-5" style="background:var(--eco-gray-50);" id="services">
    <div class="container">
        <div class="section-header">
            <span class="section-label"><i class="bi bi-tools me-1"></i> What We Offer</span>
            <h2>Professional Gardening Services</h2>
            <p class="text-muted">Expert care for your garden, from design to ongoing maintenance</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-map"></i></div>
                    <h5>Garden Design &amp; Landscaping</h5>
                    <p class="text-muted small">Full-service garden design tailored to Sri Lanka's tropical climate. We create stunning, sustainable gardens.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-scissors"></i></div>
                    <h5>Pruning &amp; Maintenance</h5>
                    <p class="text-muted small">Regular monthly upkeep — weeding, pruning, fertilizing, and pest management to keep your garden pristine.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-droplet-half"></i></div>
                    <h5>Irrigation Systems</h5>
                    <p class="text-muted small">Water-efficient drip and sprinkler system installation designed to save water and reduce your utility bills.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-tree"></i></div>
                    <h5>Tree Care</h5>
                    <p class="text-muted small">Expert tree pruning, shaping, and hazard removal. All sizes handled safely by trained professionals.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-patch-check"></i></div>
                    <h5>Lawn Management</h5>
                    <p class="text-muted small">Professional mowing, aeration, and top-dressing using eco-friendly methods for a lush green lawn.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-recycle"></i></div>
                    <h5>Composting &amp; Soil</h5>
                    <p class="text-muted small">Organic soil enrichment and composting programmes to maximise plant health the natural way.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="<?= $base ?>/register.php" class="btn btn-eco-primary px-4">
                <i class="bi bi-calendar-check me-1"></i> Book a Service
            </a>
        </div>
    </div>
</section>

<!-- ══ Workshops Section ═══════════════════════════════════════ -->
<section class="py-5 bg-white" id="workshops">
    <div class="container">
        <div class="section-header">
            <span class="section-label"><i class="bi bi-mortarboard me-1"></i> Learn &amp; Grow</span>
            <h2>Gardening Workshops &amp; Events</h2>
            <p class="text-muted">Hands-on learning experiences led by expert horticulturists</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="eco-card text-center p-4">
                    <div class="fs-1 mb-3">🌱</div>
                    <h5 class="fw-bold">Introduction to Home Gardening</h5>
                    <p class="text-muted small">Perfect for beginners — soil prep, seed planting, and basic plant care. Starter plant kit included!</p>
                    <span class="badge bg-success">LKR 500</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="eco-card text-center p-4">
                    <div class="fs-1 mb-3">🌺</div>
                    <h5 class="fw-bold">Tropical Plant Care Masterclass</h5>
                    <p class="text-muted small">Intermediate workshop on caring for Sri Lanka's native tropical plants. Pruning, fertilizing, pest ID.</p>
                    <span class="badge bg-success">LKR 750</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="eco-card text-center p-4">
                    <div class="fs-1 mb-3">🥦</div>
                    <h5 class="fw-bold">Kitchen Garden &amp; Edible Plants</h5>
                    <p class="text-muted small">Grow your own food! Herbs, vegetables, and fruits for small home gardens in Sri Lanka.</p>
                    <span class="badge bg-success">LKR 600</span>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="<?= $base ?>/register.php" class="btn btn-eco-primary px-4">
                <i class="bi bi-calendar-plus me-1"></i> Register for a Workshop
            </a>
        </div>
    </div>
</section>

<!-- ══ Why Choose EcoSprout ════════════════════════════════════ -->
<section class="py-5" style="background:linear-gradient(135deg,var(--eco-primary-dark),var(--eco-primary));">
    <div class="container text-center text-white">
        <h2 class="fw-bold mb-2" style="color:#fff;">Why Choose EcoSprout?</h2>
        <p class="mb-5" style="color:rgba(255,255,255,0.75);">Everything you need for a thriving garden — in one trusted platform</p>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="fs-2 mb-2">🌿</div>
                <h6 class="fw-bold text-white">100% Locally Grown</h6>
                <p class="small" style="color:rgba(255,255,255,0.7);">All plants are locally sourced and acclimatised to Sri Lanka's climate.</p>
            </div>
            <div class="col-md-3">
                <div class="fs-2 mb-2">👨‍🌾</div>
                <h6 class="fw-bold text-white">Expert Team</h6>
                <p class="small" style="color:rgba(255,255,255,0.7);">Our horticulturists have 10+ years of experience in tropical plant care.</p>
            </div>
            <div class="col-md-3">
                <div class="fs-2 mb-2">♻️</div>
                <h6 class="fw-bold text-white">Eco-Friendly</h6>
                <p class="small" style="color:rgba(255,255,255,0.7);">We use organic fertilizers and sustainable practices in everything we do.</p>
            </div>
            <div class="col-md-3">
                <div class="fs-2 mb-2">💬</div>
                <h6 class="fw-bold text-white">Ongoing Support</h6>
                <p class="small" style="color:rgba(255,255,255,0.7);">Submit plant-care queries and get responses from our expert team.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ CTA Banner ══════════════════════════════════════════════ -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3">Ready to Start Your Green Journey?</h2>
                <p class="text-muted mb-4">Join hundreds of plant enthusiasts in Kegalle who trust EcoSprout for all their gardening needs.</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?= $base ?>/register.php" class="btn btn-eco-primary btn-lg px-5">
                        <i class="bi bi-person-plus me-2"></i> Create Free Account
                    </a>
                    <a href="<?= $base ?>/login.php" class="btn btn-outline-success btn-lg px-5">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Footer ══════════════════════════════════════════════════ -->
<footer class="landing-footer" id="contact">
    <div class="container">
        <div class="row g-4">
            <!-- Brand -->
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:var(--eco-accent);border-radius:10px;font-size:1.1rem;color:#fff;">
                        <i class="bi bi-tree-fill"></i>
                    </span>
                    <span style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.25rem;color:#fff;">EcoSprout</span>
                </div>
                <p class="small" style="color:rgba(255,255,255,0.65);">
                    Sri Lanka's trusted plant nursery and professional gardening services provider. 
                    Bringing nature closer to every home in Kegalle and beyond.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-sm-6 col-lg-2">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#plants">Plant Catalogue</a></li>
                    <li class="mb-2"><a href="#services">Services</a></li>
                    <li class="mb-2"><a href="#workshops">Workshops</a></li>
                    <li class="mb-2"><a href="<?= $base ?>/register.php">Register</a></li>
                    <li class="mb-2"><a href="<?= $base ?>/login.php">Login</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-sm-6 col-lg-3">
                <h6 class="fw-bold mb-3">Our Services</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#services">Garden Design</a></li>
                    <li class="mb-2"><a href="#services">Tree Pruning</a></li>
                    <li class="mb-2"><a href="#services">Lawn Care</a></li>
                    <li class="mb-2"><a href="#services">Irrigation</a></li>
                    <li class="mb-2"><a href="#services">Composting</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-3">
                <h6 class="fw-bold mb-3">Contact Us</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <i class="bi bi-geo-alt-fill me-2" style="color:var(--eco-accent);"></i>
                        No. 42, Rajapihilla Road,<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;Kegalle, Sri Lanka
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-telephone-fill me-2" style="color:var(--eco-accent);"></i>
                        +94 35 222 1234
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-envelope-fill me-2" style="color:var(--eco-accent);"></i>
                        info@ecosprout.lk
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock-fill me-2" style="color:var(--eco-accent);"></i>
                        Mon–Sat: 7:00 AM – 6:00 PM
                    </li>
                </ul>
            </div>
        </div>

        <hr class="footer-divider my-4">

        <div class="row align-items-center">
            <div class="col-md-6 small" style="color:rgba(255,255,255,0.5);">
                &copy; <?= date('Y') ?> EcoSprout. All rights reserved.
            </div>
            <div class="col-md-6 text-md-end small" style="color:rgba(255,255,255,0.5);">
                Plant Nursery &amp; Gardening Services Management System
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
