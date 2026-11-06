
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

/* KONTROLLO KYÇJEN */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* MERR PËRDORUESIN */
$user_id = (int)$_SESSION["user_id"];

$stmt = $conn->prepare("SELECT emri, roli FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$perdoruesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$perdoruesi) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$emri = $perdoruesi["emri"] ?? "Përdorues";
$roli = mb_strtolower(trim($perdoruesi["roli"] ?? ""), "UTF-8");

/* KONTROLLI I ROLEVE */
$eshteAdmin = in_array($roli, [
    "administrator",
    "admin"
], true);

$eshtePunetor = in_array($roli, [
    "punetor",
    "punetor/e",
    "punëtor",
    "punëtor/e",
    "puntor",
    "puntor/e"
], true);

if (!$eshteAdmin && !$eshtePunetor) {
    http_response_code(403);
    exit("Nuk ke leje për të menaxhuar librat.");
}

$mesazhi = "";
$llojiMesazhit = "success";

/* SIGURO DOSJEN UPLOADS */
$dosja = __DIR__ . "/uploads/";

if (!is_dir($dosja)) {
    mkdir($dosja, 0755, true);
}

/* SIGURIA E TEKSTIT */
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

/* SHTO LIBËR */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["shto"])) {

    $titulli = trim($_POST["titulli"] ?? "");
    $autori = trim($_POST["autori"] ?? "");
    $kategoria = trim($_POST["kategoria"] ?? "");
    $sasia = (int)($_POST["sasia"] ?? 0);
    $kopertina = "";

    if (
        $titulli === "" ||
        $autori === "" ||
        $kategoria === "" ||
        $sasia < 0
    ) {
        $mesazhi = "Plotëso të gjitha fushat saktë.";
        $llojiMesazhit = "error";
    } else {

        $ngarkimiOK = true;

        if (
            isset($_FILES["kopertina"]) &&
            $_FILES["kopertina"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES["kopertina"]["error"] !== UPLOAD_ERR_OK) {
                $mesazhi = "Ngarkimi i kopertinës dështoi.";
                $llojiMesazhit = "error";
                $ngarkimiOK = false;
            } else {
                $tmp = $_FILES["kopertina"]["tmp_name"];
                $info = getimagesize($tmp);

                $mime = $info["mime"] ?? "";

                $lejohen = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (!$info || !isset($lejohen[$mime])) {
                    $mesazhi = "Kopertina duhet të jetë JPG, PNG ose WEBP.";
                    $llojiMesazhit = "error";
                    $ngarkimiOK = false;

                } elseif ($_FILES["kopertina"]["size"] > 3 * 1024 * 1024) {
                    $mesazhi = "Kopertina nuk duhet të jetë më e madhe se 3 MB.";
                    $llojiMesazhit = "error";
                    $ngarkimiOK = false;

                } else {
                    $emriSkedarit = "libri_" .
                        bin2hex(random_bytes(8)) . "." .
                        $lejohen[$mime];

                    if (move_uploaded_file($tmp, $dosja . $emriSkedarit)) {
                        $kopertina = "uploads/" . $emriSkedarit;
                    } else {
                        $mesazhi = "Nuk u ruajt kopertina.";
                        $llojiMesazhit = "error";
                        $ngarkimiOK = false;
                    }
                }
            }
        }

        if ($ngarkimiOK) {
            $stmt = $conn->prepare(
                "INSERT INTO books
                (titulli, autori, kategoria, sasia, kopertina)
                VALUES (?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "sssis",
                    $titulli,
                    $autori,
                    $kategoria,
                    $sasia,
                    $kopertina
                );

                if ($stmt->execute()) {
                    $mesazhi = "Libri u shtua me sukses!";
                } else {
                    $mesazhi = "Gabim gjatë ruajtjes: " . $stmt->error;
                    $llojiMesazhit = "error";
                }

                $stmt->close();
            } else {
                $mesazhi = "Gabim gjatë përgatitjes së kërkesës.";
                $llojiMesazhit = "error";
            }
        }
    }
}

