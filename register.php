<?php
require_once "db.php";

$mesazhi = "";
$emri = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $emri = trim($_POST["emri"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // Çdo regjistrim i ri ruhet si nxënës
    $roli = "nxenes/e";

    if (
        $emri === "" ||
        $username === "" ||
        $email === "" ||
        $password === ""
    ) {
        $mesazhi = "Plotëso të gjitha fushat.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mesazhi = "Shkruaj një email të vlefshëm.";

    } elseif (strlen($password) < 8) {
        $mesazhi = "Fjalëkalimi duhet t'i ketë së paku 8 karaktere.";

    } else {

        try {
            $kontrollo = $conn->prepare(
                "SELECT id FROM users WHERE username = ? OR email = ?"
            );

            $kontrollo->bind_param("ss", $username, $email);
            $kontrollo->execute();

            $rezultati = $kontrollo->get_result();

            if ($rezultati->num_rows > 0) {

                $mesazhi = "Ky username ose email ekziston.";

            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $shto = $conn->prepare(
                    "INSERT INTO users
                    (emri, username, email, password, roli)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $shto->bind_param(
                    "sssss",
                    $emri,
                    $username,
                    $email,
                    $hash,
                    $roli
                );

                if ($shto->execute()) {

                    $shto->close();
                    $kontrollo->close();

                    header("Location: login.php?regjistrimi=sukses");
                    exit;

                } else {
                    $mesazhi = "Regjistrimi dështoi. Provo përsëri.";
                }

                $shto->close();
            }

            $kontrollo->close();

        } catch (Throwable $e) {
            $mesazhi = "Ndodhi një gabim gjatë regjistrimit. Kontrollo databazën.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regjistrohu - Biblioteka Ime</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="topbar">
    <a href="index.php" class="brand">
        <img src="images/logo.png" alt="Biblioteka Ime" class="site-logo">
        <span>Biblioteka Ime</span>
    </a>

    <nav>
        <a href="index.php">Ballina</a>
        <a href="login.php">Hyr</a>
    </nav>
</header>

<main class="auth-container">

    <form class="auth-card" method="POST" action="register.php">

        <h1>Krijo llogari</h1>
        <p>Regjistrohu në sistemin e bibliotekës.</p>

        <?php if ($mesazhi !== ""): ?>
            <div class="message danger">
                <?= htmlspecialchars($mesazhi, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <label for="emri">Emri i plotë</label>
        <input
            type="text"
            id="emri"
            name="emri"
            placeholder="Shkruaj emrin e plotë"
            value="<?= htmlspecialchars($emri, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="100"
            autocomplete="name"
            required
        >

        <label for="username">Emri i përdoruesit</label>
        <input
            type="text"
            id="username"
            name="username"
            placeholder="Zgjedh username"
            value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="50"
            autocomplete="username"
            required
        >

        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Shkruaj email-in"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="100"
            autocomplete="email"
            required
        >

        <label>Roli</label>
        <input type="text" value="Nxënës" disabled>

        <label for="password">Fjalëkalimi</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Minimum 8 karaktere"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <button type="submit" class="btn">
            Regjistrohu
        </button>

        <p class="auth-link">
            Ke llogari?
            <a href="login.php">Hyr këtu</a>
        </p>

    </form>

</main>

<footer>
    <p>© 2026 Biblioteka Ime</p>
</footer>

</body>
</html>
