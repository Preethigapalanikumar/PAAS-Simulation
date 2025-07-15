<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaaS Service Portal - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            padding: 20px 0;
        }

        /* Signup Button Styles */
        .signup-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .signup-button:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            color: #5a67d8;
        }

        .signup-button::before {
            content: '👤';
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .signup-button {
                top: 15px;
                right: 15px;
                padding: 10px 18px;
                font-size: 13px;
            }
        }

        .floating-particles {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            display: block;
            pointer-events: none;
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s infinite linear;
        }

        @keyframes float {
            0% {
                opacity: 0;
                transform: translateY(100vh) rotate(0deg);
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: translateY(-100vh) rotate(360deg);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            max-width: 700px;
            width: calc(100% - 40px);
            margin: 0 auto;
            position: relative;
            z-index: 10;
            transform: translateY(0);
            animation: slideIn 0.8s ease-out;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .instruction-banner {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            animation: pulse 2s infinite;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .service-tiers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .tier-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 3px solid transparent;
            padding: 20px 15px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            min-height: 180px;
        }

        .tier-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .tier-card:hover::before {
            left: 100%;
        }

        .tier-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .tier-card.selected {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }
        
        .tier-card.basic {
            border-color: #6c757d;
            background: linear-gradient(135deg, #f8f9fa 0%, #dee2e6 100%);
        }
        .tier-card.basic.selected { 
            background: linear-gradient(135deg, #6c757d, #495057); 
            color: white;
        }
        
        .tier-card.standard {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
        .tier-card.standard.selected { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            color: white;
        }
        
        .tier-card.premium {
            border-color: #007bff;
            background: linear-gradient(135deg, #cce5ff 0%, #b3d9ff 100%);
        }
        .tier-card.premium.selected { 
            background: linear-gradient(135deg, #007bff, #0056b3); 
            color: white;
        }
        
        .tier-card.enterprise {
            border-color: #6f42c1;
            background: linear-gradient(135deg, #e2d9f3 0%, #d1c7e5 100%);
        }
        .tier-card.enterprise.selected { 
            background: linear-gradient(135deg, #6f42c1, #5a2d91); 
            color: white;
        }
        
        .tier-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tier-features {
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 12px;
            min-height: 65px;
        }
        
        .tier-price {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .tier-sla {
            font-size: 11px;
            opacity: 0.8;
            font-weight: 500;
        }

        .btn-login {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: not-allowed;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            margin-top: 20px;
            opacity: 0.7;
        }

        .btn-login.enabled {
            background: linear-gradient(135deg, #28a745, #20c997);
            cursor: pointer;
            opacity: 1;
            animation: pulse 2s infinite;
        }

        .selected-tier-info {
            background: rgba(102, 126, 234, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
            text-align: left;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .selected-tier-info.show {
            opacity: 1;
            transform: translateY(0);
        }

        .tier-capabilities {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 8px;
            margin-top: 10px;
            font-size: 11px;
        }

        .capability {
            background: rgba(255, 255, 255, 0.7);
            padding: 4px 8px;
            border-radius: 5px;
            text-align: center;
        }

        .demo-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: pulse 2s infinite;
        }

        .account-required {
            background: rgba(231, 76, 60, 0.1);
            border: 2px solid #e74c3c;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            transition: all 0.3s ease;
        }

        .account-required.signed-up {
            background: rgba(40, 167, 69, 0.1);
            border: 2px solid #28a745;
        }

        .account-required h3 {
            color: #e74c3c;
            margin-bottom: 10px;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .account-required.signed-up h3 {
            color: #28a745;
        }

        .account-required p {
            color: #c0392b;
            margin-bottom: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .account-required.signed-up p {
            color: #218838;
        }

        .create-account-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .create-account-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .footer-info {
            margin-top: 25px;
            font-size: 11px;
            color: #7f8c8d;
            line-height: 1.4;
        }

        .redirect-loading {
            display: none;
            background: rgba(102, 126, 234, 0.1);
            border: 2px solid #667eea;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }

        .redirect-loading.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px 0;
            }
            
            .service-tiers {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .login-container {
                padding: 25px 20px;
                width: calc(100% - 20px);
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .tier-card {
                min-height: 160px;
                padding: 15px 12px;
            }
            
            .tier-capabilities {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>
    <!-- Signup Button in Top Right Corner -->
    <a href="index.php" class="signup-button">
        Sign Up
    </a>

    <div class="floating-particles" id="particles"></div>
    
    <div class="login-container">
        <div class="demo-badge">Demo Portal</div>
        
        <div class="header">
            <h1>🚀 PaaS Service Portal</h1>
            <p>Select your service tier to access the hybrid scaling dashboard</p>
            <div class="instruction-banner" id="instructionBanner">
                🔒 Account Required! Please sign up first to access the dashboard.
            </div>
        </div>
        
        <div class="service-tiers">
            <div class="tier-card basic" data-tier="basic">
                <div class="tier-name">Basic</div>
                <div class="tier-features">
                    Standard monitoring<br>
                    Basic auto-scaling
                </div>
                <div class="tier-price">$29/month</div>
            </div>
            
            <div class="tier-card standard" data-tier="standard">
                <div class="tier-name">Standard</div>
                <div class="tier-features">
                    Enhanced monitoring<br>
                    24/7 chat support<br>
                </div>
                <div class="tier-price">$79/month</div>
            </div>
            
            <div class="tier-card premium" data-tier="premium">
                <div class="tier-name">Premium</div>
                <div class="tier-features">
                    Advanced monitoring<br>
                    High availability setup
                </div>
                <div class="tier-price">$199/month</div>
            </div>
            
            <div class="tier-card enterprise" data-tier="enterprise">
                <div class="tier-name">Enterprise</div>
                <div class="tier-features">
                    Full monitoring suite<br>
                    Dedicated support team<br>
                    Multi-region deployment
                </div>
                <div class="tier-price">$499/month</div>
            </div>
        </div>

        <div class="selected-tier-info" id="tierInfo">
            <strong>Selected Tier: <span id="selectedTierName"></span></strong>
            <div class="tier-capabilities" id="tierCapabilities"></div>
        </div>

        <div class="account-required" id="accountRequired">
            <h3>🔐 Account Required</h3>
            <p>You need to create an account before accessing the dashboard</p>
        </div>

        <div class="redirect-loading" id="redirectLoading">
            <div class="spinner"></div>
            <h3>Redirecting to Dashboard...</h3>
            <p>Please wait while we prepare your <span id="redirectTierName"></span> dashboard</p>
        </div>
        
        <button class="btn-login" id="loginBtn" disabled>
            Account Required - Please Sign Up First
        </button>

        <div class="footer-info">
            <strong>Getting Started:</strong><br>
            1. Click the "Sign Up" button in the top right corner to create your account<br>
            2. Select your preferred service tier (Basic, Standard, Premium, or Enterprise)<br>
            3. Complete the registration process to access your dashboard
        </div>
    </div>

    <script>
        // Dashboard URLs for each tier
        const dashboardUrls = {
            basic: 'basic.html',
            standard: 'standard.html', 
            premium: 'prem.html',
            enterprise: 'enterprise.html'
        };

        // Global variables
        let selectedTier = null;
        let isRedirecting = false;

        // Service tier data
        const tierData = {
            basic: {
                name: 'Basic Tier',
                capabilities: [
                    'Single Region',
                    '2-5 Instances',
                    'Standard Support',
                    'Basic Monitoring'
                   
                ]
            },
            standard: {
                name: 'Standard Tier',
                capabilities: [
                    'Multi-Region',
                    '5-10 Instances',
                    '24/7 Chat Support',
                    'Enhanced Monitoring'
                ]
            },
            premium: {
                name: 'Premium Tier',
                capabilities: [
                    'Global Deployment',
                    '10-15 Instances',
                    'Priority Support'
                ]
            },
            enterprise: {
                name: 'Enterprise Tier',
                capabilities: [
                    '15-20 Instances',
                    'Dedicated Support',
                    'Full Observability',
                    'Custom SLAs',
                ]
            }
        };

        // Initialize the page
        function initializePage() {
            // Clear any existing redirect state
            clearRedirectState();
            
            // Create particles
            createParticles();
            
            // Check signup status
            checkSignupStatus();
            
            // Setup event listeners
            setupEventListeners();
            
            // Reset any loading states
            resetLoadingState();
        }

        // Clear redirect state from localStorage
        function clearRedirectState() {
            // Remove any temporary redirect flags
            const keysToRemove = ['isRedirecting', 'redirectStartTime'];
            keysToRemove.forEach(key => {
                if (key in localStorage) {
                    localStorage.removeItem(key);
                }
            });
        }

        // Reset loading state
        function resetLoadingState() {
            const redirectLoading = document.getElementById('redirectLoading');
            const loginBtn = document.getElementById('loginBtn');
            
            // Hide loading animation
            redirectLoading.classList.remove('show');
            
            // Show login button
            loginBtn.style.display = 'block';
            
            // Reset redirect flag
            isRedirecting = false;
        }

        // Particle animation
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            
            // Clear existing particles first
            particlesContainer.innerHTML = '';
            
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Check if user has signed up
        function checkSignupStatus() {
            const isSignedUp = localStorage.getItem('paasPortalSignedUp') === 'true';
            const loginBtn = document.getElementById('loginBtn');
            const accountRequired = document.getElementById('accountRequired');
            const instructionBanner = document.getElementById('instructionBanner');
            
            if (isSignedUp) {
                loginBtn.disabled = false;
                loginBtn.textContent = 'Access Your Dashboard';
                loginBtn.classList.add('enabled');
                
                // Update UI elements
                accountRequired.classList.add('signed-up');
                accountRequired.querySelector('h3').textContent = '✅ Account Verified';
                accountRequired.querySelector('p').textContent = 'Select your tier and click below to access your dashboard';
                
                instructionBanner.textContent = '✅ Account Verified! Select your tier to continue';
                instructionBanner.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            // Tier selection logic
            document.querySelectorAll('.tier-card').forEach(card => {
                card.addEventListener('click', function() {
                    if (isRedirecting) return; // Prevent selection during redirect
                    
                    // Remove previous selection
                    document.querySelectorAll('.tier-card').forEach(c => c.classList.remove('selected'));
                    
                    // Add selection to clicked card
                    this.classList.add('selected');
                    selectedTier = this.dataset.tier;
                    
                    // Update UI
                    updateTierInfo();
                });
            });

            // Login button event
            document.getElementById('loginBtn').addEventListener('click', function(e) {
                if (this.disabled) {
                    e.preventDefault();
                    alert('Please create an account first by clicking the "Sign Up" button in the top right corner.');
                } else {
                    if (!selectedTier) {
                        alert('Please select a service tier before proceeding.');
                        return;
                    }
                    
                    if (isRedirecting) return; // Prevent multiple clicks
                    
                    // Redirect to the appropriate dashboard
                    redirectToDashboard(selectedTier);
                }
            });
        }

        function updateTierInfo() {
            const tierInfo = document.getElementById('tierInfo');
            const tierName = document.getElementById('selectedTierName');
            const tierCapabilities = document.getElementById('tierCapabilities');
            
            if (selectedTier) {
                tierName.textContent = tierData[selectedTier].name;
                tierCapabilities.innerHTML = tierData[selectedTier].capabilities
                    .map(cap => `<div class="capability">${cap}</div>`)
                    .join('');
                
                tierInfo.classList.add('show');
            } else {
                tierInfo.classList.remove('show');
            }
        }

        // Function to redirect to dashboard
        function redirectToDashboard(tier) {
            if (isRedirecting) return; // Prevent multiple redirects
            
            isRedirecting = true;
            
            const redirectLoading = document.getElementById('redirectLoading');
            const redirectTierName = document.getElementById('redirectTierName');
            const loginBtn = document.getElementById('loginBtn');
            
            // Show loading animation
            redirectTierName.textContent = tierData[tier].name;
            redirectLoading.classList.add('show');
            loginBtn.style.display = 'none';
            
            // Store selected tier and redirect state
            localStorage.setItem('selectedTier', tier);
            localStorage.setItem('isRedirecting', 'true');
            localStorage.setItem('redirectStartTime', Date.now().toString());
            
            // Simulate loading time and redirect
            setTimeout(() => {
                if (isRedirecting) { // Double-check we're still redirecting
                    window.location.href = dashboardUrls[tier];
                }
            }, 2000);
        }

        // Handle page visibility change (for when user comes back)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page became visible again, reset state
                resetLoadingState();
            }
        });

        // Handle browser back/forward navigation
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was restored from cache, reset everything
                initializePage();
            }
        });

        // Handle before page unload
        window.addEventListener('beforeunload', function() {
            // Clear redirect state when leaving the page
            clearRedirectState();
        });

        // Auto-enable login for demo purposes and initialize
        localStorage.setItem('paasPortalSignedUp', 'true');
        
        // Initialize everything when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }
    </script>
</body>
</html>