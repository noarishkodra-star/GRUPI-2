<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$emri = $_SESSION["emri"] ?? "Përdorues";
$roli = $_SESSION["roli"] ?? "";

if (!in_array($roli, ["administrator", "punetor", "nxenes"], true)) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$rolet = [
    "administrator" => "Administratori",
    "punetor" => "Punëtori",
    "nxenes" => "Nxënësi"
];

$titulli = $rolet[$roli];
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paneli - Biblioteka</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h2>📚 Biblioteka Ime</h2>
    <nav>
        <a href="index.php">Ballina</a>
        <a href="logout.php">Dil nga llogaria</a>
    </nav>
</header>

<main class="auth-container">
    <section class="auth-card">
        <h1>Mirë se erdhe, <?= htmlspecialchars($emri) ?>!</h1>
        <p>Roli yt: <strong><?= htmlspecialchars($titulli) ?></strong></p>

        <?php if ($roli === "administrator"): ?>
            <h2>Paneli i administratorit</h2>
            <p>Menaxho përdoruesit, librat dhe huazimet.</p>
            <p><a href="users.php">Menaxho përdoruesit</a></p>
            <p><a href="books.php">Menaxho librat</a></p>
            <p><a href="borrow.php">Menaxho huazimet</a></p>

        <?php elseif ($roli === "punetor"): ?>
            <h2>Paneli i punëtorit</h2>
            <p>Menaxho librat dhe huazimet e bibliotekës.</p>
            <p><a href="books.php">Menaxho librat</a></p>
            <p><a href="borrow.php">Menaxho huazimet</a></p>

        <?php else: ?>
            <h2>Paneli i nxënësit</h2>
            <p>Shiko librat dhe huazimet e tua.</p>
            <p><a href="books.php">Shiko librat</a></p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>