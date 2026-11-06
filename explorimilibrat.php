
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

// Kontrollo kyçjen
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$emri = $_SESSION["emri"] ?? "Përdorues";
$roli = strtolower(trim($_SESSION["roli"] ?? ""));

$eshteNxenes = in_array($roli, [
    "nxenes/e",
    "nxenes",
    "nxënës",
    "nxënës/e"
], true);

$mesazhi = "";
$llojiMesazhit = "";

function h($vlera) {
    return htmlspecialchars((string)$vlera, ENT_QUOTES, "UTF-8");
}

// ======================================
// REZERVIMI I LIBRIT
// ======================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {

    if ($_POST["action"] === "rezervo") {

        if (!$eshteNxenes) {
            $mesazhi = "Vetëm nxënësit mund të rezervojnë libra.";
            $llojiMesazhit = "gabim";

        } else {

            $libri_id = (int)($_POST["libri_id"] ?? 0);
            $ditet = (int)($_POST["ditet"] ?? 0);

            if ($libri_id <= 0) {
                $mesazhi = "Libri nuk u identifikua.";
                $llojiMesazhit = "gabim";

            } elseif ($ditet < 1 || $ditet > 7) {
                $mesazhi = "Zgjidh një afat nga 1 deri në 7 ditë.";
                $llojiMesazhit = "gabim";

            } else {

                $transaksioniFilloi = false;

                try {
                    $conn->begin_transaction();
                    $transaksioniFilloi = true;

                    // Kontrollo librin dhe kopjet
                    $stmt = $conn->prepare(
                        "SELECT id, sasia
                         FROM books
                         WHERE id = ?
                         FOR UPDATE"
                    );

                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }

                    $stmt->bind_param("i", $libri_id);
                    $stmt->execute();

                    $libri = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$libri) {
                        throw new Exception("Ky libër nuk ekziston.");
                    }

                    if ((int)$libri["sasia"] <= 0) {
                        throw new Exception("Ky libër nuk ka kopje të lira.");
                    }

                    // Kontrollo rezervimet aktive
                    $stmt = $conn->prepare(
                        "SELECT id
                         FROM borrowings
                         WHERE user_id = ?
                         AND book_id = ?
                         AND statusi IN ('rezervuar', 'huazuar')
                         LIMIT 1"
                    );

                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }

                    $stmt->bind_param("ii", $user_id, $libri_id);
                    $stmt->execute();

                    $ekziston = $stmt->get_result()->num_rows > 0;
                    $stmt->close();

                    if ($ekziston) {
                        throw new Exception(
                            "Ti tashmë e ke rezervuar këtë libër."
                        );
                    }

                    // Zvogëlo sasinë për një kopje
                    $stmt = $conn->prepare(
                        "UPDATE books
                         SET sasia = sasia - 1
                         WHERE id = ? AND sasia > 0"
                    );

                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }

                    $stmt->bind_param("i", $libri_id);
                    $stmt->execute();

                    if ($stmt->affected_rows !== 1) {
                        throw new Exception("Nuk ka kopje të lira.");
                    }

                    $stmt->close();

                    // Regjistro rezervimin me afatin e zgjedhur
                    $stmt = $conn->prepare(
                        "INSERT INTO borrowings
                         (user_id, book_id, data_huazimit,
                          data_kthimit, statusi)
                         VALUES (
                             ?, ?,
                             CURDATE(),
                             DATE_ADD(CURDATE(), INTERVAL ? DAY),
                             'rezervuar'
                         )"
                    );

                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }

                    $stmt->bind_param("iii", $user_id, $libri_id, $ditet);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();
                    $transaksioniFilloi = false;

                    $mesazhi = "Libri u rezervua me sukses për $ditet ditë!";
                    $llojiMesazhit = "sukses";

                } catch (Throwable $e) {

                    if ($transaksioniFilloi) {
                        $conn->rollback();
                    }

                    $mesazhi = "Gabim gjatë rezervimit: " . $e->getMessage();
                    $llojiMesazhit = "gabim";
                }
            }
        }
    }
}

// ======================================
// MARRJA E LIBRAVE
// ======================================

$sql = "SELECT id, titulli, autori, kategoria, sasia, kopertina
        FROM books
        ORDER BY id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Gabim gjatë marrjes së librave: " . h($conn->error));
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Eksploro librat - Biblioteka Ime</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f3f6fb;
    color: #102442;
}

header {
    background: #102442;
    color: white;
    padding: 18px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.logo {
    font-size: 23px;
    font-weight: bold;
}

nav {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

nav a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    transition: .3s;
}

nav a:hover {
    color: #d8b66a;
}

.hero {
    background:
        linear-gradient(rgba(16,36,66,.90), rgba(16,36,66,.90)),
        url("images/biblioteka.jpg") center/cover;

    color: white;
    text-align: center;
    padding: 65px 20px;
}

.hero h1 {
    font-size: 36px;
    margin-bottom: 12px;
}

.hero p {
    color: #e1e8f2;
}

.container {
    width: 90%;
    max-width: 1250px;
    margin: 40px auto;
}

.titulli {
    text-align: center;
    margin-bottom: 30px;
}

.titulli h2 {
    font-size: 29px;
    margin-bottom: 8px;
}

.titulli p {
    color: #64748b;
}

.mesazhi {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    text-align: center;
    font-weight: bold;
}

.sukses {
    background: #d1fae5;
    color: #065f46;
}

.gabim {
    background: #fee2e2;
    color: #991b1b;
}

.librat {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 25px;
}

.libri {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,.07);
    transition: .3s;
    border: 1px solid #e5eaf1;
}

