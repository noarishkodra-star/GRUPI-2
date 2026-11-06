<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

// Kontrollo nëse përdoruesi është kyçur
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// Merre emrin dhe rolin drejtpërdrejt nga databaza
$stmt = $conn->prepare("SELECT emri, roli FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$rezultati = $stmt->get_result();
$perdoruesi = $rezultati->fetch_assoc();
$stmt->close();

if (!$perdoruesi) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$emri = $perdoruesi["emri"] ?? "Përdorues";
$roli = strtolower(trim($perdoruesi["roli"] ?? ""));

// Normalizimi i roleve
if (in_array($roli, ["administrator", "admin"], true)) {
    $roli = "administrator";
} elseif (in_array($roli, ["punetor", "punetor/e", "punëtor/e", "puntor/e"], true)) {
    $roli = "punetor/e";
} else {
    // Çdo llogari pa rol ose me rol nxënësi trajtohet si nxënës
    $roli = "nxenes/e";
}

// Nëse roli është bosh ose i pasaktë, ruaje si nxënës
if (
    empty($perdoruesi["roli"]) ||
    strtolower(trim($perdoruesi["roli"])) === "pa rol"
) {
    $ndrysho = $conn->prepare("UPDATE users SET roli = ? WHERE id = ?");
    $roliRi = "nxenes/e";
    $ndrysho->bind_param("si", $roliRi, $user_id);
    $ndrysho->execute();
    $ndrysho->close();
}

// Përditëso sesionin
$_SESSION["emri"] = $emri;
$_SESSION["roli"] = $roli;

$eshteAdmin = ($roli === "administrator");

if ($roli === "administrator") {
    $roliShfaq = "Administrator";
} elseif ($roli === "punetor/e") {
    $roliShfaq = "Punëtor";
} else {
    $roliShfaq = "Nxënës";
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paneli - Biblioteka Ime</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3f6fb;
            color: #102442;
        }

        .nav {
            background: #102442;
            padding: 16px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 21px;
            font-weight: bold;
        }

        .logo img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #d8b66a;
        }

        .container {
            width: 90%;
            max-width: 1150px;
            margin: 40px auto;
        }

        .welcome {
            background: linear-gradient(135deg, #102442, #28649b);
            color: white;
            padding: 35px;
            border-radius: 18px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(16, 36, 66, 0.15);
        }

        .welcome h1 {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .welcome p {
            color: #e3eaf3;
            line-height: 1.6;
        }

        .user-info {
            display: inline-block;
            margin-top: 18px;
            background: rgba(255,255,255,0.13);
            padding: 10px 16px;
            border-radius: 20px;
            font-size: 14px;
        }

        .section-title {
            margin-bottom: 20px;
            font-size: 23px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 22px;
        }

        .card {
            background: white;
            padding: 27px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            border: 1px solid #e7edf4;
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.10);
        }

        .icon {
            font-size: 38px;
            margin-bottom: 16px;
        }

        .card h3 {
            margin-bottom: 10px;
            color: #102442;
        }

        .card p {
            color: #68778a;
            line-height: 1.5;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 11px 19px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            border: none;
        }

        .btn-gold {
            background: #d8b66a;
            color: #102442;
        }

        .btn-gold:hover {
            background: #c6a04b;
            transform: translateY(-2px);
        }

        .admin-label {
            display: inline-block;
            background: #e8eef7;
            color: #173f70;
            padding: 7px 13px;
            border-radius: 20px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 25px;
            margin-top: 45px;
            color: #718096;
            border-top: 1px solid #e1e7ef;
        }

        @media(max-width: 600px) {
            .nav {
                flex-direction: column;
                text-align: center;
            }

            .nav-links {
                justify-content: center;
            }

            .welcome {
                padding: 25px;
            }

            .welcome h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<header class="nav">

    <div class="logo">
        <img src="images/logo.png" alt="Logo">
        Biblioteka Ime
    </div>

    <nav class="nav-links">
        <a href="index.php">Ballina</a>
        <a href="dashboard.php">Paneli</a>
        <a href="logout.php">Dil</a>
    </nav>

</header>

<main class="container">

    <section class="welcome">
        <h1>Përshëndetje, <?= htmlspecialchars($emri, ENT_QUOTES, 'UTF-8') ?>!</h1>

        <p>
            Mirë se erdhe në panelin e Bibliotekës Ime.
            Këtu mund t'i përdorësh shërbimet e bibliotekës.
        </p>

        <div class="user-info">
            Roli: <?= htmlspecialchars($roliShfaq, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </section>

    <?php if ($eshteAdmin): ?>

        <div class="admin-label">PANELI I ADMINISTRATORIT</div>

        <h2 class="section-title">Menaxhimi i bibliotekës</h2>

        <section class="cards">

            <div class="card">
                <div class="icon">📦</div>
                <h3>Menaxho huazimet</h3>
                <p>Shiko kërkesat për kthimin e librave dhe konfirmo kthimet.</p>
                <a href="menaxhohuazimet.php" class="btn btn-gold">
                    Menaxho huazimet
                </a>
            </div>

            <div class="card">
                <div class="icon">📚</div>
                <h3>Menaxho librat</h3>
                <p>Shto, ndrysho dhe fshi libra nga biblioteka.</p>
                <a href="book.php" class="btn btn-gold">
                    Menaxho librat
                </a>
            </div>

            <div class="card">
                <div class="icon">👥</div>
                <h3>Menaxho nxënësit</h3>
                <p>Shiko dhe menaxho përdoruesit e bibliotekës.</p>
                <a href="users.php" class="btn btn-gold">
                    Shiko nxënësit
                </a>
            </div>

            <div class="card">
                <div class="icon">📖</div>
                <h3>Huazimet</h3>
                <p>Kontrollo rezervimet dhe huazimet e librave.</p>
                <a href="huazimet.php" class="btn btn-gold">
                    Shiko huazimet
                </a>
            </div>

            <div class="card">
                <div class="icon">👤</div>
                <h3>Profili im</h3>
                <p>Shiko të dhënat e llogarisë tënde.</p>
                <a href="profile.php" class="btn btn-gold">
                    Profili
                </a>
            </div>

        </section>

    <?php else: ?>

        <h2 class="section-title">Shërbimet për nxënësit</h2>

        <section class="cards">

            <div class="card">
                <div class="icon">📚</div>
                <h3>Librat</h3>
                <p>Eksploro librat e bibliotekës dhe rezervo librin që dëshiron.</p>
                <a href="explorimilibrat.php" class="btn btn-gold">
                    Eksploro librat
                </a>
            </div>

            <div class="card">
                <div class="icon">📖</div>
                <h3>Huazimet e mia</h3>
                <p>Kontrollo librat që ke rezervuar ose huazuar.</p>
                <a href="huazimet.php" class="btn btn-gold">
                    Shiko huazimet
                </a>
            </div>

            <div class="card">
                <div class="icon">👤</div>
                <h3>Profili im</h3>
                <p>Shiko të dhënat e llogarisë tënde.</p>
                <a href="profile.php" class="btn btn-gold">
                    Profili
                </a>
            </div>

        </section>

    <?php endif; ?>

</main>

<footer>
    © <?= date("Y") ?> Biblioteka Ime - Të gjitha të drejtat e rezervuara.
</footer>

</body>
</html>
