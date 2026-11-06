```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

/* Kontrollo kyçjen */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

/* Merr përdoruesin nga databaza */
$stmt = $conn->prepare(
    "SELECT emri, roli FROM users WHERE id = ?"
);

if (!$stmt) {
    exit("Gabim gjatë lidhjes me databazën.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$perdoruesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$perdoruesi) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* Të dhënat */
$emri = $perdoruesi["emri"] ?? "Përdorues";
$roli = strtolower(trim($perdoruesi["roli"] ?? ""));

/* Kontrolli i roleve */
$eshteAdmin = in_array($roli, [
    "administrator",
    "admin"
], true);

$eshteNxenes = in_array($roli, [
    "nxenes",
    "nxenes/e",
    "nxënës",
    "nxënës/e"
], true);

$eshtePunetor = in_array($roli, [
    "punetor",
    "punetor/e",
    "punëtor",
    "punëtor/e",
    "puntor",
    "puntor/e"
], true);

/* Ruaj të dhënat në sesion */
$_SESSION["emri"] = $emri;
$_SESSION["roli"] = $roli;

/* Nëse roli nuk njihet */
if (!$eshteAdmin && !$eshteNxenes && !$eshtePunetor) {
    $roliShfaqje = "Pa rol";
} elseif ($eshteAdmin) {
    $roliShfaqje = "Administrator";
} elseif ($eshtePunetor) {
    $roliShfaqje = "Punëtor";
} else {
    $roliShfaqje = "Nxënës";
}
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
    background: #f3f6fb;
    color: #102442;
    min-height: 100vh;
}

/* HEADER */
header {
    background: linear-gradient(120deg, #102442, #173f70);
    padding: 19px 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    border-bottom: 4px solid #d8b66a;
}

.logo {
    color: white;
    font-size: 25px;
    font-weight: bold;
}

.logo span {
    color: #d8b66a;
}

nav {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

nav a {
    color: #102442 !important;
    background: #d8b66a;
    text-decoration: none;
    padding: 11px 16px;
    border-radius: 8px;
    font-weight: bold;
    transition: .3s;
}

nav a:hover {
    background: #f0d58b;
    transform: translateY(-2px);
}

/* CONTAINER */
.container {
    width: 92%;
    max-width: 1250px;
    margin: 35px auto;
}

/* WELCOME */
.welcome {
    background:
        linear-gradient(rgba(16,36,66,.91), rgba(16,36,66,.91)),
        url("images/biblioteka.jpg") center/cover;

    padding: 45px 35px;
    border-radius: 17px;
    color: white;
    margin-bottom: 30px;
}

.welcome h1 {
    font-size: 30px;
    margin-bottom: 12px;
}

.welcome p {
    color: #e1e8f2;
    line-height: 1.7;
}

.role {
    display: inline-block;
    margin-top: 17px;
    background: #d8b66a;
    color: #102442;
    padding: 8px 15px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: bold;
}

/* SECTION */
.section-title {
    font-size: 25px;
    margin: 30px 0 18px;
    color: #102442;
}

/* CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
    gap: 20px;
}

.card {
    display: block;
    background: white;
    padding: 25px;
    border-radius: 15px;
    text-decoration: none;
    color: #102442 !important;
    border: 1px solid #e1e7ef;
    box-shadow: 0 5px 18px rgba(16,36,66,.06);
    transition: .25s;
    min-height: 170px;
}

.card:hover {
    transform: translateY(-6px);
    border-color: #d8b66a;
    box-shadow: 0 12px 25px rgba(16,36,66,.12);
}

.card-icon {
    font-size: 35px;
    margin-bottom: 16px;
}

.card h3 {
    font-size: 19px;
    margin-bottom: 10px;
    color: #102442;
}

.card p {
    font-size: 14px;
    line-height: 1.6;
    color: #667085;
}

.card.admin {
    border-top: 4px solid #d8b66a;
}

.card.student {
    border-top: 4px solid #28649b;
}

/* INFO */
.info {
    margin-top: 30px;
    background: white;
    padding: 22px;
    border-radius: 14px;
    border: 1px solid #e1e7ef;
}

.info h3 {
    margin-bottom: 12px;
}

.info p {
    color: #667085;
    line-height: 1.7;
}

/* FOOTER */
footer {
    text-align: center;
    padding: 25px;
    margin-top: 50px;
    color: #667085;
    border-top: 1px solid #e1e7ef;
}

@media(max-width: 600px) {
    header {
        justify-content: center;
        text-align: center;
    }

    nav {
        justify-content: center;
    }

    .welcome {
        padding: 30px 22px;
    }

    .welcome h1 {
        font-size: 24px;
    }

    .container {
        width: 94%;
    }

    .cards {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<header>

    <div class="logo">
        📚 Biblioteka <span>Ime</span>
    </div>

    <nav>
        <a href="index.php">🏠 Ballina</a>
        <a href="profile.php">👤 Profili</a>
        <a href="logout.php">🚪 Dil</a>
    </nav>

</header>

<main class="container">

    <section class="welcome">

        <h1>
            Mirë se erdhe, <?= htmlspecialchars($emri, ENT_QUOTES, "UTF-8") ?>!
        </h1>

        <p>
            Mirë se erdhe në panelin e Bibliotekës Ime.
            Këtu mund t'i përdorësh funksionet e bibliotekës.
        </p>

        <span class="role">
            <?= htmlspecialchars($roliShfaqje, ENT_QUOTES, "UTF-8") ?>
        </span>

    </section>


    <?php if ($eshteAdmin): ?>

        <!-- PANELI I ADMINISTRATORIT -->

        <h2 class="section-title">⚙️ Paneli i administratorit</h2>

        <div class="cards">

            <a href="explorimilibrat.php" class="card admin">
                <div class="card-icon">📚</div>
                <h3>Eksploro librat</h3>
                <p>
                    Shiko të gjithë librat dhe kopertinat
                    e regjistruara në bibliotekë.
                </p>
            </a>

            <a href="book.php" class="card admin">
                <div class="card-icon">📖</div>
                <h3>Menaxho librat</h3>
                <p>
                    Shto libra të rinj, ndrysho të dhënat
                    dhe fshij libra.
                </p>
            </a>

            <a href="menaxhohuazimet.php" class="card admin">
                <div class="card-icon">📋</div>
                <h3>Menaxho huazimet</h3>
                <p>
                    Shiko rezervimet, huazimet dhe kërkesat
                    për kthimin e librave.
                </p>
            </a>

            <a href="users.php" class="card admin">
                <div class="card-icon">👥</div>
                <h3>Menaxho përdoruesit</h3>
                <p>
                    Shiko dhe menaxho përdoruesit
                    e regjistruar në bibliotekë.
                </p>
            </a>

            <a href="huazimet.php" class="card admin">
                <div class="card-icon">📚</div>
                <h3>Huazimet</h3>
                <p>
                    Shiko faqen e huazimeve.
                </p>
            </a>

            <a href="profile.php" class="card admin">
                <div class="card-icon">👤</div>
                <h3>Profili im</h3>
                <p>
                    Shiko të dhënat e llogarisë tënde.
                </p>
            </a>

        </div>


    <?php elseif ($eshteNxenes): ?>

        <!-- PANELI I NXËNËSIT -->

        <h2 class="section-title">📚 Paneli i nxënësit</h2>

        <div class="cards">

            <a href="explorimilibrat.php" class="card student">
                <div class="card-icon">🔎</div>
                <h3>Eksploro librat</h3>
                <p>
                    Shiko librat dhe rezervoni librin
                    që dëshiron të lexosh.
                </p>
            </a>

            <a href="huazimet.php" class="card student">
                <div class="card-icon">📖</div>
                <h3>Huazimet e mia</h3>
                <p>
                    Shiko librat e rezervuar dhe huazuar
                    dhe kërko kthimin e tyre.
                </p>
            </a>

            <a href="profile.php" class="card student">
                <div class="card-icon">👤</div>
                <h3>Profili im</h3>
                <p>
                    Shiko të dhënat e llogarisë tënde.
                </p>
            </a>

        </div>


    <?php elseif ($eshtePunetor): ?>

        <!-- PANELI I PUNËTORIT -->

        <h2 class="section-title">📚 Paneli i punëtorit</h2>

        <div class="cards">

            <a href="explorimilibrat.php" class="card student">
                <div class="card-icon">🔎</div>
                <h3>Eksploro librat</h3>
                <p>
                    Shiko librat dhe kopertinat e tyre.
                </p>
            </a>

            <a href="huazimet.php" class="card student">
                <div class="card-icon">📋</div>
                <h3>Huazimet</h3>
                <p>
                    Shiko faqen e huazimeve.
                </p>
            </a>

            <a href="profile.php" class="card student">
                <div class="card-icon">👤</div>
                <h3>Profili im</h3>
                <p>
                    Shiko të dhënat e llogarisë tënde.
                </p>
            </a>

        </div>


    <?php else: ?>

        <div class="info">
            <h3>⚠️ Roli nuk është caktuar</h3>
            <p>
                Llogaria jote nuk ka një rol të njohur.
                Kontakto administratorin e bibliotekës
                për të kontrolluar rolin në databazë.
            </p>
        </div>

    <?php endif; ?>


    <section class="info">
        <h3>📚 Biblioteka Ime</h3>
        <p>
            Sistemi i bibliotekës për menaxhimin e librave,
            rezervimeve dhe huazimeve.
        </p>
    </section>

</main>

<footer>
    © <?= date("Y") ?> Biblioteka Ime.
    Të gjitha të drejtat e rezervuara.
</footer>

</body>
</html>
```
