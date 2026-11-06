
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

$kycur = isset($_SESSION["user_id"]);
$roli = $_SESSION["roli"] ?? "";
$emri = $_SESSION["emri"] ?? "";

$faqjaLibrave = ($kycur && $roli !== "administrator")
    ? "explorimilibrat.php"
    : "books.php";

$kerkimi = trim($_GET["kerko"] ?? "");
$librat = [];
$gabim = false;

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

try {
    if ($kerkimi !== "") {
        $sql = "SELECT id, titulli, autori, kategoria, sasia
                FROM books
                WHERE titulli LIKE ?
                   OR autori LIKE ?
                   OR kategoria LIKE ?
                ORDER BY id DESC
                LIMIT 8";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $term = "%" . $kerkimi . "%";
            $stmt->bind_param("sss", $term, $term, $term);
            $stmt->execute();

            $rezultati = $stmt->get_result();

            while ($libri = $rezultati->fetch_assoc()) {
                $librat[] = $libri;
            }

            $stmt->close();
        } else {
            $gabim = true;
        }
    } else {
        $sql = "SELECT id, titulli, autori, kategoria, sasia
                FROM books
                ORDER BY id DESC
                LIMIT 8";

        $rezultati = $conn->query($sql);

        if ($rezultati) {
            while ($libri = $rezultati->fetch_assoc()) {
                $librat[] = $libri;
            }
        } else {
            $gabim = true;
        }
    }
} catch (Throwable $e) {
    $gabim = true;
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Biblioteka Ime | Zbulo botën e librave</title>

    <meta name="description" content="Mirë se vini në Biblioteka Ime. Kërko libra dhe zbulo tituj të rinj për lexim.">

    <link rel="stylesheet" href="style.css">

    <style>
        .home-page {
            --home-blue: #3157d5;
            --home-dark: #15213b;
            --home-muted: #68758c;
        }

        .home-page .hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.4fr .6fr;
            align-items: center;
            gap: 25px;
            min-height: 370px;
            padding: 55px;
            margin-bottom: 30px;
            border-radius: 26px;
            background: linear-gradient(125deg, #111d36 0%, #1e438c 58%, #3157d5 100%);
            color: #fff;
            box-shadow: 0 20px 55px rgba(35, 66, 145, .18);
        }

        .home-page .hero::before {
            content: "";
            position: absolute;
            z-index: -1;
            width: 330px;
            height: 330px;
            right: -70px;
            top: -110px;
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 50%;
            box-shadow: 0 0 0 35px rgba(255,255,255,.035),
                        0 0 0 70px rgba(255,255,255,.025);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 13px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 30px;
            background: rgba(255,255,255,.09);
            color: #e0eaff;
            font-size: 12px;
            font-weight: 700;
        }

        .home-page .hero h1 {
            max-width: 620px;
            margin: 0 0 20px;
            color: #fff;
            font-size: clamp(34px, 4.5vw, 53px);
            line-height: 1.13;
            letter-spacing: -1.8px;
        }

        .home-page .hero p {
            max-width: 550px;
            color: #dbe6ff;
            font-size: 15px;
            line-height: 1.9;
        }

        .home-page .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 27px;
        }

        .home-page .hero-actions .button {
            background: #fff;
            color: #1e40af;
            box-shadow: none;
        }

        .home-page .hero-actions .button:hover {
            background: #e7efff;
            transform: translateY(-3px);
        }

        .home-page .hero-actions .button-light {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.3);
            color: #fff;
        }

        .home-page .hero-actions .button-light:hover {
            background: rgba(255,255,255,.17);
            color: #fff;
        }

        .hero-art {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 230px;
        }

        .hero-book {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 185px;
            height: 230px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 14px 24px 24px 14px;
            background: linear-gradient(145deg, #f6c977, #dc9341);
            box-shadow: 15px 18px 35px rgba(0,0,0,.25),
                        inset 9px 0 rgba(115, 61, 25, .13);
            font-size: 85px;
            transform: rotate(7deg);
            animation: homeFloat 4s ease-in-out infinite;
        }

        @keyframes homeFloat {
            0%, 100% { transform: translateY(0) rotate(7deg); }
            50% { transform: translateY(-10px) rotate(4deg); }
        }

        .home-welcome {
            padding: 15px 18px;
            margin-bottom: 22px;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e40af;
        }

        .home-search {
            position: relative;
            z-index: 3;
            padding: 25px;
            margin: -3px 0 42px;
            border: 1px solid #e6ebf3;
            border-radius: 19px;
            background: #fff;
            box-shadow: 0 12px 35px rgba(23, 42, 79, .07);
        }

        .home-search h2 {
            margin-bottom: 6px;
            color: #15213b;
            font-size: 20px;
        }

        .home-search > p {
            margin-bottom: 18px;
            color: #68758c;
            font-size: 13px;
        }

        .home-search form {
            display: flex;
            gap: 10px;
        }

        .home-search input {
            min-width: 0;
            flex: 1;
            height: 49px;
            padding: 0 16px;
            border: 1px solid #dce3ef;
            border-radius: 11px;
            background: #f9fbff;
            font-size: 14px;
        }

        .home-search input:focus {
            border-color: #3157d5;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(49,87,213,.09);
        }

        .home-search button {
            min-width: 115px;
            height: 49px;
        }

        .home-section {
            margin-bottom: 45px;
        }

        .home-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 21px;
        }

        .home-eyebrow {
            display: block;
            margin-bottom: 7px;
            color: #3157d5;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .home-section-heading h2 {
            color: #15213b;
            font-size: clamp(23px, 3vw, 29px);
            line-height: 1.3;
            letter-spacing: -.8px;
        }

        .home-section-heading p {
            margin-top: 7px;
            color: #68758c;
            font-size: 13px;
        }

        .home-view-all {
            flex-shrink: 0;
            padding: 10px 13px;
            border: 1px solid #dbe5ff;
            border-radius: 10px;
            color: #3157d5;
            font-size: 12px;
            font-weight: 700;
            transition: .2s ease;
        }

        .home-view-all:hover {
            background: #3157d5;
            color: #fff;
            transform: translateY(-2px);
        }

        .home-page .book-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .home-page .book-card {
            position: relative;
            overflow: hidden;
            padding: 22px;
            border: 1px solid #e6ebf3;
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(23,42,79,.035);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }

        .home-page .book-card:hover {
            transform: translateY(-6px);
            border-color: #cbd8ff;
            box-shadow: 0 16px 35px rgba(23,42,79,.1);
        }

        .home-book-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 57px;
            height: 66px;
            margin-bottom: 18px;
            border-radius: 9px 14px 14px 9px;
            background: linear-gradient(145deg, #e0eaff, #c7d7ff);
            box-shadow: inset 5px 0 rgba(49,87,213,.12);
            font-size: 28px;
            transition: transform .25s ease;
        }

        .home-page .book-card:hover .home-book-icon {
            transform: rotate(-5deg) scale(1.05);
        }

        .home-book-category {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            padding: 5px 9px;
            border-radius: 6px;
            background: #eff6ff;
            color: #3157d5;
            font-size: 10px;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .home-page .book-card h3 {
            margin: 13px 0 8px;
            color: #17233c;
            font-size: 16px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .home-book-author {
            min-height: 42px;
            color: #68758c;
            font-size: 12px;
        }

        .home-book-stock {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding-top: 13px;
            margin-top: 15px;
            border-top: 1px solid #edf0f6;
            color: #68758c;
            font-size: 11px;
        }

        .home-book-stock strong {
            color: #15213b;
        }

        .home-empty {
            grid-column: 1 / -1;
            padding: 38px 20px;
            border: 1px dashed #cbd5e1;
            border-radius: 17px;
            background: #fff;
            text-align: center;
        }

        .home-empty span {
            display: block;
            margin-bottom: 12px;
            font-size: 40px;
        }

        .home-empty h3 {
            margin-bottom: 8px;
            color: #15213b;
        }

        .home-empty p {
            color: #68758c;
            font-size: 13px;
        }

        .home-features {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .home-feature {
            padding: 25px;
            border: 1px solid #e6ebf3;
            border-radius: 17px;
            background: #fff;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .home-feature:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(23,42,79,.07);
        }

        .home-feature-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 47px;
            height: 47px;
            margin-bottom: 17px;
            border-radius: 13px;
            background: #eff6ff;
            font-size: 22px;
        }

        .home-feature h3 {
            margin-bottom: 9px;
            color: #15213b;
            font-size: 16px;
        }

        .home-feature p {
            margin-bottom: 15px;
            color: #68758c;
            font-size: 13px;
            line-height: 1.8;
        }

        .home-feature a {
            font-size: 12px;
            font-weight: 800;
        }

        .home-cta {
            position: relative;
            overflow: hidden;
            padding: 35px;
            margin: 15px 0 20px;
            border-radius: 21px;
            background: #eaf0ff;
            text-align: center;
        }

        .home-cta h2 {
            margin-bottom: 10px;
            color: #15213b;
            font-size: 25px;
        }

        .home-cta p {
            margin-bottom: 20px;
            color: #68758c;
            font-size: 14px;
        }

        .home-footer {
            padding: 25px 15px;
            margin-top: 35px;
            border-top: 1px solid #e6ebf3;
            color: #7b879b;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 1000px) {
            .home-page .hero {
                grid-template-columns: 1fr .45fr;
                padding: 38px;
            }

            .hero-book {
                width: 140px;
                height: 185px;
                font-size: 65px;
            }

            .home-page .book-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .home-page .hero {
                grid-template-columns: 1fr;
                padding: 30px 23px;
                border-radius: 19px;
            }

            .home-page .hero h1 {
                font-size: 35px;
                letter-spacing: -1px;
            }

            .home-page .hero p {
                font-size: 13px;
            }

            .hero-art {
                min-height: 140px;
                justify-content: flex-start;
                padding-left: 15px;
            }

            .hero-book {
                width: 95px;
                height: 125px;
                border-radius: 9px 15px 15px 9px;
                font-size: 45px;
            }

            .home-search {
                padding: 19px;
                margin-bottom: 35px;
            }

            .home-search form {
                flex-direction: column;
            }

            .home-search button {
                width: 100%;
            }

            .home-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .home-page .book-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .home-page .book-card {
                padding: 15px;
            }

            .home-page .book-card h3 {
                font-size: 14px;
            }

            .home-book-icon {
                width: 47px;
                height: 55px;
                font-size: 24px;
            }

            .home-features {
                grid-template-columns: 1fr;
            }

            .home-cta {
                padding: 27px 20px;
            }

            .home-cta h2 {
                font-size: 22px;
            }
        }

        @media (max-width: 360px) {
            .home-page .book-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-book {
                animation: none;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <a href="index.php" class="brand">
        <span aria-hidden="true">📚</span>
        <span>Biblioteka Ime</span>
    </a>

    <nav aria-label="Navigimi kryesor">
        <a href="index.php" class="active">Ballina</a>

        <a href="<?= h($faqjaLibrave) ?>">Librat</a>

        <?php if ($kycur): ?>
            <a href="dashboard.php">Paneli</a>

            <?php if ($roli === "administrator"): ?>
                <a href="users.php">Përdoruesit</a>
            <?php endif; ?>

            <a href="logout.php">Dil</a>
        <?php else: ?>
            <a href="login.php">Kyçu</a>
            <a href="register.php">Regjistrohu</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container home-page">

    <?php if ($kycur && $emri !== ""): ?>
        <div class="home-welcome">
            Mirë se u ktheve, <strong><?= h($emri) ?></strong>!
            Gjej librin tënd të radhës.
        </div>
    <?php endif; ?>

    <section class="hero">
        <div class="hero-content">

            <div class="hero-label">
                <span aria-hidden="true">✦</span>
                HAPËSIRA JOTE E LEXIMIT
            </div>

            <h1>Zbulo një botë të re në çdo libër.</h1>

            <p>
                Mirë se vini në Biblioteka Ime. Eksploro koleksionin tonë,
                kërko autorët e preferuar dhe gjej histori që ia vlen të lexohen.
            </p>

            <div class="hero-actions">
                <a href="<?= h($faqjaLibrave) ?>" class="button">
                    Eksploro librat →
                </a>

                <?php if (!$kycur): ?>
                    <a href="register.php" class="button-light">Krijo llogari</a>
                <?php else: ?>
                    <a href="dashboard.php" class="button-light">Hape panelin</a>
                <?php endif; ?>
            </div>

        </div>

        <div class="hero-art" aria-hidden="true">
            <div class="hero-book">📖</div>
        </div>
    </section>

    <section class="home-search" id="kerkimi">

        <h2>Gjej librin që po kërkon 🔎</h2>
        <p>Kërko me titull, emër autori ose kategori.</p>

        <form action="index.php" method="GET">

            <input
                type="search"
                name="kerko"
                placeholder="P.sh. romane, histori, emri i autorit..."
                value="<?= h($kerkimi) ?>"
                aria-label="Kërko libra"
            >

            <button type="submit" class="button">Kërko libra</button>

        </form>
    </section>

    <section class="home-section">

        <div class="home-section-heading">
            <div>
                <span class="home-eyebrow">KOLEKSIONI YNË</span>

                <h2>
                    <?= $kerkimi !== "" ? "Rezultatet e kërkimit" : "Zbulo librat" ?>
                </h2>

                <p>
                    <?= $kerkimi !== ""
                        ? "Rezultatet për: " . h($kerkimi)
                        : "Disa nga librat e regjistruar në bibliotekë." ?>
                </p>
            </div>

            <a href="<?= h($faqjaLibrave) ?>" class="home-view-all">
                Shiko të gjithë librat →
            </a>
        </div>

        <div class="book-grid">

            <?php if ($gabim): ?>

                <div class="home-empty">
                    <span>⚠️</span>
                    <h3>Librat nuk mund të ngarkoheshin</h3>
                    <p>Kontrollo lidhjen me databazën dhe tabelën books.</p>
                </div>

            <?php elseif (count($librat) > 0): ?>

                <?php foreach ($librat as $libri): ?>

                    <article class="book-card">

                        <div class="home-book-icon" aria-hidden="true">📘</div>

                        <?php if (!empty($libri["kategoria"])): ?>
                            <span class="home-book-category">
                                <?= h($libri["kategoria"]) ?>
                            </span>
                        <?php else: ?>
                            <span class="home-book-category">LIBËR</span>
                        <?php endif; ?>

                        <h3><?= h($libri["titulli"]) ?></h3>

                        <p class="home-book-author">
                            Autor: <?= h($libri["autori"]) ?>
                        </p>

                        <div class="home-book-stock">
                            <span>Gjendja</span>

                            <strong>
                                <?= (int)$libri["sasia"] > 0
                                    ? "Në dispozicion"
                                    : "Nuk ka kopje" ?>
                            </strong>
                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="home-empty">
                    <span>📚</span>

                    <?php if ($kerkimi !== ""): ?>

                        <h3>Nuk u gjet asnjë libër</h3>
                        <p>Provo të kërkosh me një titull ose autor tjetër.</p>

                        <br>

                        <a href="index.php" class="button">
                            Shfaq të gjithë librat
                        </a>

                    <?php else: ?>

                        <h3>Biblioteka po pret librat e parë</h3>
                        <p>Sapo të regjistrohen libra, ata do të shfaqen këtu.</p>

                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>
    </section>

    <section class="home-section">

        <div class="home-section-heading">
            <div>
                <span class="home-eyebrow">PËRVOJA JOTE</span>
                <h2>Gjithçka për leximin, në një vend.</h2>
                <p>Një mënyrë e thjeshtë për të eksploruar bibliotekën.</p>
            </div>
        </div>

        <div class="home-features">

            <article class="home-feature">
                <div class="home-feature-icon" aria-hidden="true">🔎</div>

                <h3>Kërkim i shpejtë</h3>

                <p>
                    Gjej librat sipas titullit, autorit ose kategorisë
                    pa pasur nevojë të kërkosh një nga një.
                </p>

                <a href="#kerkimi">Fillo kërkimin →</a>
            </article>

            <article class="home-feature">
                <div class="home-feature-icon" aria-hidden="true">📚</div>

                <h3>Koleksioni i librave</h3>

                <p>
                    Shfleto librat e regjistruar dhe shiko informacionet
                    kryesore për secilin prej tyre.
                </p>

                <a href="<?= h($faqjaLibrave) ?>">Shfleto librat →</a>
            </article>

            <article class="home-feature">
                <div class="home-feature-icon" aria-hidden="true">👤</div>

                <h3>Llogaria jote</h3>

                <?php if ($kycur): ?>

                    <p>
                        Vazhdo te paneli yt për të përdorur funksionet
                        e disponueshme për llogarinë tënde.
                    </p>

                    <a href="dashboard.php">Hape panelin →</a>

                <?php else: ?>

                    <p>
                        Krijo një llogari për të hyrë në funksionet
                        që ofron Biblioteka Ime.
                    </p>

                    <a href="register.php">Regjistrohu →</a>

                <?php endif; ?>

            </article>

        </div>
    </section>

    <?php if (!$kycur): ?>

        <section class="home-cta">

            <h2>Gati për të zbuluar librin tënd të radhës?</h2>

            <p>Hyr në Biblioteka Ime dhe fillo të eksplorosh.</p>

            <a href="register.php" class="button">
                Krijo llogari falas →
            </a>

        </section>

    <?php endif; ?>

</main>

<footer class="home-footer">
    <p>
        &copy; <?= date("Y") ?> Biblioteka Ime.
        Ndërtuar për dashamirësit e librit. 📚
    </p>
</footer>

</body>
</html>