/* NDRYSHO LIBËR */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ndrysho"])) {

    $id = (int)($_POST["id"] ?? 0);
    $titulli = trim($_POST["titulli"] ?? "");
    $autori = trim($_POST["autori"] ?? "");
    $kategoria = trim($_POST["kategoria"] ?? "");
    $sasia = (int)($_POST["sasia"] ?? 0);

    if (
        $id <= 0 ||
        $titulli === "" ||
        $autori === "" ||
        $kategoria === "" ||
        $sasia < 0
    ) {
        $mesazhi = "Të dhënat e librit nuk janë të sakta.";
        $llojiMesazhit = "error";
    } else {
        $stmt = $conn->prepare(
            "UPDATE books
             SET titulli = ?, autori = ?, kategoria = ?, sasia = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssii",
            $titulli,
            $autori,
            $kategoria,
            $sasia,
            $id
        );

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $mesazhi = "Libri u ndryshua me sukses!";
        } else {
            $mesazhi = "Gabim gjatë ndryshimit të librit.";
            $llojiMesazhit = "error";
        }

        $stmt->close();
    }
}

/* FSHI LIBËR - VETËM ADMINISTRATORI */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["fshij"])) {

    if (!$eshteAdmin) {
        http_response_code(403);
        exit("Vetëm administratori mund të fshijë libra.");
    }

    $id = (int)($_POST["id"] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare(
            "SELECT kopertina FROM books WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $libri = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($libri) {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM borrowings
                 WHERE book_id = ? AND statusi <> 'kthyer'"
            );

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $aktiv = (int)(
                $stmt->get_result()->fetch_assoc()["total"] ?? 0
            );

            $stmt->close();

            if ($aktiv > 0) {
                $mesazhi = "Ky libër ka huazime aktive dhe nuk mund të fshihet.";
                $llojiMesazhit = "error";
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM books WHERE id = ?"
                );

                $stmt->bind_param("i", $id);

                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    if (!empty($libri["kopertina"])) {
                        $rruga = __DIR__ . "/" . $libri["kopertina"];

                        if (is_file($rruga)) {
                            unlink($rruga);
                        }
                    }

                    $mesazhi = "Libri u fshi me sukses!";
                } else {
                    $mesazhi = "Libri nuk mund të fshihet.";
                    $llojiMesazhit = "error";
                }

                $stmt->close();
            }
        } else {
            $mesazhi = "Libri nuk u gjet.";
            $llojiMesazhit = "error";
        }
    }
}

/* KËRKIMI */
$kerko = trim($_GET["kerko"] ?? "");

if ($kerko !== "") {
    $like = "%" . $kerko . "%";

    $stmt = $conn->prepare(
        "SELECT id, titulli, autori, kategoria, sasia, kopertina
         FROM books
         WHERE titulli LIKE ?
         OR autori LIKE ?
         OR kategoria LIKE ?
         ORDER BY id DESC"
    );

    $stmt->bind_param("sss", $like, $like, $like);

} else {
    $stmt = $conn->prepare(
        "SELECT id, titulli, autori, kategoria, sasia, kopertina
         FROM books
         ORDER BY id DESC"
    );
}

$stmt->execute();
$librat = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="sq">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menaxho librat | Biblioteka Ime</title>

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

