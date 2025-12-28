<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cẩm Nang Sức Khỏe - KhamCare</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="shared-styles.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <style>
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', 'Noto Sans Khmer', Arial, sans-serif;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #06b6d4 100%);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(6, 182, 212, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header:hover {
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.12);
        }

        .header .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Logo Styles */
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #06b6d4;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .logo span {
            background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #10b981 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: logoTextShine 3s linear infinite;
        }

        @keyframes logoTextShine {
            0% {
                background-position: 0% center;
            }
            100% {
                background-position: 200% center;
            }
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .logo:hover span {
            animation-duration: 1.5s;
        }

        .logo img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 18px;
            background: transparent;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
        }

        .logo:hover img {
            transform: scale(1.08) rotate(2deg);
            filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
            animation-play-state: paused;
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes logoPulse {
            0%, 100% {
                filter: drop-shadow(0 4px 12px rgba(6, 182, 212, 0.3));
            }
            50% {
                filter: drop-shadow(0 6px 20px rgba(6, 182, 212, 0.5));
            }
        }

        /* Navigation Menu */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: #06b6d4;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 10px 16px;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            white-space: nowrap;
        }

        .nav-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 16px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
        }

        .nav-menu a:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .nav-menu a:hover::before {
            opacity: 1;
            transform: scale(1);
        }

        .nav-menu a:active {
            transform: translateY(0);
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Language Selector Styles */
        .language-selector {
            position: relative;
        }

        .lang-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .lang-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
        }

        .lang-toggle i {
            font-size: 1.1rem;
        }

        .lang-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 280px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid rgba(14, 165, 233, 0.1);
        }

        .lang-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .lang-option {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid #e5e7eb;
        }

        .lang-option:last-child {
            border-bottom: none;
        }

        .lang-option:hover {
            background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .lang-option.active {
            background: linear-gradient(135deg, #22d3ee 0%, #0ea5e9 100%);
            color: white;
        }

        .lang-option.active .lang-country,
        .lang-option.active .lang-name,
        .lang-option.active .lang-code {
            color: white;
        }

        .lang-country {
            font-weight: 700;
            font-size: 1rem;
            color: #374151;
            min-width: 35px;
        }

        .lang-flag {
            display: none;
        }

        .lang-name {
            flex: 1;
            font-weight: 600;
            font-size: 1.1rem;
            color: #374151;
            text-align: center;
        }

        .lang-code {
            font-weight: 700;
            font-size: 1rem;
            color: #0ea5e9;
            min-width: 35px;
            text-align: right;
        }

        .lang-option.active .lang-code {
            color: white;
        }

        .btn-account {
            background: #0ea5e9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .btn-account:hover {
            background: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        /* Account Dropdown Styles */
        .account-dropdown-wrapper {
            position: relative;
        }

        .btn-account-dropdown {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            white-space: nowrap;
        }

        .btn-account-dropdown:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .btn-account-dropdown .arrow {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .account-dropdown-wrapper.active .btn-account-dropdown .arrow {
            transform: rotate(180deg);
        }

        .account-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 250px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid rgba(14, 165, 233, 0.1);
        }

        .account-dropdown-wrapper.active .account-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .account-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
        }

        .account-dropdown-item:last-child {
            border-bottom: none;
        }

        .account-dropdown-item:hover {
            background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0ea5e9;
        }

        .account-dropdown-item i {
            width: 20px;
            font-size: 1.1rem;
            color: #0ea5e9;
        }

        .account-dropdown-item.logout {
            color: #ef4444;
        }

        .account-dropdown-item.logout:hover {
            background: linear-gradient(90deg, #fef2f2 0%, #fee2e2 100%);
        }

        .account-dropdown-item.logout i {
            color: #ef4444;
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px 50px;
            position: relative;
            z-index: 1;
        }

        /* Page Title */
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            padding: 40px 0 20px;
            animation: fadeInDown 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-title h1 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: #ffffff;
            font-size: 3em;
            font-weight: 800;
            margin-bottom: 15px;
            text-transform: uppercase;
            text-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.3),
                0 2px 10px rgba(6, 182, 212, 0.5);
            letter-spacing: 2px;
        }

        .page-title .divider {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px 0;
        }

        .page-title .divider::before,
        .page-title .divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            max-width: 200px;
        }

        .page-title .divider .icon {
            color: #ffffff;
            font-size: 1.8em;
            margin: 0 15px;
            text-shadow: 0 2px 10px rgba(255, 255, 255, 0.5);
        }

        /* Introduction Section */
        .intro-section {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

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

        .intro-section p {
            color: #334155;
            font-size: 1.05em;
            margin-bottom: 15px;
            text-align: justify;
            line-height: 1.8;
        }

        /* Section Title */
        .section-title {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: #ffffff;
            font-size: 2.2em;
            font-weight: 700;
            margin: 50px 0 40px;
            text-transform: uppercase;
            text-shadow: 
                0 4px 15px rgba(0, 0, 0, 0.3),
                0 2px 8px rgba(6, 182, 212, 0.5);
            letter-spacing: 1px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 120px;
            height: 4px;
            background: linear-gradient(90deg, #ffffff, rgba(255, 255, 255, 0.3));
            margin-top: 15px;
            border-radius: 2px;
            box-shadow: 0 2px 15px rgba(255, 255, 255, 0.4);
        }

        /* Guide Cards Grid */
        .guides-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-bottom: 50px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .guide-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(6, 182, 212, 0.1);
            position: relative;
            animation: fadeInUp 0.6s ease-out both;
        }

        .guide-card:nth-child(1) { animation-delay: 0.1s; }
        .guide-card:nth-child(2) { animation-delay: 0.2s; }
        .guide-card:nth-child(3) { animation-delay: 0.3s; }
        .guide-card:nth-child(4) { animation-delay: 0.4s; }
        .guide-card:nth-child(5) { animation-delay: 0.5s; }
        .guide-card:nth-child(6) { animation-delay: 0.6s; }

        .guide-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #06b6d4, #0891b2, #0e7490);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .guide-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(6, 182, 212, 0.25);
        }

        .guide-card:hover::before {
            opacity: 1;
        }

        .guide-card-content {
            display: flex;
            flex-direction: column;
            padding: 0;
            gap: 0;
        }

        .guide-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            border-radius: 20px 20px 0 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(6, 182, 212, 0.15);
            transition: transform 0.3s ease;
        }

        .guide-card:hover .guide-image {
            transform: scale(1.05);
        }

        .guide-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .guide-image .placeholder {
            font-size: 4.5em;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .guide-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 30px;
        }

        .guide-info h3 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: #0e7490;
            font-size: 1.4em;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .guide-info p {
            color: #475569;
            font-size: 0.95em;
            line-height: 1.8;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .guide-info p strong {
            color: #0891b2;
            font-weight: 600;
        }

        .download-btn {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            align-self: flex-start;
            box-shadow: 0 4px 15px rgba(20, 184, 166, 0.3);
            position: relative;
            overflow: hidden;
        }

        .download-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
        }

        .download-btn:hover::before {
            left: 100%;
        }

        .download-btn:active {
            transform: translateY(0);
        }

        /* Specialties Section */
        .specialties-section {
            margin: 60px 0;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .specialties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 30px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .specialty-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 20px;
            padding: 35px 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid rgba(6, 182, 212, 0.1);
            position: relative;
            overflow: hidden;
        }

        .specialty-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.05), rgba(14, 116, 144, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .specialty-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 15px 40px rgba(6, 182, 212, 0.25);
            border-color: rgba(6, 182, 212, 0.3);
        }

        .specialty-card:hover::before {
            opacity: 1;
        }

        .specialty-icon {
            font-size: 4em;
            margin-bottom: 20px;
            display: block;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .specialty-card:hover .specialty-icon {
            transform: scale(1.2) rotate(10deg);
        }

        .specialty-name {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: #0e7490;
            font-size: 1.15em;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .guides-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px 30px;
            }

            .guide-image {
                height: 200px;
            }

            .page-title h1 {
                font-size: 1.8em;
            }

            .section-title {
                font-size: 1.4em;
            }

            .specialties-grid {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }
        }

        /* Beautiful Footer Styles */
        .beautiful-footer {
            background: 
                radial-gradient(ellipse at top left, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
                linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #06b6d4 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
            margin-top: 100px;
            box-shadow: 
                0 -25px 50px rgba(14, 165, 233, 0.2),
                0 -15px 30px rgba(0, 0, 0, 0.3);
        }

        .footer-wave {
            position: absolute;
            top: -1px;
            left: 0;
            width: 100%;
            height: 120px;
            z-index: 2;
        }

        .footer-wave svg {
            width: 100%;
            height: 100%;
        }

        .beautiful-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="footer-medical" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(255, 255, 255, 0.05)"><circle cx="15" cy="15" r="1"/><circle cx="45" cy="45" r="0.8"/><rect x="25" y="20" width="10" height="20" rx="2"/><rect x="20" y="25" width="20" height="10" rx="2"/></g></pattern></defs><rect width="60" height="60" fill="url(%23footer-medical)"/></svg>');
            background-size: 120px 120px;
            opacity: 0.3;
            pointer-events: none;
            z-index: 1;
        }

        .footer-content {
            padding: 80px 0 40px;
            position: relative;
            z-index: 2;
        }

        .beautiful-footer h2,
        .beautiful-footer h3,
        .beautiful-footer h4,
        .beautiful-footer p,
        .beautiful-footer a,
        .beautiful-footer span {
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .beautiful-footer i {
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
        }

        .footer-main-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
            gap: 60px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }

        .footer-brand {
            max-width: 450px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }

        .brand-info h2 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
        }

        .brand-info p {
            font-size: 0.9rem;
            color: #cbd5e1;
            margin: 0;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .brand-desc {
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 30px;
            font-size: 1.05rem;
        }

        .brand-stats {
            display: flex;
            gap: 30px;
            margin-top: 25px;
        }

        .brand-stats .stat {
            text-align: center;
        }

        .brand-stats .number {
            display: block;
            font-size: 1.8rem;
            font-weight: 800;
            color: #0ea5e9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }

        .brand-stats .label {
            font-size: 0.9rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #ffffff;
        }

        .footer-column h3 i {
            color: #0ea5e9;
            margin-right: 10px;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-column li {
            margin-bottom: 15px;
        }

        .footer-column a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-column a i {
            width: 18px;
            color: #0ea5e9;
            font-size: 0.9rem;
            font-style: normal;
        }

        .footer-column a:hover {
            color: #ffffff;
            transform: translateX(8px);
        }

        .footer-column a:hover i {
            color: #06b6d4;
            transform: scale(1.2);
        }

        .contact-info {
            margin-bottom: 30px;
        }

        .contact-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .contact-item i {
            font-size: 1.3rem;
            margin-top: 3px;
            color: #0ea5e9;
        }

        .contact-item strong {
            display: block;
            margin-bottom: 5px;
            color: #ffffff;
        }

        .social-section h4 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social.facebook {
            background: #1877f2;
        }

        .social.youtube {
            background: #ff0000;
        }

        .social.zalo {
            background: #0068ff;
        }

        .social.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }

        .social:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .footer-bottom {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px 0;
            position: relative;
            z-index: 2;
        }

        .bottom-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-bottom p {
            margin: 0;
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        .bottom-links {
            display: flex;
            gap: 30px;
        }

        .bottom-links a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .bottom-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .footer-main-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .brand-stats {
                justify-content: space-around;
            }

            .bottom-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .bottom-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <a href="TrangChu.php" class="logo">
                <img src="image/logo.png.png" alt="Logo">
                <span>KhamCare</span>
            </a>
            
            <nav class="nav-menu">
    <a href="TimKiemBS.php" class="nav-link" id="navSearch" data-translate="navSearch">Tìm bác sĩ</a>
    <a href="ChuyenKhoa.php" class="nav-link" id="navSpecialty" data-translate="navSpecialty">Chuyên khoa</a>
    <a href="TuVanTrucTuyen.php" class="nav-link" id="navConsult" data-translate="navConsult">Tư vấn</a>
    <a href="Camnangsuckhoe.php" class="nav-link active" id="navGuide" data-translate="navGuide">Cẩm nang sức khỏe</a>
    <a href="doctor_auth.php" class="nav-link doctor-link" id="navDoctor">
        <i class="fas fa-user-md"></i> <span id="navDoctorText" data-translate="navDoctor">Dành cho bác sĩ</span>
    </a>
</nav>

<div class="header-actions">
    <div class="account-dropdown-wrapper" id="accountDropdown">
        <button class="btn-account-dropdown" onclick="toggleAccountDropdown(event)">
            <i class="fas fa-user"></i>
            <span id="navAccount" data-translate="navAccount">Tài khoản</span>
            <i class="fas fa-chevron-down arrow"></i>
        </button>

        <div class="account-dropdown-menu">
            <a href="tk.php" class="account-dropdown-item">
                <i class="fas fa-user-circle"></i>
                <span data-translate="myProfile">Hồ sơ của tôi</span>
            </a>

            <a href="DatLichK.php" class="account-dropdown-item">
                <i class="fas fa-calendar-check"></i>
                <span data-translate="myAppointments">Lịch hẹn</span>
            </a>

            <a href="TuVanTrucTuyen.php" class="account-dropdown-item">
                <i class="fas fa-comments"></i>
                <span data-translate="onlineConsult">Tư vấn trực tuyến</span>
            </a>

            <a href="logout.php" class="account-dropdown-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span data-translate="logout">Đăng xuất</span>
            </a>
        </div>
    </div>
</div>
        </div>
    </div>
    <!-- End Header -->

    <!-- Main Container -->
    <div class="main-container">

    <!-- Page Title -->
    <div class="page-title">
        <h1 data-translate="pageTitle">Cẩm Nang Sức Khỏe</h1>
        <div class="divider">
            <span class="icon">+</span>
        </div>
    </div>

    <!-- Introduction Section -->
    <div class="intro-section">
        <p data-translate="intro1">
            <strong>Sức khỏe là tài sản quý giá nhất!</strong>
            Giống như một chiếc xe cần bảo dưỡng định kỳ, cơ thể chúng ta cũng cần được kiểm tra sức khỏe thường xuyên để phát hiện sớm các vấn đề và duy trì sức khỏe tốt nhất.
        </p>

        <p data-translate="intro2">
            Với phương châm <strong>"Phòng bệnh hơn chữa bệnh"</strong>, KhamCare cung cấp các cẩm nang sức khỏe toàn diện theo từng chuyên khoa, giúp bạn:
        </p>

        <ul style="margin-left: 30px; margin-bottom: 15px; color: #334155;">
            <li data-translate="benefit1">✔ Hiểu rõ các bệnh lý thường gặp và cách phòng tránh</li>
            <li data-translate="benefit2">✔ Nhận biết triệu chứng cảnh báo cần đi khám ngay</li>
            <li data-translate="benefit3">✔ Xử lý sơ cứu ban đầu khi gặp vấn đề sức khỏe</li>
            <li data-translate="benefit4">✔ Xây dựng lối sống lành mạnh và chế độ dinh dưỡng phù hợp</li>
        </ul>

        <p data-translate="introCTA">
            <em>Hãy tải ngay các cẩm nang miễn phí bên dưới để bảo vệ sức khỏe bản thân và gia đình!</em>
        </p>
    </div>

    <!-- Specialties Section -->
    <div class="specialties-section">
        <h2 class="section-title" data-translate="specialtyTitle">Chuyên khoa</h2>

        <div class="specialties-grid">

            <div class="specialty-card">
                <span class="specialty-icon">❤️</span>
                <div class="specialty-name" data-translate="cardiology">Tim mạch</div>
            </div>

            <div class="specialty-card">
                <span class="specialty-icon">🩺</span>
                <div class="specialty-name" data-translate="internal">Nội tổng quát</div>
            </div>

            <div class="specialty-card">
                <span class="specialty-icon">👶</span>
                <div class="specialty-name" data-translate="pediatrics">Nhi khoa</div>
            </div>

            <div class="specialty-card">
                <span class="specialty-icon">🧠</span>
                <div class="specialty-name" data-translate="neurology">Thần kinh</div>
            </div>

            <div class="specialty-card">
                <span class="specialty-icon">🤰</span>
                <div class="specialty-name" data-translate="obstetrics">Sản phụ khoa</div>
            </div>

            <div class="specialty-card">
                <span class="specialty-icon">🌿</span>
                <div class="specialty-name" data-translate="dermatology">Da liễu</div>
            </div>

        </div>
    </div>

    <!-- Library Section -->
    <h2 class="section-title" data-translate="libraryTitle">Thư viện cẩm nang sức khỏe miễn phí cho quý khách</h2>

    <div class="guides-grid">

    <!-- Guide 1: Tim mạch -->
    <div class="guide-card">
        <div class="guide-card-content">
            <div class="guide-image">
                <img src="image/timmach.png" alt="Cẩm Nang Tim Mạch">
            </div>
            <div class="guide-info">
                <h3 data-translate="guide1Title">Cẩm Nang Sức Khỏe Tim Mạch</h3>

                <p data-translate="guide1Content">
                    <strong>1. Kiến thức cơ bản:</strong> Tăng huyết áp, thiếu máu cơ tim, nhồi máu cơ tim, rối loạn nhịp tim, suy tim, mỡ máu cao.<br><br>

                    <strong>2. Nguyên nhân & Triệu chứng:</strong> Ăn mặn/béo, stress, ít vận động, hút thuốc. Triệu chứng: đau ngực, khó thở, hồi hộp, phù chân.<br><br>

                    <strong>3. Phòng tránh:</strong> Hạn chế muối/đường/mỡ, đi bộ 30 phút/ngày, không hút thuốc, theo dõi huyết áp định kỳ.<br><br>

                    <strong>4. Xử lý khẩn cấp:</strong> Đau ngực >5 phút → gọi cấp cứu. Huyết áp ≥ 160/100 → đến cơ sở y tế.<br><br>

                    <strong>5. Khi nào gặp bác sĩ:</strong> Đau ngực dữ dội, khó thở đột ngột, huyết áp >180/120.
                </p>

                <button class="download-btn">Tải cẩm nang</button>
            </div>
        </div>
    </div>

    <!-- Guide 2: Nội tổng quát -->
    <div class="guide-card">
        <div class="guide-card-content">
            <div class="guide-image">
                <img src="image/noitongquar.png" alt="Cẩm Nang Nội Tổng Quát">
            </div>

            <div class="guide-info">
                <h3 data-translate="guide2Title">Cẩm Nang Sức Khỏe Nội Tổng Quát</h3>

                <p data-translate="guide2Content">
                    <strong>1. Kiến thức cơ bản:</strong> Tiểu đường, rối loạn mỡ máu, bệnh tuyến giáp, viêm dạ dày, thiếu máu, bệnh gan mật.<br><br>

                    <strong>2. Nguyên nhân & Triệu chứng:</strong> Ăn uống kém, stress, thức khuya. Triệu chứng: mệt mỏi, sụt cân, đau bụng, khát nhiều.<br><br>

                    <strong>3. Phòng tránh:</strong> Ăn nhiều rau xanh, không bỏ bữa sáng, tập thể dục 20–30 phút/ngày, khám định kỳ.<br><br>

                    <strong>4. Xử lý:</strong> Đau dạ dày → uống nước ấm, nghỉ ngơi. Hạ đường huyết → uống nước ngọt, ăn bánh kẹo.<br><br>

                    <strong>5. Khi nào gặp bác sĩ:</strong> Sốt >48h, đau bụng cấp, nôn máu, phân đen, khát bất thường.
                </p>

                <button class="download-btn">Tải cẩm nang</button>
            </div>
        </div>
    </div>

    <!-- Guide 3: Nhi khoa -->
    <div class="guide-card">
    <div class="guide-card-content">
        <div class="guide-image">
            <img src="image/nhikhoa.png" alt="Cẩm Nang Nhi Khoa">
        </div>
        <div class="guide-info">
            <h3 data-translate="guide3Title">Cẩm Nang Sức Khỏe Nhi Khoa</h3>
            <p data-translate="guide3Content">
                <strong>1. Kiến thức cơ bản:</strong> Sốt, cảm cúm, viêm họng, tiêu chảy, dị ứng, viêm phổi.<br><br>
                <strong>2. Nguyên nhân & Triệu chứng:</strong> Virus, vi khuẩn, thay đổi thời tiết. Triệu chứng: sốt, ho, sổ mũi, nôn, phát ban.<br><br>
                <strong>3. Phòng tránh:</strong> Tiêm chủng đầy đủ, giữ vệ sinh tay/đồ chơi, ăn đủ chất, ngủ đủ giấc.<br><br>
                <strong>4. Xử lý:</strong> Sốt → lau mát, hạ sốt khi ≥ 38.5°C. Ho/sổ mũi → xông mũi, nước muối sinh lý. Tiêu chảy → bù nước Oresol.<br><br>
                <strong>5. Khi nào gặp bác sĩ:</strong> Sốt ≥ 39°C kéo dài, co giật, thở nhanh, đi ngoài ra máu.
            </p>
            <button class="download-btn">Tải cẩm nang</button>
        </div>
    </div>
</div>


    <!-- Guide 4: Thần kinh -->
    <div class="guide-card">
    <div class="guide-card-content">
        <div class="guide-image">
            <img src="image/thankinh.png" alt="Cẩm Nang Thần Kinh">
        </div>
        <div class="guide-info">
            <h3 data-translate="guide4Title">Cẩm Nang Sức Khỏe Thần Kinh</h3>
            <p data-translate="guide4Content">
                <strong>1. Kiến thức cơ bản:</strong> Đau đầu, migraine, rối loạn giấc ngủ, đau thần kinh tọa, lo âu, đột quỵ.<br><br>
                <strong>2. Nguyên nhân & Triệu chứng:</strong> Stress, mất ngủ, tư thế sai, tăng huyết áp. Triệu chứng: đau đầu, tê tay chân, chóng mặt.<br><br>
                <strong>3. Phòng tránh:</strong> Giảm stress, thiền 10 phút/ngày, ngồi đúng tư thế, ngủ đủ 7–8 giờ.<br><br>
                <strong>4. Xử lý:</strong> Đau đầu → nghỉ ngơi, massage thái dương. Tê tay chân → thay đổi tư thế. Mất ngủ → tránh điện thoại trước khi ngủ.<br><br>
                <strong>5. Khi nào gặp bác sĩ:</strong> Đau đầu dữ dội, méo miệng, yếu nửa người, tê liệt kéo dài.
            </p>
            <button class="download-btn">Tải cẩm nang</button>
        </div>
    </div>
</div>


    <!-- Guide 5: Sản phụ khoa -->
    <div class="guide-card">
    <div class="guide-card-content">
        <div class="guide-image">
            <img src="image/sangphukhoa.png" alt="Cẩm Nang Sản Phụ Khoa">
        </div>
        <div class="guide-info">
            <h3 data-translate="guide5Title">Cẩm Nang Sức Khỏe Sản Phụ Khoa</h3>
            <p data-translate="guide5Content">
                <strong>1. Kiến thức cơ bản:</strong> Viêm phụ khoa, rối loạn kinh nguyệt, u xơ tử cung, u nang buồng trứng, thai kỳ.<br><br>
                <strong>2. Nguyên nhân & Triệu chứng:</strong> Nhiễm khuẩn, nội tiết, stress. Triệu chứng: khí hư bất thường, ngứa, đau bụng kinh.<br><br>
                <strong>3. Phòng tránh:</strong> Vệ sinh vùng kín đúng cách, mặc đồ thoáng, khám phụ khoa mỗi 6 tháng/lần.<br><br>
                <strong>4. Xử lý:</strong> Khí hư bất thường → giữ sạch, đi khám. Đau bụng kinh → chườm ấm. Thai nghén → ăn đầy đủ bữa, bổ sung vitamin.<br><br>
                <strong>5. Khi nào gặp bác sĩ:</strong> Ra máu bất thường, đau bụng dữ dội, trễ kinh >10 ngày, thai máy yếu.
            </p>
            <button class="download-btn">Tải cẩm nang</button>
        </div>
    </div>
</div>


    <!-- Guide 6: Da liễu -->
    <div class="guide-card">
    <div class="guide-card-content">
        <div class="guide-image">
            <img src="image/dalieu.png" alt="Cẩm Nang Da Liễu">
        </div>
        <div class="guide-info">
            <h3 data-translate="guide6Title">Cẩm Nang Sức Khỏe Da Liễu</h3>
            <p data-translate="guide6Content">
                <strong>1. Kiến thức cơ bản:</strong> Viêm da, dị ứng, mụn trứng cá, nấm da, rụng tóc, chàm cơ địa.<br><br>
                <strong>2. Nguyên nhân & Triệu chứng:</strong> Bụi bẩn, dị ứng mỹ phẩm, nội tiết. Triệu chứng: ngứa, mẩn đỏ, mụn, bong tróc.<br><br>
                <strong>3. Phòng tránh:</strong> Giữ da sạch, tránh gãi, dùng mỹ phẩm phù hợp, uống nhiều nước.<br><br>
                <strong>4. Xử lý:</strong> Dị ứng → rửa bằng nước mát. Mụn → vệ sinh nhẹ, không nặn. Nấm → giữ da khô, không dùng chung khăn.<br><br>
                <strong>5. Khi nào gặp bác sĩ:</strong> Mụn viêm nặng, mẩn đỏ lan rộng, ngứa kéo dài > 1 tuần, phát ban kèm khó thở.
            </p>
            <button class="download-btn">Tải cẩm nang</button>
        </div>
    </div>
</div>

</div>
<!-- End guides-grid -->

</div>
<!-- End main-container -->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Translation data
        const translations = {
    vi: {
        // Navigation
        navSearch: "Tìm bác sĩ",
        navSpecialty: "Chuyên khoa",
        navConsult: "Tư vấn",
        navGuide: "Cẩm nang sức khỏe",
        navDoctor: "Dành cho bác sĩ",
        navAccount: "Tài khoản",
        myProfile: "Hồ sơ của tôi",
        myAppointments: "Lịch hẹn",
        onlineConsult: "Tư vấn trực tuyến",
        logout: "Đăng xuất",
        
        // Page content
        pageTitle: "Cẩm Nang Sức Khỏe",
        intro1: "<strong>Sức khỏe là tài sản quý giá nhất!</strong> Giống như một chiếc xe cần bảo dưỡng định kỳ, cơ thể chúng ta cũng cần được kiểm tra sức khỏe thường xuyên để phát hiện sớm các vấn đề và duy trì sức khỏe tốt nhất.",
        intro2: 'Với phương châm <strong>"Phòng bệnh hơn chữa bệnh"</strong>, KhamCare cung cấp các cẩm nang sức khỏe toàn diện theo từng chuyên khoa, giúp bạn:',
        benefit1: "✓ Hiểu rõ các bệnh lý thường gặp và cách phòng tránh",
        benefit2: "✓ Nhận biết triệu chứng cảnh báo cần đi khám ngay",
        benefit3: "✓ Xử lý sơ cứu ban đầu khi gặp vấn đề sức khỏe",
        benefit4: "✓ Xây dựng lối sống lành mạnh và chế độ dinh dưỡng phù hợp",
        introCTA: "<em>Hãy tải ngay các cẩm nang miễn phí bên dưới để bảo vệ sức khỏe bản thân và gia đình!</em>",
        specialtyTitle: "Chuyên khoa",
        libraryTitle: "Thư viện cẩm nang sức khỏe miễn phí cho quý khách",
        downloadBtn: "Tải cẩm nang",
        cardiology: "Tim mạch",
        internal: "Nội tổng quát",
        pediatrics: "Nhi khoa",
        neurology: "Thần kinh",
        obstetrics: "Sản phụ khoa",
        dermatology: "Da liễu",
        guide1Title: "Cẩm Nang Sức Khỏe Tim Mạch",
        guide1Content: "<strong>1. Kiến thức cơ bản:</strong> Tăng huyết áp, thiếu máu cơ tim, nhồi máu cơ tim, rối loạn nhịp tim, suy tim, mỡ máu cao.<br><br><strong>2. Nguyên nhân & Triệu chứng:</strong> Ăn mặn/béo, stress, ít vận động, hút thuốc. Triệu chứng: đau ngực, khó thở, hồi hộp, phù chân.<br><br><strong>3. Phòng tránh:</strong> Hạn chế muối/đường/mỡ, đi bộ 30 phút/ngày, không hút thuốc, theo dõi huyết áp định kỳ.<br><br><strong>4. Xử lý khẩn cấp:</strong> Đau ngực >5 phút → gọi cấp cứu. Huyết áp ≥160/100 → đến cơ sở y tế.<br><br><strong>5. Khi nào gặp bác sĩ:</strong> Đau ngực dữ dội, khó thở đột ngột, huyết áp >180/120.",
        guide2Title: "Cẩm Nang Sức Khỏe Nội Tổng Quát",
        guide2Content: "<strong>1. Kiến thức cơ bản:</strong> Tiểu đường, rối loạn mỡ máu, bệnh tuyến giáp, viêm dạ dày, thiếu máu, bệnh gan mật.<br><br><strong>2. Nguyên nhân & Triệu chứng:</strong> Ăn uống kém, stress, thức khuya. Triệu chứng: mệt mỏi, sốt cân, đau bụng, khát nhiều.<br><br><strong>3. Phòng tránh:</strong> Ăn nhiều rau xanh, không bỏ bữa sáng, tập 20-30 phút/ngày, khám định kỳ.<br><br><strong>4. Xử lý:</strong> Đau dạ dày → nước ấm, nghỉ ngơi. Hạ đường huyết → nước ngọt, bánh.<br><br><strong>5. Khi nào gặp bác sĩ:</strong> Sốt >48h, đau bụng cấp, nôn máu, phân đen, khát bất thường.",
        guide3Title: "Cẩm Nang Sức Khỏe Nhi Khoa",
        guide3Content: "<strong>1. Kiến thức cơ bản:</strong> Sốt, cảm cúm, viêm họng, tiêu chảy, dị ứng, viêm phổi.<br><br><strong>2. Nguyên nhân & Triệu chứng:</strong> Virus, vi khuẩn, thay đổi thời tiết. Triệu chứng: sốt, ho, sổ mũi, nôn, phát ban.<br><br><strong>3. Phòng tránh:</strong> Tiêm chủng đầy đủ, giữ vệ sinh tay/đồ chơi, ăn đủ chất, ngủ đủ giấc.<br><br><strong>4. Xử lý:</strong> Sốt → lau mát, hạ sốt ≥38.5°C. Ho/sổ mũi → xông mũi, nước muối. Tiêu chảy → Oresol.<br><br><strong>5. Khi nào gặp bác sĩ:</strong> Sốt ≥39°C kéo dài, co giật, thở nhanh, đi ngoài máu.",
        guide4Title: "Cẩm Nang Sức Khỏe Thần Kinh",
        guide4Content: "<strong>1. Kiến thức cơ bản:</strong> Đau đầu, migraine, rối loạn giấc ngủ, đau thần kinh tọa, lo âu, đột quỵ.<br><br><strong>2. Nguyên nhân & Triệu chứng:</strong> Stress, mất ngủ, tư thế sai, tăng huyết áp. Triệu chứng: đau đầu, tê tay chân, chóng mặt.<br><br><strong>3. Phòng tránh:</strong> Giảm stress, thiền 10 phút/ngày, ngồi đúng tư thế, ngủ đủ 7-8h.<br><br><strong>4. Xử lý:</strong> Đau đầu → nước, massage thái dương. Tê tay chân → đổi tư thế. Mất ngủ → tắt điện thoại.<br><br><strong>5. Khi nào gặp bác sĩ:</strong> Đau đầu dữ dội, méo miệng, yếu nửa người, tê liệt kéo dài.",
        guide5Title: "Cẩm Nang Sức Khỏe Sản Phụ Khoa",
        guide5Content: "<strong>1. Kiến thức cơ bản:</strong> Viêm phụ khoa, rối loạn kinh nguyệt, u xơ tử cung, u nang buồng trứng, thai kỳ.<br><br><strong>2. Nguyên nhân & Triệu chứng:</strong> Nhiễm khuẩn, nội tiết, stress. Triệu chứng: khí hư bất thường, ngứa, đau bụng kinh.<br><br><strong>3. Phòng tránh:</strong> Vệ sinh vùng kín đúng cách, mặc đồ thoáng, khám phụ khoa mỗi 6 tháng/lần.<br><br><strong>4. Xử lý:</strong> Khí hư bất thường → giữ sạch, đi khám. Đau bụng kinh → chườm ấm. Thai nghén → ăn đầy đủ bữa, bổ sung vitamin.<br><br><strong>5. Khi nào gặp bác sĩ:</strong> Ra máu bất thường, đau bụng dữ dội, trễ kinh >10 ngày, thai máy yếu.",
        guide6Title: "Cẩm Nang Sức Khỏe Da Liễu",
        guide6Content: "<strong>1. Kiến thức cơ bản:</strong> Viêm da, dị ứng, mụn trứng cá, nấm da, rụng tóc, chàm cơ địa.<br><br><strong>2. Nguyên nhân & Triệu chứng:</strong> Bụi bẩn, dị ứng mỹ phẩm, nội tiết. Triệu chứng: ngứa, mẩn đỏ, mụn, bong tróc.<br><br><strong>3. Phòng tránh:</strong> Giữ da sạch, tránh gãi, dùng mỹ phẩm phù hợp, uống nhiều nước.<br><br><strong>4. Xử lý:</strong> Dị ứng → rửa bằng nước mát. Mụn → vệ sinh nhẹ, không nặn. Nấm → giữ da khô, không dùng chung khăn.<br><br><strong>5. Khi nào gặp bác sĩ:</strong> Mụn viêm nặng, mẩn đỏ lan rộng, ngứa kéo dài > 1 tuần, phát ban kèm khó thở.",
        // Footer
        footerDesc: "Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. Kết nối bạn với các bác sĩ chuyên khoa uy tín.",
        footerPatients: "Bệnh nhân",
        footerDoctors: "Bác sĩ",
        footerSpecialties: "Chuyên khoa",
        footerForPatient: "Dành Cho Bệnh Nhân",
        footerSearchDoctor: "Tìm kiếm bác sĩ",
        footerLogin: "Đăng nhập",
        footerRegister: "Đăng ký",
        footerBooking: "Đặt lịch",
        footerAppointmentControl: "Bảng kiểm soát lịch hẹn",
        footerForDoctor: "Dành Cho Bác Sĩ",
        footerCheckAppointment: "Kiểm tra cuộc hẹn",
        footerChat: "Chat",
        footerDashboard: "Dashboard của bác sĩ",
        footerContact: "Liên Hệ",
        footerAddress: "Địa chỉ:",
        footerAddressDetail: "Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
        footerConnectUs: "Kết nối với chúng tôi",
        footerRights: "Tất cả quyền được bảo lưu.",
        footerPrivacy: "Chính sách bảo mật",
        footerTerms: "Điều khoản sử dụng",
        footerSupport: "Hỗ trợ khách hàng"
    },
    km: {
        // Navigation
        navSearch: "ស្វែងរកវេជ្ជបណ្ឌិត",
        navSpecialty: "ឯកទេស",
        navConsult: "ពិគ្រោះយោបល់",
        navGuide: "សៀវភៅណែនាំសុខភាព",
        navDoctor: "សម្រាប់វេជ្ជបណ្ឌិត",
        navAccount: "គណនី",
        myProfile: "ប្រវត្តិរូបរបស់ខ្ញុំ",
        myAppointments: "ការណាត់ជួប",
        onlineConsult: "ពិគ្រោះយោបល់អនឡាញ",
        logout: "ចាកចេញ",
        
        // Page content
        pageTitle: "សៀវភៅណែនាំសុខភាព",
        intro1: "<strong>សុខភាពគឺជាទ្រព្យសម្បត្តិដ៏មានតម្លៃបំផុត!</strong> ដូចជារថយន្តមួយដែលត្រូវការថែទាំជាប្រចាំ រាងកាយរបស់យើងក៏ត្រូវការពិនិត្យសុខភាពជាប្រចាំដើម្បីរកឃើញបញ្ហាទាន់ពេល",
        intro2: 'ជាមួយនឹងគោលការណ៍ <strong>"ការពារជំងឺប្រសើរជាងការព្យាបាល"</strong>, KhamCare ផ្តល់សៀវភៅណែនាំសុខភាពគ្រប់ជ្រុងជ្រោយតាមឯកទេស ជួយអ្នក:',
        benefit1: "✓ យល់ដឹងអំពីជំងឺទូទៅ និងវិធីការពារ",
        benefit2: "✓ ស្គាល់សញ្ញាព្រមានដែលត្រូវពិនិត្យភ្លាម",
        benefit3: "✓ ដោះស្រាយបឋមនៅពេលមានបញ្ហា",
        benefit4: "✓ បង្កើតរបៀបរស់នៅ និងរបបអាហារសមរម្យ",
        introCTA: "<em>សូមទាញយកសៀវភៅណែនាំឥតគិតថ្លៃខាងក្រោមដើម្បីការពារសុខភាពខ្លួនឯង និងគ្រួសារ!</em>",
        specialtyTitle: "ឯកទេស",
        libraryTitle: "បណ្ណាល័យសៀវភៅណែនាំសុខភាពឥតគិតថ្លៃសម្រាប់អតិថិជន",
        downloadBtn: "ទាញយក",
        cardiology: "ជំងឺបេះដូង",
        internal: "ផ្ទៃក្នុងទូទៅ",
        pediatrics: "កុមារ",
        neurology: "សរសៃប្រសាទ",
        obstetrics: "សម្ភព និងស្ត្រី",
        dermatology: "ស្បែក",
        guide1Title: "សៀវភៅណែនាំសុខភាពបេះដូង",
        guide1Content: "<strong>១. ចំណេះដឹងមូលដ្ឋាន:</strong> ជំងឺលើសឈាម, ខ្វះឈាមបេះដូង, ខ្វះឈាមស្រួចបេះដូង, ចង្វាក់បេះដូងមិនប្រក្រតី, បេះដូងខ្សោយ, ខ្លាញ់ឈាមខ្ពស់<br><br><strong>២. មូលហេតុ & រោគសញ្ញា:</strong> ញ៉ាំប្រៃ/ខ្លាញ់, ស្ត្រេស, ហាត់ប្រាណតិច, ជក់បារី រោគសញ្ញា: ឈឺទ្រូង, ដង្ហើមខ្លី, បេះដូងញ័រ, ជើងហើម<br><br><strong>៣. ការពារ:</strong> កាត់បន្ថយអំបិល/ស្ករ/ខ្លាញ់, ដើរ៣០នាទី/ថ្ងៃ, មិនជក់បារី, តាមដានសម្ពាធឈាមជាប្រចាំ<br><br><strong>៤. ដោះស្រាយបន្ទាន់:</strong> ឈឺទ្រូង>៥នាទី → ហៅសង្គ្រោះបន្ទាន់ សម្ពាធឈាម≥១៦០/១០០ → ទៅមន្ទីរពេទ្យ<br><br><strong>៥. ពេលណាត្រូវជួបវេជ្ជបណ្ឌិត:</strong> ឈឺទ្រូងខ្លាំង, ដង្ហើមខ្លីភ្លាមៗ, សម្ពាធឈាម>១៨០/១២០",
        guide2Title: "សៀវភៅណែនាំសុខភាពផ្ទៃក្នុងទូទៅ",
        guide2Content: "<strong>១. ចំណេះដឹងមូលដ្ឋាន:</strong> ជំងឺទឹកនោមផ្អែម, ខ្លាញ់ឈាមមិនប្រក្រតី, ជំងឺក្រពេញទីរ៉ូអ៊ីត, រលាកក្រពះ, ខ្វះឈាម, ជំងឺថ្លើម<br><br><strong>២. មូលហេតុ & រោគសញ្ញា:</strong> ញ៉ាំមិនល្អ, ស្ត្រេស, ដេកយឺត រោគសញ្ញា: អស់កម្លាំង, ស្គមខ្លួន, ឈឺពោះ, ស្រេកទឹកច្រើន<br><br><strong>៣. ការពារ:</strong> ញ៉ាំបន្លែច្រើន, មិនរំលងអាហារពេលព្រឹក, ហាត់ប្រាណ២០-៣០នាទី/ថ្ងៃ, ពិនិត្យជាប្រចាំ<br><br><strong>៤. ដោះស្រាយ:</strong> ឈឺក្រពះ → ទឹកក្តៅ, សម្រាក ស្ករឈាមធ្លាក់ → ទឹកផ្អែម, នំ<br><br><strong>៥. ពេលណាត្រូវជួបវេជ្ជបណ្ឌិត:</strong> ក្តៅខ្លួន>៤៨ម៉ោង, ឈឺពោះខ្លាំង, ក្អួតឈាម, លាមកខ្មៅ, ស្រេកទឹកខុសប្រក្រតី",
        guide3Title: "សៀវភៅណែនាំសុខភាពកុមារ",
        guide3Content: "<strong>១. ចំណេះដឹងមូលដ្ឋាន:</strong> ក្តៅខ្លួន, ផ្តាសាយ, រលាកបំពង់ក, រាគរូស, អាឡែស៊ី, រលាកសួត<br><br><strong>២. មូលហេតុ & រោគសញ្ញា:</strong> មេរោគ, បាក់តេរី, អាកាសធាតុប្រែប្រួល រោគសញ្ញា: ក្តៅខ្លួន, ក្អក, ស្ទះច្រមុះ, ក្អួត, កន្ទួល<br><br><strong>៣. ការពារ:</strong> ចាក់វ៉ាក់សាំងគ្រប់គ្រាន់, រក្សាអនាម័យដៃ/ប្រដាប់ក្មេងលេង, ញ៉ាំគ្រប់គ្រាន់, គេងគ្រប់គ្រាន់<br><br><strong>៤. ដោះស្រាយ:</strong> ក្តៅខ្លួន → ជូតត្រជាក់, បន្ថយក្តៅ≥៣៨.៥°C ក្អក/ស្ទះច្រមុះ → ចំហាយទឹក, ទឹកអំបិល រាគរូស → Oresol<br><br><strong>៥. ពេលណាត្រូវជួបវេជ្ជបណ្ឌិត:</strong> ក្តៅខ្លួន≥៣៩°C យូរ, ប្រកាច់, ដង្ហើមលឿន, លាមកមានឈាម",
        guide4Title: "សៀវភៅណែនាំសុខភាពសរសៃប្រសាទ",
        guide4Content: "<strong>១. ចំណេះដឹងមូលដ្ឋាន:</strong> ឈឺក្បាល, ឈឺក្បាលពាក់កណ្តាល, គេងមិនលក់, ឈឺសរសៃប្រសាទតាមខ្នង, ថប់បារម្ភ, ដាច់សរសៃឈាមខួរក្បាល<br><br><strong>២. មូលហេតុ & រោគសញ្ញា:</strong> ស្ត្រេស, គេងមិនលក់, ឥរិយាបថមិនត្រឹមត្រូវ, សម្ពាធឈាមខ្ពស់ រោគសញ្ញា: ឈឺក្បាល, ស្ពឹកដៃជើង, វិលមុខ<br><br><strong>៣. ការពារ:</strong> កាត់បន្ថយស្ត្រេស, សមាធិ១០នាទី/ថ្ងៃ, អង្គុយត្រឹមត្រូវ, គេង៧-៨ម៉ោង<br><br><strong>៤. ដោះស្រាយ:</strong> ឈឺក្បាល → ទឹក, ម៉ាស្សាចំហៀង ស្ពឹកដៃជើង → ប្តូរឥរិយាបថ គេងមិនលក់ → បិទទូរស័ព្ទ<br><br><strong>៥. ពេលណាត្រូវជួបវេជ្ជបណ្ឌិត:</strong> ឈឺក្បាលខ្លាំង, មាត់ឆៀង, ខ្សោយពាក់កណ្តាលខ្លួន, ស្ពឹកយូរ",
        guide5Title: "សៀវភៅណែនាំសុខភាពសម្ភពនិងស្ត្រី",
        guide5Content: "<strong>១. ចំណេះដឹងមូលដ្ឋាន:</strong> រលាកស្រទាប់ស្ត្រី, ខូចចក្រ, ដុំសាច់ស្បូន, ដុំទឹកអូវែរ, ការមានផ្ទៃពោះ<br><br><strong>២. មូលហេតុ & រោគសញ្ញា:</strong> ឆ្លងមេរោគ, ហរម៉ូន, ស្ត្រេស រោគសញ្ញា: ការបញ្ចេញមិនប្រក្រតី, រមាស់, ឈឺពោះពេលមករដូវ<br><br><strong>៣. ការពារ:</strong> រក្សាអនាម័យត្រឹមត្រូវ, ស្លៀកពាក់ស្រាល, ពិនិត្យ៦ខែ/ដង<br><br><strong>៤. ដោះស្រាយ:</strong> ការបញ្ចេញមិនប្រក្រតី → រក្សាស្អាត, ទៅពិនិត្យ ឈឺពោះពេលមករដូវ → ថប់ក្តៅ ផ្ទៃពោះ → ញ៉ាំគ្រប់គ្រាន់, បន្ថែមវីតាមីន<br><br><strong>៥. ពេលណាត្រូវជួបវេជ្ជបណ្ឌិត:</strong> ឈាមចេញមិនប្រក្រតី, ឈឺពោះខ្លាំង, យឺតមករដូវ>១០ថ្ងៃ, ទារកខ្សោយ",
        guide6Title: "សៀវភៅណែនាំសុខភាពស្បែក",
        guide6Content: "<strong>១. ចំណេះដឹងមូលដ្ឋាន:</strong> រលាកស្បែក, អាឡែស៊ី, មុន, ផ្សិតស្បែក, ជ្រុះសក់, រលាកស្បែកអាតូពិក<br><br><strong>២. មូលហេតុ & រោគសញ្ញា:</strong> ធូលី, អាឡែស៊ីគ្រឿងសំអាង, ហរម៉ូន រោគសញ្ញា: រមាស់, កន្ទួលក្រហម, មុន, សំបកស្បែកជ្រុះ<br><br><strong>៣. ការពារ:</strong> រក្សាស្បែកស្អាត, ជៀសវាងកោស, ប្រើគ្រឿងសំអាងសមរម្យ, ផឹកទឹកច្រើន<br><br><strong>៤. ដោះស្រាយ:</strong> អាឡែស៊ី → លាងទឹកត្រជាក់ មុន → លាងស្រាល, កុំច្របាច់ ផ្សិត → រក្សាស្ងួត, កុំប្រើរួមកន្សែង<br><br><strong>៥. ពេលណាត្រូវជួបវេជ្ជបណ្ឌិត:</strong> មុនរលាកធ្ងន់org កន្ទួលរាលដាល, រមាស់>១សប្តាហ៍org កន្ទួលមានដង្ហើមខ្លី",
        // Footer
        footerDesc: "ប្រព័ន្ធកក់ការពិនិត្យជំងឺអនឡាញឈានមុខគេនៅវៀតណាម។ ភ្ជាប់អ្នកជាមួយវេជ្ជបណ្ឌិតឯកទេសល្បីល្បាញ។",
        footerPatients: "អ្នកជំងឺ",
        footerDoctors: "វេជ្ជបណ្ឌិត",
        footerSpecialties: "ឯកទេស",
        footerForPatient: "សម្រាប់អ្នកជំងឺ",
        footerSearchDoctor: "ស្វែងរកវេជ្ជបណ្ឌិត",
        footerLogin: "ចូល",
        footerRegister: "ចុះឈ្មោះ",
        footerBooking: "កក់ការណាត់ជួប",
        footerAppointmentControl: "គ្រប់គ្រងការណាត់ជួប",
        footerForDoctor: "សម្រាប់វេជ្ជបណ្ឌិត",
        footerCheckAppointment: "ពិនិត្យការណាត់ជួប",
        footerChat: "ជជែក",
        footerDashboard: "ផ្ទាំងគ្រប់គ្រងវេជ្ជបណ្ឌិត",
        footerContact: "ទំនាក់ទំនង",
        footerAddress: "អាសយដ្ឋាន:",
        footerAddressDetail: "មន្ទីរពេទ្យអន្តរជាតិ<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
        footerConnectUs: "ភ្ជាប់ជាមួយយើង",
        footerRights: "រក្សាសិទ្ធិគ្រប់យ៉ាង។",
        footerPrivacy: "គោលការណ៍ឯកជនភាព",
        footerTerms: "លក្ខខណ្ឌប្រើប្រាស់",
        footerSupport: "ជំនួយអតិថិជន"
    }
};


        // Get current language from localStorage or default to 'vi'
        let currentLang = localStorage.getItem('language') || 'vi';

        // Function to translate page
        function translatePage(lang) {
            currentLang = lang;
            localStorage.setItem('language', lang);
            
            const t = translations[lang];
            
            // Translate all elements with data-translate attribute
            document.querySelectorAll('[data-translate]').forEach(el => {
                const key = el.getAttribute('data-translate');
                if (t[key]) {
                    el.innerHTML = t[key];
                }
            });
            
            // Update all download buttons
            document.querySelectorAll('.download-btn').forEach(btn => {
                btn.textContent = t.downloadBtn;
            });
        }

        // Toggle dropdown
        const langToggle = document.getElementById('langToggle');
        const langDropdown = document.getElementById('langDropdown');
        
        console.log('langToggle:', langToggle);
        console.log('langDropdown:', langDropdown);
        
        if (langToggle && langDropdown) {
            langToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                langToggle.classList.toggle('active');
                langDropdown.classList.toggle('show');
            });
        } else {
            console.error('Language selector elements not found!');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            langToggle.classList.remove('active');
            langDropdown.classList.remove('show');
        });

        // Initialize language options
        document.querySelectorAll('.lang-option').forEach(option => {
            option.addEventListener('click', (e) => {
                e.stopPropagation();
                const lang = option.dataset.lang;
                
                // Update active state
                document.querySelectorAll('.lang-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                option.classList.add('active');
                
                // Update toggle button text
                const langCode = option.querySelector('.lang-code').textContent;
                document.getElementById('currentLang').textContent = langCode;
                
                // Translate page
                translatePage(lang);
                
                // Translate footer
                if (typeof applyFooterTranslation === 'function') {
                    applyFooterTranslation(lang);
                }
                
                // Close dropdown
                langToggle.classList.remove('active');
                langDropdown.classList.remove('show');
            });
        });

        // Apply saved language on page load
        translatePage(currentLang);
        
        // Apply footer translation on page load
        if (typeof applyFooterTranslation === 'function') {
            applyFooterTranslation(currentLang);
        }
        
        // Update toggle button with saved language
        const savedOption = document.querySelector(`.lang-option[data-lang="${currentLang}"]`);
        if (savedOption) {
            document.querySelectorAll('.lang-option').forEach(opt => opt.classList.remove('active'));
            savedOption.classList.add('active');
            document.getElementById('currentLang').textContent = savedOption.querySelector('.lang-code').textContent;
        }
    }); // End DOMContentLoaded
    </script>

    <script>
        // Account dropdown toggle
        function toggleAccountDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('accountDropdown');
            dropdown.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('accountDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>

    <!-- Beautiful Medical Footer -->
    <footer class="beautiful-footer">
        <div class="footer-wave">
            <svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,60 C300,120 900,0 1200,60 L1200,120 L0,120 Z" fill="url(#footerGradient)"/>
                <defs>
                    <linearGradient id="footerGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#0c4a6e;stop-opacity:1" />
                        <stop offset="30%" style="stop-color:#0369a1;stop-opacity:1" />
                        <stop offset="70%" style="stop-color:#0ea5e9;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:1" />
                    </linearGradient>
                </defs>
            </svg>
        </div>
        
        <div class="footer-content">
            <div class="container">
                <div class="footer-main-grid">
                    <!-- Brand Column -->
                    <div class="footer-brand">
                        <div class="brand-header">
                            <div class="brand-icon">
                                <img src="image/logo.png.png" alt="KhamCare Logo" style="width: 60px; height: 60px; border-radius: 12px;">
                            </div>
                            <div class="brand-info">
                                <h2>KhamCare</h2>
                                <p>INTERNATIONAL HOSPITAL</p>
                            </div>
                        </div>
                        <p class="brand-desc" data-translate="footerDesc">
                            Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. 
                            Kết nối bạn với các bác sĩ chuyên khoa uy tín.
                        </p>
                        <div class="brand-stats">
                            <div class="stat">
                                <span class="number">50K+</span>
                                <span class="label" data-translate="footerPatients">Bệnh nhân</span>
                            </div>
                            <div class="stat">
                                <span class="number">200+</span>
                                <span class="label" data-translate="footerDoctors">Bác sĩ</span>
                            </div>
                            <div class="stat">
                                <span class="number">15+</span>
                                <span class="label" data-translate="footerSpecialties">Chuyên khoa</span>
                            </div>
                        </div>
                    </div>

                <!-- Patient Links -->
                <div class="footer-column">
                    <h3><i class="fas fa-user-injured"></i> <span data-translate="footerForPatient">Dành Cho Bệnh Nhân</span></h3>
                    <ul>
                        <li><a href="TimKiemBS.php"><i class="fas fa-search"></i> <span data-translate="footerSearchDoctor">Tìm kiếm bác sĩ</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-sign-in-alt"></i> <span data-translate="footerLogin">Đăng nhập</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> <span data-translate="footerRegister">Đăng ký</span></a></li>
                        <li><a href="DatLichK.php"><i class="fas fa-calendar-plus"></i> <span data-translate="footerBooking">Đặt lịch</span></a></li>
                        <li><a href="#"><i class="fas fa-history"></i> <span data-translate="footerAppointmentControl">Bảng kiểm soát lịch hẹn</span></a></li>
                    </ul>
                </div>

                <!-- Doctor Links -->
                <div class="footer-column">
                    <h3><i class="fas fa-user-md"></i> <span data-translate="footerForDoctor">Dành Cho Bác Sĩ</span></h3>
                    <ul>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span data-translate="footerCheckAppointment">Kiểm tra cuộc hẹn</span></a></li>
                        <li><a href="TuVanTrucTuyen.php"><i class="fas fa-comments"></i> <span data-translate="footerChat">Chat</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-md"></i> <span data-translate="footerLogin">Đăng nhập</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> <span data-translate="footerRegister">Đăng ký</span></a></li>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-chart-line"></i> <span data-translate="footerDashboard">Dashboard của bác sĩ</span></a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="footer-column contact-column">
                    <h3><i class="fas fa-phone-alt"></i> <span data-translate="footerContact">Liên Hệ</span></h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong data-translate="footerAddress">Địa chỉ:</strong><br>
                                <span data-translate="footerAddressDetail">Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM</span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Hotline:</strong><br>
                                0433636050
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email:</strong><br>
                                benhviendakhoaquocte@gmail.com
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="social-section">
                        <h4 data-translate="footerConnectUs">Kết nối với chúng tôi</h4>
                        <div class="social-links">
                            <a href="#" class="social facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social youtube"><i class="fab fa-youtube"></i></a>
                            <a href="#" class="social zalo"><i class="fas fa-comment-dots"></i></a>
                            <a href="#" class="social instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="bottom-content">
                <p>&copy; 2024 <strong>KhamCare International Hospital</strong>. <span data-translate="footerRights">Tất cả quyền được bảo lưu.</span></p>
                <div class="bottom-links">
                    <a href="#" data-translate="footerPrivacy">Chính sách bảo mật</a>
                    <a href="#" data-translate="footerTerms">Điều khoản sử dụng</a>
                    <a href="#" data-translate="footerSupport">Hỗ trợ khách hàng</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
// Footer translation for Camnangsuckhoe (duplicate - already defined above)
// This script block should be removed or merged with the one above
</script>

</body>
</html>
