
<?php
session_start();
require_once "db.php";

$mesazhi = "";
$klasaMesazhit = "";

if (isset($_GET["regjistrimi"]) && $_GET["regjistrimi"] === "sukses") {
    $mesazhi = "Regjistrimi u krye me sukses! Tani mund të kyçesh.";
    $klasaMesazhit = "success";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, emri, username, password, roli
         FROM users
         WHERE username = ? OR email = ?
         LIMIT 1"
    );

    if ($stmt) {
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

            $stmt->close();

            header("Location: index.php");
            exit;

        } else {
            $mesazhi = "Të dhënat e kyçjes nuk janë të sakta.";
            $klasaMesazhit = "error";
        }

        $stmt->close();
    } else {
        $mesazhi = "Ndodhi një gabim. Provo përsëri.";
        $klasaMesazhit = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="sq">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kyçu | Biblioteka Ime</title>

    <link rel="stylesheet" href="style.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #102b50;
        }

        .login-page {
            min-height: 100vh;

            background:
                linear-gradient(
                    120deg,
                    rgba(8, 26, 53, 0.92),
                    rgba(18, 59, 112, 0.72)
                ),
                url('images/biblioteka.jpg');

            background-size: cover;
            background-position: center;

            display: flex;
            flex-direction: column;
        }

        /* HEADER */

        .login-header {
            width: 100%;
            padding: 20px 7%;

            display: flex;
            justify-content: space-between;
            align-items: center;

            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;

            text-decoration: none;
            color: white;
        }

        .brand img {
            width: 52px;
            height: 52px;

            object-fit: cover;
            border-radius: 50%;

            border: 2px solid #d6b76b;
        }

        .brand h2 {
            font-size: 21px;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .header-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .header-links a {
            color: white;
            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            transition: 0.3s;
        }

        .header-links a:hover {
            color: #e3c77f;
        }

        .header-links .register-btn {
            background: #d6b76b;
            color: #173b70;

            padding: 12px 23px;
            border-radius: 30px;
        }

        .header-links .register-btn:hover {
            background: white;
            color: #173b70;
        }

        /* MAIN */

        .login-main {
            flex: 1;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 45px 20px;
        }

        /* LOGIN CARD */

        .login-card {
            width: 100%;
            max-width: 440px;

            background: #ffffff;

            padding: 42px;

            border-radius: 20px;

            box-shadow: 0 25px 70px rgba(0,0,0,0.35);

            animation: appear 0.7s ease;
        }

        .book-icon {
            width: 65px;
            height: 65px;

            margin: 0 auto 20px;

            background: #f5f0e3;
            border: 1px solid #e6d5a9;

            border-radius: 18px;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 32px;
        }

        .login-card h1 {
            text-align: center;

            color: #173b70;

            font-size: 29px;
            margin: 0 0 9px;
        }

        .subtitle {
            text-align: center;

            color: #7c887f;

            font-size: 14px;
            margin: 0 0 30px;
        }

        /* FORM */

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;

            color: #254b7c;

            font-weight: 600;
            font-size: 14px;

            margin-bottom: 9px;
        }

        .form-group input {
            width: 100%;
            height: 51px;

            padding: 0 15px;

            border: 1px solid #dce4de;
            background: #f9fbf9;

            border-radius: 10px;

            outline: none;

            font-size: 14px;
            color: #173b70;

            transition: 0.25s;
        }

        .form-group input:focus {
            border-color: #b99a53;
            background: white;

            box-shadow: 0 0 0 4px rgba(214,183,107,0.16);
        }

        /* BUTTON */

        .login-button {
            width: 100%;
            height: 52px;

            border: none;
            border-radius: 10px;

            background: #173b70;
            color: #ffffff;

            font-size: 16px;
            font-weight: 700;

            cursor: pointer;

            transition: 0.3s;
            margin-top: 5px;
        }

        .login-button:hover {
            background: #d6b76b;
            color: #173b70;

            transform: translateY(-2px);

            box-shadow: 0 8px 20px rgba(214,183,107,0.25);
        }

        /* MESSAGES */

        .message {
            padding: 13px;

            border-radius: 9px;

            font-size: 13px;
            text-align: center;

            margin-bottom: 22px;
        }

        .success {
            background: #e6f5e9;
            color: #26713b;
            border: 1px solid #c5e8cc;
        }

        .error {
            background: #fff0ef;
            color: #bd3434;
            border: 1px solid #f2cdca;
        }

        /* BOTTOM */

        .register-text {
            text-align: center;

            color: #78847c;
            font-size: 14px;

            margin: 25px 0 0;
        }

        .register-text a {
            color: #a18138;

            font-weight: 700;
            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        /* FOOTER */

        .login-footer {
            text-align: center;

            padding: 18px;

            color: rgba(255,255,255,0.65);
            font-size: 12px;
        }

        /* ANIMATION */

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* MOBILE */

        @media(max-width: 600px) {

            .login-header {
                padding: 15px 5%;
            }

            .brand h2 {
                font-size: 16px;
            }

            .brand img {
                width: 42px;
                height: 42px;
            }

            .header-links {
                gap: 12px;
            }

            .header-links a {
                font-size: 12px;
            }

            .header-links .register-btn {
                padding: 10px 13px;
            }

            .login-card {
                padding: 30px 23px;
            }

            .login-card h1 {
                font-size: 25px;
            }
        }

    </style>
</head>

<body>

<div class="login-page">

    <header class="login-header">

        <a href="index.php" class="brand">

            <img src="images/logo.png" alt="Biblioteka Ime">

            <h2>Biblioteka Ime</h2>

        </a>

        <nav class="header-links">

            <a href="index.php">Ballina</a>

            <a href="register.php" class="register-btn">
                Regjistrohu
            </a>

        </nav>

    </header>

    <main class="login-main">

        <form class="login-card" method="POST" action="login.php">

            <div class="book-icon">📚</div>

            <h1>Mirë se u ktheve!</h1>

            <p class="subtitle">
                Kyçu në llogarinë tënde dhe vazhdo udhëtimin në botën e librave.
            </p>

            <?php if ($mesazhi !== ""): ?>

                <div class="message <?php echo htmlspecialchars($klasaMesazhit); ?>">

                    <?php echo htmlspecialchars($mesazhi); ?>

                </div>

            <?php endif; ?>

            <div class="form-group">

                <label for="username">
                    Emri i përdoruesit ose email
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Shkruaj emrin ose email-in"
                    autocomplete="username"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Fjalëkalimi
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Shkruaj fjalëkalimin"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button type="submit" class="login-button">
                Kyçu në llogari →
            </button>

            <p class="register-text">

                Nuk ke ende llogari?

                <a href="register.php">
                    Regjistrohu këtu
                </a>

            </p>

        </form>

    </main>

    <footer class="login-footer">

        © <?php echo date("Y"); ?> Biblioteka Ime.
        Të gjitha të drejtat e rezervuara.

    </footer>

</div>

</body>
</html>
