<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zurimart Bakery | Zuri Bread</title>
    <meta name="description" content="Zurimart Bakery presents Zuri Bread, a bromate-free 500g loaf made for fresh family moments every day.">
    <style>
        :root {
            --cream: #f4efe4;
            --paper: #fffdf8;
            --ink: #081526;
            --muted: #6f7682;
            --red: #f4052c;
            --orange: #ff8b1f;
            --gold: #d2a22a;
            --dark: #111111;
            --panel-shadow: 0 20px 50px rgba(18, 18, 18, 0.12);
            --radius-lg: 32px;
            --max: 1240px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(255, 139, 31, 0.12), transparent 18%),
                linear-gradient(180deg, #f9f4ea 0%, #f4efe4 100%);
        }

        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }

        .container {
            width: min(calc(100% - 32px), var(--max));
            margin: 0 auto;
        }

        .topbar {
            background: var(--red);
            color: #fff;
            font-size: 0.98rem;
        }

        .topbar-inner,
        .nav-inner,
        .footer-band,
        .footer-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .topbar-inner {
            padding: 16px 0;
            flex-wrap: wrap;
        }

        .topbar-list,
        .socials,
        .nav-links,
        .stats,
        .hero-actions,
        .feature-grid,
        .products,
        .offer-grid,
        .gallery-grid,
        .footer-grid {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .nav-shell {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(17, 17, 17, 0.96);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
        }

        .nav-inner {
            min-height: 96px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #fff;
            min-width: 0;
        }

        .brand img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.16);
            background: #fff;
        }

        .brand-copy strong {
            display: block;
            font-size: 1.4rem;
            letter-spacing: 0.02em;
        }

        .brand-copy span {
            display: block;
            color: #e0be66;
            font-size: 0.84rem;
            letter-spacing: 0.26em;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .nav-links a {
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
        }

        .nav-links a:hover,
        .socials a:hover {
            color: #ffd07a;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 26px;
            font-weight: 800;
            font-size: 1rem;
            text-transform: uppercase;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn:hover { transform: translateY(-2px); }

        .btn-primary {
            background: var(--red);
            color: #fff;
            box-shadow: 0 14px 30px rgba(244, 5, 44, 0.22);
        }

        .btn-secondary {
            background: var(--orange);
            color: #fff;
            box-shadow: 0 14px 30px rgba(255, 139, 31, 0.22);
        }

        .btn-ghost {
            border: 1px solid rgba(255, 255, 255, 0.24);
            color: #fff;
        }

        .hero {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(11, 11, 11, 0.92) 0%, rgba(11, 11, 11, 0.88) 43%, rgba(82, 35, 6, 0.68) 100%),
                radial-gradient(circle at 82% 28%, rgba(255, 142, 40, 0.45), transparent 24%),
                #1a120d;
            min-height: 780px;
            display: flex;
            align-items: center;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.03)),
                repeating-linear-gradient(0deg, transparent 0 34px, rgba(255, 255, 255, 0.02) 34px 36px),
                repeating-linear-gradient(90deg, transparent 0 72px, rgba(255, 255, 255, 0.02) 72px 74px);
            opacity: 0.28;
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            gap: 40px;
            align-items: center;
            padding: 72px 0 64px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--orange);
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: 0.04em;
            font-size: 1.15rem;
            margin-bottom: 18px;
        }

        .hero h1 {
            margin: 0;
            color: #fff;
            font-size: clamp(3rem, 8vw, 6.8rem);
            line-height: 0.98;
            text-transform: uppercase;
            max-width: 760px;
        }

        .hero p {
            color: rgba(255, 255, 255, 0.78);
            font-size: 1.08rem;
            line-height: 1.8;
            max-width: 620px;
            margin: 22px 0 0;
        }

        .hero-actions { margin-top: 34px; }

        .hero-card {
            justify-self: end;
            width: min(100%, 510px);
            position: relative;
        }

        .hero-card::before {
            content: "";
            position: absolute;
            inset: auto 18px -18px 18px;
            height: 24px;
            background: rgba(0, 0, 0, 0.35);
            filter: blur(18px);
            border-radius: 50%;
        }

        .hero-pack {
            position: relative;
            padding: 26px 24px 0;
            border-radius: var(--radius-lg);
            background: linear-gradient(180deg, rgba(255, 201, 88, 0.98), rgba(255, 230, 176, 0.92));
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.3);
        }

        .hero-pack img {
            width: 100%;
            max-height: 610px;
            object-fit: contain;
            animation: floaty 4.8s ease-in-out infinite;
        }

        .offer-badge {
            position: absolute;
            top: 36px;
            right: 12px;
            background: #fff5ea;
            color: var(--orange);
            font-size: 2rem;
            font-weight: 900;
            padding: 20px 26px;
            border-radius: 999px 999px 999px 40px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.16);
        }

        .section { padding: 110px 0; }
        .section-compact { padding: 90px 0; }

        .section-title {
            text-align: center;
            max-width: 820px;
            margin: 0 auto 54px;
        }

        .section-title h2 {
            margin: 16px 0 0;
            font-size: clamp(2.4rem, 5vw, 4.3rem);
            line-height: 1.08;
        }

        .section-title p {
            margin: 20px auto 0;
            color: var(--muted);
            line-height: 1.8;
            font-size: 1.05rem;
            max-width: 760px;
        }

        .products { justify-content: center; }

        .product-card {
            width: min(100%, 272px);
            background: var(--paper);
            border-radius: 30px;
            box-shadow: var(--panel-shadow);
            padding: 0 22px 28px;
            text-align: center;
        }

        .product-visual {
            width: 184px;
            height: 184px;
            margin: -46px auto 22px;
            border-radius: 50%;
            background: #fff;
            border: 3px dashed rgba(244, 5, 44, 0.5);
            padding: 16px;
            display: grid;
            place-items: center;
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.1);
        }

        .product-visual img {
            max-height: 148px;
            object-fit: contain;
        }

        .product-card h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .product-card p {
            margin: 14px 0 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .price {
            display: block;
            margin-top: 18px;
            color: var(--red);
            font-size: 1.7rem;
            font-weight: 900;
        }

        .offer-grid { justify-content: center; }

        .offer-card {
            position: relative;
            overflow: hidden;
            width: min(100%, 380px);
            min-height: 430px;
            background:
                linear-gradient(90deg, rgba(13, 13, 13, 0.95) 0%, rgba(13, 13, 13, 0.84) 45%, rgba(13, 13, 13, 0.4) 100%),
                #171717;
            color: #fff;
            padding: 34px 34px 30px;
            box-shadow: var(--panel-shadow);
        }

        .offer-card::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 60px;
            background: linear-gradient(180deg, transparent, rgba(255, 144, 40, 0.18));
        }

        .offer-card img {
            position: absolute;
            right: 14px;
            bottom: 0;
            width: 54%;
            max-height: 88%;
            object-fit: contain;
        }

        .offer-card .badge-mini {
            position: absolute;
            right: 26px;
            top: 28px;
            color: #ffcf29;
            border: 2px solid rgba(255, 207, 41, 0.75);
            border-radius: 999px;
            font-weight: 900;
            padding: 12px 16px;
            line-height: 1;
        }

        .offer-copy {
            position: relative;
            z-index: 1;
            max-width: 210px;
        }

        .offer-copy h3 {
            margin: 18px 0 14px;
            font-size: 2.1rem;
            line-height: 1.02;
            text-transform: uppercase;
        }

        .offer-copy p {
            margin: 0 0 28px;
            color: #ff9a31;
            font-size: 1.02rem;
            font-weight: 700;
        }

        .about-panel {
            background: #fff;
            border-radius: 36px;
            padding: 64px 56px;
            box-shadow: var(--panel-shadow);
            text-align: center;
        }

        .stats {
            justify-content: center;
            margin-top: 40px;
        }

        .stat {
            min-width: 170px;
            padding: 22px 24px;
            border-radius: 20px;
            background: linear-gradient(180deg, #fff8ef, #fff1de);
        }

        .stat strong {
            display: block;
            font-size: 2rem;
            color: var(--orange);
        }

        .stat span { color: var(--muted); }
        .feature-grid { justify-content: center; }

        .feature-card {
            width: min(100%, 238px);
            background: #fff;
            border-radius: 26px;
            padding: 28px 24px;
            text-align: center;
            box-shadow: var(--panel-shadow);
        }

        .feature-icon {
            width: 76px;
            height: 76px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffcc77, #ff8b1f);
            display: grid;
            place-items: center;
            font-size: 1.6rem;
            font-weight: 900;
            color: #3a2203;
        }

        .feature-card h3 {
            margin: 0 0 12px;
            font-size: 1.2rem;
        }

        .feature-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .cta-banner {
            overflow: hidden;
            position: relative;
            background:
                linear-gradient(90deg, rgba(14, 14, 14, 0.95), rgba(14, 14, 14, 0.7)),
                linear-gradient(90deg, #3b1b08, #cc6a14);
            color: #fff;
            min-height: 420px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            padding: 48px 56px;
            box-shadow: var(--panel-shadow);
        }

        .cta-copy h2 {
            margin: 18px 0 14px;
            font-size: clamp(2.3rem, 4.5vw, 4rem);
            line-height: 1.05;
            text-transform: uppercase;
        }

        .cta-copy p {
            margin: 0 0 30px;
            color: #ffa63d;
            font-weight: 700;
            font-size: 1.05rem;
        }

        .cta-banner img {
            justify-self: end;
            width: min(100%, 500px);
            max-height: 340px;
            object-fit: contain;
        }

        .gallery-grid {
            gap: 22px;
            justify-content: center;
        }

        .gallery-card {
            width: min(100%, 285px);
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: var(--panel-shadow);
        }

        .gallery-card img {
            width: 100%;
            height: 270px;
            object-fit: cover;
            background: linear-gradient(180deg, #ffe39b, #f1be42);
        }

        .gallery-card .copy { padding: 20px 22px 24px; }

        .gallery-card h3 {
            margin: 0 0 10px;
            font-size: 1.15rem;
        }

        .gallery-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        footer {
            background:
                radial-gradient(circle at right top, rgba(255, 255, 255, 0.06), transparent 18%),
                #031321;
            color: #fff;
            padding-top: 100px;
        }

        .footer-band {
            background: var(--orange);
            color: #fff;
            border-radius: 28px;
            padding: 32px 38px;
            transform: translateY(-56px);
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.9fr);
            gap: 28px;
            align-items: stretch;
        }

        .footer-band-details {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            align-items: start;
        }

        .footer-band-item {
            min-width: 0;
        }

        .footer-band-cta {
            background: rgba(3, 19, 33, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 22px;
            padding: 22px 24px;
            align-self: stretch;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .footer-band-cta p {
            margin: 0 0 16px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }

        .footer-band-cta .btn {
            width: 100%;
            padding: 14px 20px;
            border-radius: 999px;
            background: #fff;
            color: #b94f00;
            box-shadow: none;
        }

        .footer-band-cta .btn:hover {
            background: #fff6e8;
        }

        .footer-band-item strong,
        .footer-col h3 {
            display: block;
            margin-bottom: 10px;
        }

        .footer-band-item span,
        .footer-col p,
        .footer-col li,
        .footer-bottom {
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.8;
        }

        .footer-grid {
            align-items: flex-start;
            padding-bottom: 56px;
        }

        .footer-col {
            flex: 1 1 220px;
            min-width: 220px;
        }

        .footer-logo {
            width: 94px;
            height: 94px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.14);
            background: #fff;
            margin-bottom: 18px;
        }

        .footer-col ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .footer-col li + li { margin-top: 8px; }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 18px 0 26px;
            text-align: center;
            font-size: 0.95rem;
        }

        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-14px); }
        }

        @media (max-width: 1080px) {
            .hero-inner,
            .cta-banner { grid-template-columns: 1fr; }
            .hero-card,
            .cta-banner img { justify-self: center; }
            .hero { min-height: auto; }
        }

        @media (max-width: 820px) {
            .nav-inner {
                padding: 18px 0;
                align-items: flex-start;
                flex-direction: column;
                min-height: auto;
            }

            .section,
            .section-compact { padding: 78px 0; }

            .hero-inner { padding: 56px 0; }
            .about-panel,
            .cta-banner { padding: 34px 24px; }

            .offer-badge {
                font-size: 1.5rem;
                padding: 15px 20px;
            }

            .footer-band {
                grid-template-columns: 1fr;
            }

            .footer-band-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .topbar-inner,
            .footer-grid { gap: 14px; }

            .hero h1 { font-size: 2.8rem; }
            .section-title h2,
            .cta-copy h2 { font-size: 2.2rem; }

            .product-card,
            .offer-card,
            .gallery-card { width: 100%; }

            .footer-band {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    @php
        $logo = asset('images/zurimart-logo.jpg');
        $product = asset('images/zuri-bread-pack.jpg');
        $products = [
            ['name' => 'Classic Family Loaf', 'text' => 'Fresh daily oven-baked loaf for breakfast, school lunch, and home tables.', 'price' => 'N2,500'],
            ['name' => 'Bromate-Free Goodness', 'text' => 'Made for safer, softer, better everyday bread enjoyment.', 'price' => '500g'],
            ['name' => 'Golden Crust Finish', 'text' => 'Rich bakery taste with a soft center and a bright, premium shelf look.', 'price' => 'Fresh Daily'],
            ['name' => 'Zurimart Signature Pack', 'text' => 'Sealed and branded for quality, confidence, and easy retail display.', 'price' => 'Trusted'],
        ];
        $offers = [
            ['eyebrow' => 'On This Week', 'title' => 'Fresh Daily Bread', 'text' => 'Early morning bakery batches', 'button' => 'Order Now', 'accent' => 'primary'],
            ['eyebrow' => 'Welcome Zurimart', 'title' => 'Today Special Food', 'text' => 'Soft loaf for every meal', 'button' => 'Discover More', 'accent' => 'secondary'],
            ['eyebrow' => 'Bakery Favourite', 'title' => 'Family Value Pack', 'text' => 'Great for homes and stores', 'button' => 'Contact Sales', 'accent' => 'primary'],
        ];
        $features = [
            ['icon' => '01', 'title' => 'Bromate-Free', 'text' => 'A cleaner bread choice prepared with your family in mind.'],
            ['icon' => '02', 'title' => '500g Pack', 'text' => 'A practical loaf size for homes, mini marts, and neighborhood shops.'],
            ['icon' => '03', 'title' => 'Freshly Baked', 'text' => 'Produced to deliver a soft bite, rich aroma, and shelf appeal.'],
            ['icon' => '04', 'title' => 'Retail Ready', 'text' => 'Strong branding and packaging that stands out on display.'],
        ];
        $gallery = [
            ['title' => 'Front View', 'text' => 'The premium retail-facing pack with strong shelf presence.'],
            ['title' => 'Brand Identity', 'text' => 'Zurimart Bakery branding built around trust and consistency.'],
            ['title' => 'Product Display', 'text' => 'A clean presentation style for online and offline promotions.'],
            ['title' => 'Daily Freshness', 'text' => 'A bright, warm visual language inspired by bakery freshness.'],
        ];
    @endphp

    <div class="topbar">
        <div class="container topbar-inner">
            <div class="topbar-list">
                <span>Fresh bakery quality for homes and stores</span>
                <span>/</span>
                <span>Mon - Sat: 8.00 am - 6.00 pm</span>
            </div>
            <div class="socials">
                <span>Follow Us:</span>
                <a href="#contact">Facebook</a>
                <a href="#contact">Instagram</a>
                <a href="#contact">WhatsApp</a>
            </div>
        </div>
    </div>

    <header class="nav-shell">
        <div class="container nav-inner">
            <a class="brand" href="#home">
                <img src="{{ $logo }}" alt="Zurimart Bakery logo">
                <span class="brand-copy">
                    <strong>Zurimart Bakery</strong>
                    <span>Zuri Bread</span>
                </span>
            </a>
            <nav class="nav-links">
                <a href="#home">Home</a>
                <a href="#products">Popular Items</a>
                <a href="#about">About</a>
                <a href="#benefits">Benefits</a>
                <a href="#gallery">Gallery</a>
                <a href="#contact">Contact</a>
            </nav>
            <div class="hero-actions">
                <a class="btn btn-ghost" href="{{ route('login') }}">Admin Login</a>
                <a class="btn btn-primary" href="#contact">Order Now</a>
            </div>
        </div>
    </header>

    <section class="hero" id="home">
        <div class="container hero-inner">
            <div>
                <div class="eyebrow">Welcome Zurimart</div>
                <h1>Fresh Zuri Bread For Every Table</h1>
                <p>
                    Discover Zurimart Bakery's bromate-free 500g bread loaf, crafted for families, retailers,
                    and everyday moments that deserve softness, freshness, and dependable quality.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#products">Order Now</a>
                    <a class="btn btn-secondary" href="#about">Learn More</a>
                </div>
            </div>
            <div class="hero-card">
                <div class="offer-badge">Fresh<br>Daily</div>
                <div class="hero-pack">
                    <img src="{{ $product }}" alt="Zuri Bread product pack">
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="products">
        <div class="container">
            <div class="section-title">
                <div class="eyebrow" style="justify-content:center;">Best Food</div>
                <h2>Popular Product Highlights</h2>
                <p>
                    The Fresheat-style layout is now adapted for your bread product, keeping the same premium
                    storefront energy while focusing the story on Zuri Bread.
                </p>
            </div>
            <div class="products">
                @foreach ($products as $item)
                    <article class="product-card">
                        <div class="product-visual">
                            <img src="{{ $product }}" alt="{{ $item['name'] }}">
                        </div>
                        <h3>{{ $item['name'] }}</h3>
                        <p>{{ $item['text'] }}</p>
                        <span class="price">{{ $item['price'] }}</span>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-compact">
        <div class="container">
            <div class="offer-grid">
                @foreach ($offers as $offer)
                    <article class="offer-card">
                        <div class="badge-mini">Best<br>Seller</div>
                        <img src="{{ $product }}" alt="{{ $offer['title'] }}">
                        <div class="offer-copy">
                            <div class="eyebrow" style="margin-bottom:0; color:var(--red);">{{ $offer['eyebrow'] }}</div>
                            <h3>{{ $offer['title'] }}</h3>
                            <p>{{ $offer['text'] }}</p>
                            <a class="btn {{ $offer['accent'] === 'secondary' ? 'btn-secondary' : 'btn-primary' }}" href="#contact">{{ $offer['button'] }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section" id="about">
        <div class="container">
            <div class="about-panel">
                <div class="eyebrow" style="justify-content:center;">About Us</div>
                <div class="section-title" style="margin-bottom:0;">
                    <h2>Variety Of Flavours From A Trusted Bakery Brand</h2>
                    <p>
                        Zurimart Bakery is positioned as a warm, modern bakery brand. This homepage brings that
                        identity online with a premium food-template feel while clearly presenting the real product:
                        Zuri Bread, a bromate-free loaf designed for freshness, convenience, and daily enjoyment.
                    </p>
                </div>
                <div class="stats">
                    <div class="stat"><strong>500g</strong><span>Pack Size</span></div>
                    <div class="stat"><strong>100%</strong><span>Bromate-Free</span></div>
                    <div class="stat"><strong>Daily</strong><span>Fresh Batches</span></div>
                    <div class="stat"><strong>Retail</strong><span>Ready Display</span></div>
                </div>
                <div class="hero-actions" style="justify-content:center; margin-top:34px;">
                    <a class="btn btn-primary" href="#contact">Order Now</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section-compact" id="benefits">
        <div class="container">
            <div class="section-title">
                <div class="eyebrow" style="justify-content:center;">Popular Dishes</div>
                <h2>Best Selling Benefits</h2>
                <p>
                    Each card below replaces the food-menu storytelling of the original template with bread-first
                    messaging that fits your product and market.
                </p>
            </div>
            <div class="feature-grid">
                @foreach ($features as $feature)
                    <article class="feature-card">
                        <div class="feature-icon">{{ $feature['icon'] }}</div>
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-compact">
        <div class="container">
            <div class="cta-banner">
                <div class="cta-copy">
                    <div class="eyebrow" style="color:var(--red);">Welcome Zurimart</div>
                    <h2>Today Special Food</h2>
                    <p>Soft, golden, everyday bread for family meals and retail shelves.</p>
                    <a class="btn btn-primary" href="#contact">Order Now</a>
                </div>
                <img src="{{ $product }}" alt="Zuri Bread promotional display">
            </div>
        </div>
    </section>

    <section class="section" id="gallery">
        <div class="container">
            <div class="section-title">
                <div class="eyebrow" style="justify-content:center;">Gallery</div>
                <h2>Brand And Product Gallery</h2>
                <p>
                    The template screenshots included a gallery strip and branded footer area, so this version keeps
                    that same structure using your own logo and bread pack.
                </p>
            </div>
            <div class="gallery-grid">
                @foreach ($gallery as $index => $item)
                    <article class="gallery-card">
                        <img src="{{ $index % 2 === 0 ? $product : $logo }}" alt="{{ $item['title'] }}">
                        <div class="copy">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['text'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container">
            <div class="footer-band">
                <div class="footer-band-details">
                    <div class="footer-band-item">
                        <strong>Address</strong>
                        <span>Zurimart Bakery, Nigeria</span>
                    </div>
                    <div class="footer-band-item">
                        <strong>Send Email</strong>
                        <span>sales@zurimartbakery.com</span>
                    </div>
                    <div class="footer-band-item">
                        <strong>Call / WhatsApp</strong>
                        <span>Available on request for retail and bulk orders</span>
                    </div>
                </div>
                <div class="footer-band-item footer-band-cta">
                    <strong>Order Online</strong>
                    <p>Need bread for home, retail shelves, or bulk supply? Open the public order page and submit your request now.</p>
                    <a class="btn" href="{{ route('orders.create') }}">Go to Public Order Form</a>
                </div>
            </div>

            <div class="footer-grid">
                <div class="footer-col">
                    <img class="footer-logo" src="{{ $logo }}" alt="Zurimart Bakery logo">
                    <h3>Zurimart Bakery</h3>
                    <p>Premium bakery presentation inspired by the Fresheat template and tailored to Zuri Bread.</p>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#gallery">Our Gallery</a></li>
                        <li><a href="#benefits">Product Benefits</a></li>
                        <li><a href="{{ route('login') }}">Admin Login</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Our Product</h3>
                    <ul>
                        <li>Zuri Bread</li>
                        <li>Bromate-Free Loaf</li>
                        <li>500g Pack</li>
                        <li>Retail Ready Bread</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Us</h3>
                    <p>Monday - Saturday: 8am - 6pm</p>
                    <p>For household, retail, and wholesale enquiries, use the contact details above or the order button in this section.</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; {{ now()->year }} Zurimart Bakery. Built as a public product landing page for Zuri Bread.
        </div>
    </footer>
</body>
</html>
