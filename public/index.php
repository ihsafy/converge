<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CONVERGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #818cf8;
            --bg-surface: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
        }

        body {
            background: #ffffff;
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow-x: hidden;
            color: var(--text-main);
        }

        /* --- 1. GLASS NAVBAR --- */
        .landing-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1200px;
            margin: 0 auto;
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-buttons { display: flex; align-items: center; gap: 20px; }

        .btn-login {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        .btn-login:hover { color: var(--primary); }

        .btn-join {
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
            background: var(--primary-dark);
        }

        /* --- 2. HERO SECTION (Split Layout) --- */
        .hero-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 5% 6rem;
            display: grid;
            grid-template-columns: 1fr 1fr; /* Split screen */
            align-items: center;
            gap: 4rem;
            position: relative;
        }

        /* Decorative Background Blob */
        .bg-blob {
            position: absolute;
            top: -50px;
            right: -100px;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: -1;
        }

        .hero-content { z-index: 2; }

        .hero-badge {
            background: #eef2ff;
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 1.5rem;
            border: 1px solid #e0e7ff;
        }

        .hero-title {
            font-size: 3.5rem;
            line-height: 1.1;
            font-weight: 900;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            color: #111827;
        }
        .hero-title span { color: var(--primary); }

        .hero-text {
            font-size: 1.2rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            line-height: 1.6;
            max-width: 500px;
        }

        .hero-cta-group { display: flex; gap: 15px; }

        .cta-primary {
            padding: 16px 32px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        .cta-primary:hover { transform: translateY(-3px); background: var(--primary-dark); }

        .cta-secondary {
            padding: 16px 32px;
            background: white;
            color: var(--text-main);
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .cta-secondary:hover { background: #f9fafb; border-color: #d1d5db; }

        /* --- 3. CSS GRAPHIC (Right Side) --- */
        .hero-visual {
            position: relative;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Simulating a dashboard card stack with CSS only */
        .card-stack {
            position: relative;
            width: 320px;
            height: 200px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 50px -10px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.05);
            z-index: 2;
            padding: 20px;
            animation: float 6s ease-in-out infinite;
        }
        
        .card-stack::before { /* Card Behind */
            content: '';
            position: absolute;
            top: -20px; left: 20px; right: 20px; bottom: 0;
            background: #e0e7ff;
            border-radius: 16px;
            z-index: -1;
            opacity: 0.6;
        }
        
        .card-row { height: 12px; background: #f3f4f6; margin-bottom: 12px; border-radius: 4px; }
        .w-70 { width: 70%; } .w-40 { width: 40%; } .w-90 { width: 90%; }
        
        .floating-icon {
            position: absolute;
            width: 60px; height: 60px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            z-index: 3;
        }
        .icon-1 { top: -30px; right: -20px; animation: float 5s ease-in-out infinite 1s; }
        .icon-2 { bottom: -40px; left: -30px; animation: float 7s ease-in-out infinite 0.5s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        /* --- 4. STATS BAR --- */
        .stats-bar {
            background: #111827;
            padding: 3rem 0;
            color: white;
            text-align: center;
        }
        .stats-grid {
            max-width: 1000px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
        }
        .stat-item h2 { font-size: 2.5rem; margin: 0; color: var(--accent); }
        .stat-item p { margin: 5px 0 0; color: #9ca3af; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }

        /* --- 5. FEATURES --- */
        .features-section { padding: 6rem 5%; background: #f9fafb; }
        .section-header { text-align: center; max-width: 600px; margin: 0 auto 4rem; }
        .section-header h2 { font-size: 2.2rem; font-weight: 800; margin-bottom: 10px; color: #1f2937; }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px; margin: 0 auto;
        }
        .feature-card {
            background: white; padding: 2.5rem; border-radius: 16px;
            border: 1px solid #e5e7eb; transition: 0.3s;
        }
        .feature-card:hover { transform: translateY(-10px); border-color: var(--primary); box-shadow: 0 15px 30px rgba(0,0,0,0.05); }
        .icon-box {
            width: 55px; height: 55px; background: #eef2ff; color: var(--primary);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1.5rem;
        }

        /* --- 6. FOOTER --- */
        .footer { background: white; border-top: 1px solid #f3f4f6; padding: 4rem 5% 2rem; text-align: center; }
        .developer-tag {
            display: inline-flex; align-items: center; gap: 6px;
            background: #f3f4f6; padding: 6px 14px; border-radius: 20px;
            font-size: 0.8rem; color: #4b5563; margin-top: 20px;
        }
        .developer-tag a { color: var(--primary); font-weight: 700; text-decoration: none; }

        /* Mobile */
        @media (max-width: 900px) {
            .hero-wrapper { grid-template-columns: 1fr; text-align: center; padding-top: 3rem; }
            .hero-text { margin: 0 auto 2.5rem; }
            .hero-cta-group { justify-content: center; }
            .hero-visual { display: none; } /* Hide complex visual on mobile */
            .stats-grid { grid-template-columns: 1fr; gap: 40px; }
            .hero-title { font-size: 2.5rem; }
        }
    </style>
</head>
<body class="animate-fade-in">

    <nav class="landing-nav">
        <a href="index.php" class="logo">
            <span style="color:#4f46e5;">❖</span> CONVERGE.
        </a>
        <div class="nav-buttons">
            <a href="login.php" class="btn-login">Log In</a>
            <a href="register.php" class="btn-join">Get Started</a>
        </div>
    </nav>

    <div class="hero-wrapper">
        <div class="bg-blob"></div>
        
        <div class="hero-content animate-fade-up">
            <div class="hero-badge">🚀 The #1 Club Management System</div>
            <h1 class="hero-title">Where Community <br> Meets <span>Innovation</span>.</h1>
            <p class="hero-text">
                Streamline events, manage memberships, and connect with professionals. 
                The all-in-one platform designed for modern communities.
            </p>
            <div class="hero-cta-group">
                <a href="register.php" class="cta-primary">Join Now &rarr;</a>
                <a href="login.php" class="cta-secondary">Member Login</a>
            </div>
        </div>

        <div class="hero-visual animate-fade-up delay-1">
            <div class="card-stack">
                <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <div style="width:40px; height:40px; background:#f3f4f6; border-radius:50%;"></div>
                    <div style="width:80px; height:10px; background:#f3f4f6; border-radius:4px; margin-top:15px;"></div>
                </div>
                <div class="card-row w-90"></div>
                <div class="card-row w-70"></div>
                <div class="card-row w-40"></div>
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <div style="flex:1; height:30px; background:#4f46e5; border-radius:6px; opacity:0.1;"></div>
                    <div style="flex:1; height:30px; background:#f3f4f6; border-radius:6px;"></div>
                </div>
            </div>
            
            <div class="floating-icon icon-1">📅</div>
            <div class="floating-icon icon-2">👥</div>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stats-grid">
            <div class="stat-item">
                <h2>500+</h2>
                <p>Active Members</p>
            </div>
            <div class="stat-item">
                <h2>50+</h2>
                <p>Events Hosted</p>
            </div>
            <div class="stat-item">
                <h2>100%</h2>
                <p>Secure Platform</p>
            </div>
        </div>
    </div>

    <div class="features-section">
        <div class="section-header">
            <h2>Everything you need.</h2>
            <p style="color:#6b7280;">Powerful features to help your community grow and thrive.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="icon-box">📅</div>
                <h3>Event Management</h3>
                <p style="color:#6b7280; margin-top:10px; line-height: 1.5;">
                    Create, schedule, and manage events effortlessly. Track registrations in real-time.
                </p>
            </div>

            <div class="feature-card">
                <div class="icon-box">🛡️</div>
                <h3>Secure Roles</h3>
                <p style="color:#6b7280; margin-top:10px; line-height: 1.5;">
                    Advanced permission systems for Super Admins, Managers, and Members.
                </p>
            </div>

            <div class="feature-card">
                <div class="icon-box">⚡</div>
                <h3>Instant Updates</h3>
                <p style="color:#6b7280; margin-top:10px; line-height: 1.5;">
                    Notify all members about schedule changes or new announcements instantly.
                </p>
            </div>
        </div>
    </div>

    <div style="background:white; padding: 4rem 1rem; text-align: center;">
        <h2 style="font-size:2rem; margin-bottom:1rem; color:#1f2937;">Ready to get started?</h2>
        <p style="margin-bottom:2rem; color:#6b7280;">Join thousands of others in the CONVERGE network.</p>
        <a href="register.php" class="btn-join" style="padding: 15px 40px; font-size:1.1rem;">Create Free Account</a>
    </div>

    <footer class="footer">
        <div style="font-weight: 800; color: #1f2937; font-size: 1.2rem; margin-bottom: 10px;">CONVERGE.</div>
        <div style="color: #9ca3af; font-size: 0.9rem;">
            &copy; <?= date('Y') ?> Club Management System. All rights reserved.
        </div>
        
        <div class="developer-tag">
            <span>System crafted by</span> 
            <a href="developer.php">Dr. Doom</a>
        </div>
    </footer>

</body>
</html>