
<?php
session_start();
require_once "db.php";

$mesazhi = "";

if (isset($_GET["regjistrimi"]) && $_GET["regjistrimi"] === "sukses") {
    $mesazhi = "Regjistrimi u krye me sukses. Tash mund të hysh.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, emri, username, password, roli
         FROM users WHERE username = ? OR email = ?"
    );
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $rezultati = $stmt->get_result();
    $user = $rezultati->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["emri"] = $user["emri"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["roli"] = $user["roli"];

        header("Location: dashboard.php");
        exit;
    } else {
        $mesazhi = "Emri i përdoruesit/email ose fjalëkalimi është gabim.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hyr - Biblioteka</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h2>📚 Biblioteka Ime</h2>
    <nav>
        <a href="index.php">Ballina</a>
        <a href="register.php">Regjistrohu</a>
    </nav>
</header>

<main class="auth-container">
    <form class="auth-card" method="POST" action="login.php">
        <h1>Mirë se u ktheve!</h1>
        <p>Hyr në llogarinë tënde.</p>

        <?php if ($mesazhi !== ""): ?>
            <p class="message"><?php echo htmlspecialchars($mesazhi); ?></p>
        <?php endif; ?>

        <label for="username">Emri i përdoruesit ose email</label>
        <input id="username" type="text" name="username" required>

        <label for="password">Fjalëkalimi</label>
        <input id="password" type="password" name="password" required>

        <button type="submit" class="btn">Hyr</button>

        <p class="auth-link">Nuk ke llogari?
            <a href="register.php">Regjistrohu këtu</a>
        </p>
    </form>
</main>
</body>
</html>