.libri:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0,0,0,.12);
}

.kopertina {
    width: 100%;
    height: 260px;
    background: #e8edf5;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.kopertina img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pa-kopertine {
    color: #64748b;
    font-size: 15px;
}

.libri-info {
    padding: 20px;
}

.libri-info h3 {
    font-size: 19px;
    margin-bottom: 12px;
}

.libri-info p {
    color: #64748b;
    margin-bottom: 8px;
    font-size: 14px;
}

.sasia {
    margin-top: 13px;
    font-weight: bold;
    color: #173f70;
}

.zgjedh-ditet {
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    border: 1px solid #d1d9e6;
    border-radius: 9px;
    background: white;
    color: #102442;
    font-size: 15px;
    cursor: pointer;
}

.btn-rezervo {
    width: 100%;
    border: none;
    background: #d8b66a;
    color: #102442;
    padding: 13px;
    border-radius: 9px;
    font-weight: bold;
    font-size: 15px;
    cursor: pointer;
    margin-top: 12px;
    transition: .3s;
}

.btn-rezervo:hover {
    background: #c5a04e;
    transform: translateY(-2px);
}

.btn-rezervo:disabled {
    background: #cbd5e1;
    color: #64748b;
    cursor: not-allowed;
    transform: none;
}

.bosh {
    text-align: center;
    background: white;
    padding: 45px 20px;
    border-radius: 15px;
    color: #64748b;
}

footer {
    background: #102442;
    color: white;
    text-align: center;
    padding: 22px;
    margin-top: 60px;
}

@media(max-width: 600px) {

    header {
        justify-content: center;
        text-align: center;
    }

    nav {
        justify-content: center;
    }

    .hero h1 {
        font-size: 28px;
    }

    .container {
        width: 94%;
    }
}

</style>

</head>

<body>

<header>

    <div class="logo">📚 Biblioteka Ime</div>

    <nav>
        <a href="index.php">Ballina</a>
        <a href="explorimilibrat.php">Librat</a>
        <a href="huazimet.php">Huazimet e mia</a>
        <a href="dashboard.php">Paneli</a>
        <a href="profile.php">Profili</a>
        <a href="logout.php">Dil</a>
    </nav>

</header>

<section class="hero">

    <h1>Eksploro librat</h1>

    <p>
        Përshëndetje, <?= h($emri) ?>!
        Gjej librin që dëshiron të lexosh.
    </p>

</section>

<main class="container">

    <div class="titulli">
        <h2>Lista e librave</h2>
        <p>Zgjidh librin dhe cakto sa ditë dëshiron ta rezervosh.</p>
    </div>

    <?php if ($mesazhi !== ""): ?>

        <div class="mesazhi <?= h($llojiMesazhit) ?>">
            <?= h($mesazhi) ?>
        </div>

    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>

        <div class="librat">

            <?php while ($libri = $result->fetch_assoc()): ?>

                <article class="libri">

                    <div class="kopertina">

                        <?php
                        $kopertina = trim($libri["kopertina"] ?? "");
                        $rrugaKopertines = "uploads/kopertina/" . basename($kopertina);
                        ?>

                        <?php if ($kopertina !== ""): ?>

                            <img
                                src="<?= h($rrugaKopertines) ?>"
                                alt="<?= h($libri["titulli"]) ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                            >

                            <span class="pa-kopertine" style="display:none;">
                                Kopertina nuk u gjet
                            </span>

                        <?php else: ?>

                            <span class="pa-kopertine">
                                Pa kopertinë
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="libri-info">

                        <h3><?= h($libri["titulli"]) ?></h3>

                        <p>
                            <strong>Autori:</strong>
                            <?= h($libri["autori"]) ?>
                        </p>

                        <p>
                            <strong>Kategoria:</strong>
                            <?= h($libri["kategoria"]) ?>
                        </p>

                        <div class="sasia">
                            Kopje të lira: <?= (int)$libri["sasia"] ?>
                        </div>

                        <?php if ($eshteNxenes): ?>

                            <?php if ((int)$libri["sasia"] > 0): ?>

                                <form method="POST" action="explorimilibrat.php">

                                    <input
                                        type="hidden"
                                        name="libri_id"
                                        value="<?= (int)$libri["id"] ?>"
                                    >

                                    <label for="ditet-<?= (int)$libri["id"] ?>">
                                        Sa ditë dëshiron ta rezervosh?
                                    </label>

                                    <select
                                        name="ditet"
                                        id="ditet-<?= (int)$libri["id"] ?>"
                                        class="zgjedh-ditet"
                                        required
                                    >

                                        <?php for ($i = 1; $i <= 7; $i++): ?>

                                            <option
                                                value="<?= $i ?>"
                                                <?= $i === 7 ? "selected" : "" ?>
                                            >
                                                <?= $i ?> <?= $i === 1 ? "ditë" : "ditë" ?>
                                            </option>

                                        <?php endfor; ?>

                                    </select>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="rezervo"
                                        class="btn-rezervo"
                                    >
                                        Rezervo librin
                                    </button>

                                </form>

                            <?php else: ?>

                                <button class="btn-rezervo" disabled>
                                    Nuk ka kopje të lira
                                </button>

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="bosh">
            <h2>Nuk ka libra të regjistruar</h2>
            <p>Kur administratori të shtojë libra, ata do të shfaqen këtu.</p>
        </div>

    <?php endif; ?>

</main>

<footer>
    © <?= date("Y") ?> Biblioteka Ime - Të gjitha të drejtat e rezervuara.
</footer>

</body>
</html>

