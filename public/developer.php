<?php
// public/developer.php
require_once __DIR__ . '/../includes/auth.php'; 
// No login required for this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Developer Profile - Dr. Doom</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #1f2937;
            padding: 20px;
        }

        /* Card Container */
        .profile-card {
            background: white;
            width: 100%;
            max-width: 480px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px -5px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            border: 1px solid rgba(255,255,255,0.5);
        }

        /* Top Banner */
        .banner {
            height: 160px;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            position: relative;
        }
        
        /* Decorative Pattern on Banner */
        .banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(#ffffff 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.1;
        }

        /* Profile Image */
        .avatar-container {
            position: relative;
            margin-top: -80px; /* Pulls image up into banner */
            display: inline-block;
        }

        .avatar {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 6px solid white;
            object-fit: cover;
            object-position: top; /* Centers Dr. Doom's face */
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            background: #1f2937;
        }

        /* Status Dot */
        .status-dot {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 20px;
            height: 20px;
            background: #10b981; /* Green for Online */
            border: 3px solid white;
            border-radius: 50%;
        }

        /* Content */
        .content {
            padding: 20px 30px 40px;
        }

        .name {
            font-size: 1.8rem;
            font-weight: 800;
            color: #111827;
            margin: 10px 0 5px;
        }

        .role {
            color: #4f46e5;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .bio {
            color: #6b7280;
            line-height: 1.6;
            font-size: 1rem;
            margin-bottom: 25px;
        }

        /* Tech Stack Badges */
        .skills {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .skill-badge {
            background: #f3f4f6;
            color: #374151;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #e5e7eb;
        }

        /* Contact Box */
        .contact-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #1f2937;
            color: white;
            text-decoration: none;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.2s, background 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .contact-btn:hover {
            transform: translateY(-2px);
            background: #111827;
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }

        .back-link {
            display: block;
            margin-top: 25px;
            color: #9ca3af;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-link:hover { color: #4b5563; }

    </style>
</head>
<body class="animate-fade-up">

    <div class="profile-card">
        <div class="banner"></div>

        <div class="avatar-container">
            <img src="https://images.squarespace-cdn.com/content/v1/662d6dbc571bdb21fdbc79b5/74f0978e-7e70-4fe4-a8dc-c05112b758ca/16735402991293.jpg" 
                 alt="Dr. Doom" 
                 class="avatar">
            <div class="status-dot" title="System Online"></div>
        </div>

        <div class="content">
            <h1 class="name">Dr. Doom</h1>
            <div class="role">System Architect & Lead Developer</div>

            <p class="bio">
                Mastermind behind the CONVERGE system. <br>
                Specializing in full-stack architecture, secure database management, and building scalable community platforms.
            </p>

            <div class="skills">
                <span class="skill-badge">PHP 8</span>
                <span class="skill-badge">MySQL</span>
                <span class="skill-badge">Security</span>
                <span class="skill-badge">UI/UX</span>
            </div>

            <a href="mailto:doom47@gmail.com" class="contact-btn">
                <span>📧</span> Contact Developer
            </a>

            <a href="index.php" class="back-link">&larr; Back to Home</a>
        </div>
    </div>

</body>
</html>