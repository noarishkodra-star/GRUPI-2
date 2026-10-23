<?php
require_once "db.php";

$mesazhi = "";
$emri = "";
$username = "";
$email = "";
$roli = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $emri = trim($_POST["emri"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $roli = $_POST["roli"] ?? "";

    if (
        $emri === "" ||
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $roli === ""
    ) {
        $mesazhi = "Plotëso të gjitha fushat.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mesazhi = "Shkruaj një email të vlefshëm.";
    } elseif (strlen($password) < 8) {
        $mesazhi = "Fjalëkalimi duhet t'i ketë së paku 8 karaktere.";
    } elseif (!in_array($roli, ["nxenes", "puntor/e"], true)) {
        $mesazhi = "Roli i zgjedhur nuk lejohet.";
    } else {
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

<header>
    <h2>📚 Biblioteka Ime</h2>

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
            <p class="message">
                <?= htmlspecialchars($mesazhi, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <label for="emri">Emri i plotë</label>
        <input
            type="text"
            id="emri"
            name="emri"
            value="<?= htmlspecialchars($emri, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="100"
            required
        >

        <label for="username">Emri i përdoruesit</label>
        <input
            type="text"
            id="username"
            name="username"
            value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="50"
            required
        >

        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            maxlength="100"
            required
        >

        <label for="roli">Regjistrohu si</label>
        <select id="roli" name="roli" required>
            <option value="">-- Zgjedh rolin --</option>

            <option value="nxenes"
                <?= $roli === "nxenes" ? "selected" : "" ?>>
                Nxënës
            </option>

            <option value="puntor/e"
                <?= $roli === "puntor/e" ? "selected" : "" ?>>
                Punëtor/e
            </option>
        </select>

        <label for="password">Fjalëkalimi</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <button type="submit" class="btn">Regjistrohu</button>

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

