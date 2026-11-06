<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$emri = $_SESSION["emri"] ?? "Përdorues";
$roli = $_SESSION["roli"] ?? "nxenes/e";

$eshteAdmin = ($roli === "administrator");
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Paneli | Biblioteka Ime</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #172554;
            color: white;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
        }

        .logo span {
            color: #60a5fa;
        }

        nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        nav a {
            display: inline-block;
            color: white;
            text-decoration: none;
            background: #2563eb;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        nav a:hover {
            background: #1d4ed8;
        }

        .user-info {
            font-size: 14px;
            line-height: 1.7;
        }

        .container {
            width: 90%;
            max-width: 1150px;
            margin: 35px auto;
        }

        .welcome {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border-left: 5px solid #2563eb;
        }

        .welcome h1 {
            font-size: 29px;
            margin-bottom: 12px;
            color: #172554;
        }

        .welcome p {
            color: #64748b;
            line-height: 1.7;
        }

        .section-title {
            margin-bottom: 18px;
            font-size: 22px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: 0.2s;
            border: 1px solid #e2e8f0;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .icon {
            font-size: 34px;
            margin-bottom: 15px;
        }

        .card h3 {
            margin-bottom: 10px;
            color: #172554;
        }

        .card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background: #2563eb;
            color: white;
            padding: 11px 17px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .btn-red {
            background: #dc2626;
        }

        .btn-red:hover {
            background: #b91c1c;
        }

        .admin-label {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 12px;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
            font-size: 13px;
            margin-top: 40px;
        }

        @media (max-width: 650px) {
            header {
                padding: 20px;
            }

            .container {
                width: 92%;
                margin-top: 20px;
            }

            .welcome {
                padding: 22px;
            }

            .welcome h1 {
                font-size: 24px;
            }

            .logo {
                font-size: 22px;
            }

            nav {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="logo">📚 Biblioteka <span>Ime</span></div>

    <nav>
        <a href="index.php">🏠 Ballina</a>
        <a href="dashboard.php">📊 Paneli</a>
    </nav>

    <div class="user-info">
        Mirë se erdhe,
        <strong><?= htmlspecialchars($emri, ENT_QUOTES, 'UTF-8') ?></strong>
        <br>
        Roli:
        <strong><?= htmlspecialchars($roli, ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <h1>Mirë se erdhe në panelin tënd!</h1>

        <p>
            Këtu mund t'i shikosh librat, huazimet dhe informacionet
            e llogarisë tënde në Biblioteka Ime.
        </p>

        <?php if ($eshteAdmin): ?>
            <span class="admin-label">ADMINISTRATOR</span>
        <?php endif; ?>
    </section>

    <h2 class="section-title">📌 Shërbimet e bibliotekës</h2>

    <div class="cards">

        <div class="card">
            <div class="icon">📚</div>
            <h3>Librat</h3>
            <p>Shiko librat dhe informacionet për koleksionin e bibliotekës.</p>
            <a href="librat.php" class="btn">Shiko librat</a>
        </div>

        <div class="card">
            <div class="icon">🔖</div>
            <h3>Huazimet e mia</h3>
            <p>Kontrollo informacionet për librat e huazuar.</p>
            <a href="huazimet.php" class="btn">Shiko huazimet</a>
        </div>

        <?php if ($eshteAdmin): ?>

        <div class="card">
            <div class="icon">📖</div>
            <h3>Menaxhimi i librave</h3>
            <p>Shto libra të rinj dhe përditëso informacionet e tyre.</p>
            <a href="books.php" class="btn">Menaxho librat</a>
        </div>

        <div class="card">
            <div class="icon">👥</div>
            <h3>Menaxhimi i përdoruesve</h3>
            <p>Shiko dhe menaxho llogaritë e përdoruesve.</p>
            <a href="users.php" class="btn">Menaxho përdoruesit</a>
        </div>

        <?php endif; ?>

        <div class="card">
            <div class="icon">👤</div>
            <h3>Profili im</h3>
            <p>Shiko informacionet e llogarisë tënde.</p>
            <a href="profile.php" class="btn">Hap profilin</a>
        </div>

        <div class="card">
            <div class="icon">🚪</div>
            <h3>Dil nga llogaria</h3>
            <p>Dil në mënyrë të sigurt nga llogaria jote.</p>
            <a href="logout.php" class="btn btn-red">Dil nga llogaria</a>
        </div>

    </div>

</main>

<footer>
    &copy; <?= date("Y") ?> Biblioteka Ime. Të gjitha të drejtat e rezervuara.
</footer>

</body>
</html>