header {
    background: linear-gradient(120deg, #102442, #173f70);
    padding: 18px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    border-bottom: 4px solid #d8b66a;
}

.logo {
    color: white;
    font-size: 23px;
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
    color: #102442;
    background: #d8b66a;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: bold;
}

nav a:hover {
    background: #f0d58b;
}

.container {
    width: 92%;
    max-width: 1200px;
    margin: 35px auto;
}

.heading {
    margin-bottom: 25px;
}

.heading h1 {
    font-size: 29px;
    margin-bottom: 8px;
}

.heading p {
    color: #68778a;
}

.panel {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
    margin-bottom: 30px;
}

.panel h2 {
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field label {
    font-weight: bold;
    font-size: 14px;
}

.field input {
    width: 100%;
    padding: 12px;
    border: 1px solid #d5deea;
    border-radius: 8px;
    outline: none;
}

.field input:focus {
    border-color: #28649b;
}

.full {
    grid-column: 1 / -1;
}

.btn {
    display: inline-block;
    border: none;
    border-radius: 8px;
    padding: 11px 17px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    transition: .2s;
}

.btn-gold {
    background: #d8b66a;
    color: #102442;
}

.btn-gold:hover {
    background: #c6a04b;
}

.btn-blue {
    background: #173f70;
    color: white;
}

.btn-blue:hover {
    background: #28649b;
}

.btn-red {
    background: #c83e4d;
    color: white;
}

.btn-red:hover {
    background: #a92e3b;
}

.message {
    padding: 14px 17px;
    border-radius: 9px;
    margin-bottom: 22px;
    font-weight: bold;
}

.success {
    background: #e4f7eb;
    color: #176536;
}

.error {
    background: #fde8e8;
    color: #a32626;
}

.search {
    display: flex;
    gap: 10px;
    margin-bottom: 22px;
}

.search input {
    flex: 1;
    min-width: 0;
    padding: 12px;
    border: 1px solid #d5deea;
    border-radius: 8px;
}

.books {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 20px;
}

.book {
    background: white;
    border: 1px solid #e1e7ef;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 5px 17px rgba(0,0,0,.05);
}

.cover {
    height: 230px;
    background: #eaf0f7;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.cover img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.no-cover {
    color: #718096;
    font-size: 45px;
}

.book-info {
    padding: 18px;
}

.book-info h3 {
    margin-bottom: 9px;
    font-size: 18px;
}

.book-info p {
    color: #68778a;
    font-size: 14px;
    margin-bottom: 7px;
}

.book-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.book-actions form {
    margin: 0;
}

.empty {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    color: #68778a;
    grid-column: 1 / -1;
}

@media(max-width: 650px) {
    header {
        justify-content: center;
        text-align: center;
    }

    nav {
        justify-content: center;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .search {
        flex-direction: column;
    }

    .heading h1 {
        font-size: 24px;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">📚 Biblioteka <span>Ime</span></div>

    <nav>
        <a href="dashboard.php">Paneli</a>
        <a href="explorimilibrat.php">Eksploro librat</a>
        <a href="logout.php">Dil</a>
    </nav>
</header>

<main class="container">

    <div class="heading">
        <h1>📚 Menaxhimi i librave</h1>
        <p>
            Përshëndetje, <?= h($emri) ?>!
            Këtu mund t'i menaxhosh librat e bibliotekës.
        </p>
        <p>
            Roli: <?= $eshteAdmin ? "Administrator" : "Punëtor" ?>
        </p>
    </div>

    <?php if ($mesazhi !== ""): ?>
        <div class="message <?= $llojiMesazhit === "error" ? "error" : "success" ?>">
            <?= h($mesazhi) ?>
        </div>
    <?php endif; ?>

    <!-- SHTO LIBËR -->
    <section class="panel">
        <h2>➕ Shto libër të ri</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="field">
                    <label>Titulli i librit</label>
                    <input type="text" name="titulli" required maxlength="255">
                </div>

                <div class="field">
                    <label>Autori</label>
                    <input type="text" name="autori" required maxlength="255">
                </div>

                <div class="field">
                    <label>Kategoria</label>
                    <input type="text" name="kategoria" required maxlength="150">
                </div>

                <div class="field">
                    <label>Sasia</label>
                    <input type="number" name="sasia" min="0" value="1" required>
                </div>

                <div class="field full">
                    <label>Kopertina (JPG, PNG ose WEBP)</label>
                    <input type="file" name="kopertina" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div class="full">
                    <button type="submit" name="shto" class="btn btn-gold">
                        + Shto librin
                    </button>
                </div>

            </div>
        </form>
    </section>

    <!-- LISTA E LIBRAVE -->
    <section class="panel">
        <h2>📖 Librat e bibliotekës</h2>

        <form method="GET" class="search">
            <input
                type="text"
                name="kerko"
                placeholder="Kërko sipas titullit, autorit ose kategorisë..."
                value="<?= h($kerko) ?>"
            >

            <button type="submit" class="btn btn-blue">Kërko</button>
            <a href="book.php" class="btn btn-gold">Pastro</a>
        </form>

        <div class="books">

            <?php if ($librat->num_rows > 0): ?>

                <?php while ($libri = $librat->fetch_assoc()): ?>

                    <article class="book">

                        <div class="cover">
                            <?php
                            $kop = trim($libri["kopertina"] ?? "");
                            $kopPath = __DIR__ . "/" . $kop;
                            ?>

                            <?php if ($kop !== "" && is_file($kopPath)): ?>
                                <img src="<?= h($kop) ?>" alt="Kopertina e librit">
                            <?php else: ?>
                                <div class="no-cover">📚</div>
                            <?php endif; ?>
                        </div>

                        <div class="book-info">
                            <h3><?= h($libri["titulli"]) ?></h3>

                            <p>
                                <strong>Autori:</strong>
                                <?= h($libri["autori"]) ?>
                            </p>

                            <p>
                                <strong>Kategoria:</strong>
                                <?= h($libri["kategoria"]) ?>
                            </p>

                            <p>
                                <strong>Sasia:</strong>
                                <?= (int)$libri["sasia"] ?>
                            </p>

                            <div class="book-actions">

                                <button
                                    type="button"
                                    class="btn btn-blue"
                                    onclick='hapNdryshimin(
                                        <?= (int)$libri["id"] ?>,
                                        <?= json_encode($libri["titulli"], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                                        <?= json_encode($libri["autori"], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                                        <?= json_encode($libri["kategoria"], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                                        <?= (int)$libri["sasia"] ?>
                                    )'
                                >
                                    Ndrysho
                                </button>

                                <?php if ($eshteAdmin): ?>
                                    <form
                                        method="POST"
                                        onsubmit="return confirm('A je i sigurt që dëshiron ta fshish këtë libër?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$libri["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="fshij"
                                            class="btn btn-red"
                                        >
                                            Fshij
                                        </button>
                                    </form>
                                <?php endif; ?>

                            </div>
                        </div>

                    </article>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty">
                    Nuk u gjet asnjë libër.
                </div>

            <?php endif; ?>

        </div>
    </section>

</main>

<!-- MODALI PËR NDRYSHIM -->
<div id="editModal" style="
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    z-index:999;
    padding:20px;
    overflow:auto;
">
    <div style="
        background:white;
        max-width:550px;
        margin:40px auto;
        padding:25px;
        border-radius:15px;
    ">
        <h2 style="margin-bottom:20px;">✏️ Ndrysho librin</h2>

        <form method="POST">
            <input type="hidden" name="id" id="edit_id">

            <div class="field" style="margin-bottom:14px;">
                <label>Titulli</label>
                <input type="text" name="titulli" id="edit_titulli" required>
            </div>

            <div class="field" style="margin-bottom:14px;">
                <label>Autori</label>
                <input type="text" name="autori" id="edit_autori" required>
            </div>

            <div class="field" style="margin-bottom:14px;">
                <label>Kategoria</label>
                <input type="text" name="kategoria" id="edit_kategoria" required>
            </div>

            <div class="field" style="margin-bottom:20px;">
                <label>Sasia</label>
                <input type="number" name="sasia" id="edit_sasia" min="0" required>
            </div>

            <button type="submit" name="ndrysho" class="btn btn-gold">
                Ruaj ndryshimet
            </button>

            <button
                type="button"
                class="btn btn-red"
                onclick="mbyllNdryshimin()"
            >
                Mbyll
            </button>
        </form>
    </div>
</div>

<script>
function hapNdryshimin(id, titulli, autori, kategoria, sasia) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_titulli").value = titulli;
    document.getElementById("edit_autori").value = autori;
    document.getElementById("edit_kategoria").value = kategoria;
    document.getElementById("edit_sasia").value = sasia;

    document.getElementById("editModal").style.display = "block";
}

function mbyllNdryshimin() {
    document.getElementById("editModal").style.display = "none";
}

window.addEventListener("click", function(event) {
    const modal = document.getElementById("editModal");

    if (event.target === modal) {
        mbyllNdryshimin();
    }
});
</script>

</body>
</html>

<?php
$stmt->close();
?>
