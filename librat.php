
<?php
require_once "db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$kerko = trim($_GET["q"] ?? "");

if ($kerko !== "") {
    $stmt = $conn->prepare(
        "SELECT id, titulli, autori, kategoria, sasia
         FROM books
         WHERE titulli LIKE ?
            OR autori LIKE ?
            OR kategoria LIKE ?
         ORDER BY titulli"
    );

    $search = "%" . $kerko . "%";
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $books = $conn->query(
        "SELECT id, titulli, autori, kategoria, sasia
         FROM books ORDER BY titulli"
    );
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Librat | Biblioteka Ime</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="site-header">
    <a href="index.php" class="brand">
        📚 Biblioteka Ime
    </a>

    <nav>
        <a href="index.php">Ballina</a>
        <a href="librat.php">Librat</a>
        <a href="login.php">Kyçu</a>
        <a href="register.php">Regjistrohu</a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <h1>Koleksioni ynë i librave 📚</h1>

        <p>
            Zbulo librat tanë dhe gjej librin që po kërkon.
            Shfleto sipas titullit, autorit ose kategorisë.
        </p>
    </section>

    <section class="about">

        <h2>🔎 Kërko librin tënd</h2>

        <form method="GET" action="librat.php">

            <input
                type="text"
                name="q"
                placeholder="Titulli, autori ose kategoria..."
                value="<?= e($kerko) ?>"
            >

            <button type="submit" class="btn">
                Kërko libra
            </button>

            <a href="librat.php" class="button">
                Shfaq të gjithë
            </a>

        </form>

    </section>

    <h2>📖 Librat në bibliotekë</h2>

    <div class="cards">

        <?php if ($books->num_rows > 0): ?>

            <?php while ($book = $books->fetch_assoc()): ?>

                <div class="card">

                    <h3><?= e($book["titulli"]) ?></h3>

                    <p>
                        <strong>Autori:</strong>
                        <?= e($book["autori"]) ?>
                    </p>

                    <p>
                        <strong>Kategoria:</strong>
                        <?= e($book["kategoria"]) ?>
                    </p>

                    <p>
                        <strong>Kopjet:</strong>
                        <?= (int)$book["sasia"] ?>
                    </p>

                    <?php if ((int)$book["sasia"] > 0): ?>
                        <span class="badge success">
                            Në bibliotekë
                        </span>
                    <?php else: ?>
                        <span class="badge danger">
                            Nuk ka kopje të lira
                        </span>
                    <?php endif; ?>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="card">
                <h3>Nuk u gjet asnjë libër!</h3>
                <p>Provo të kërkosh me një emër tjetër.</p>
            </div>

        <?php endif; ?>

    </div>

</main>

<footer>
    <p>© 2026 Biblioteka Ime | Të gjithë të drejtat e rezervuara.</p>
</footer>

</body>
</html>
