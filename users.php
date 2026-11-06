<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

// Vetëm administratori
if (
    !isset($_SESSION["user_id"]) ||
    strtolower(trim($_SESSION["roli"] ?? "")) !== "administrator"
) {
    http_response_code(403);
    exit("Nuk ke leje për ta hapur këtë faqe.");
}

// CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mesazhi = "";
$gabim = "";

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

// Normalizimi i roleve të vjetra
function normalizoRolin($roli) {
    $roli = strtolower(trim((string)$roli));

    if (in_array($roli, ["nxenes", "nxenes/e", "nxënës", "nxënës/e"], true)) {
        return "nxenes/e";
    }

    if (in_array($roli, ["punetor", "punetor/e", "puntor", "puntor/e", "punëtor", "punëtor/e"], true)) {
        return "punetor/e";
    }

    if ($roli === "administrator") {
        return "administrator";
    }

    return $roli;
}

// Ndryshimi i rolit
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $gabim = "Kërkesa nuk është e vlefshme. Rifresko faqen.";

    } else {

        $user_id = (int)($_POST["user_id"] ?? 0);
        $roli = $_POST["roli"] ?? "";

        $roletLejuara = ["nxenes/e", "punetor/e", "administrator"];

        if ($user_id <= 0) {
            $gabim = "Përdoruesi nuk është i vlefshëm.";

        } elseif ($user_id === (int)$_SESSION["user_id"]) {
            $gabim = "Nuk mund ta ndryshosh rolin e llogarisë sate.";

        } elseif (!in_array($roli, $roletLejuara, true)) {
            $gabim = "Roli i zgjedhur nuk është i vlefshëm.";

        } else {

            try {
                $kontrollo = $conn->prepare(
                    "SELECT id FROM users WHERE id = ?"
                );

                $kontrollo->bind_param("i", $user_id);
                $kontrollo->execute();

                $ekziston = $kontrollo->get_result()->num_rows > 0;
                $kontrollo->close();

                if (!$ekziston) {
                    throw new Exception("Përdoruesi nuk u gjet.");
                }

                $stmt = $conn->prepare(
                    "UPDATE users SET roli = ? WHERE id = ?"
                );

                $stmt->bind_param("si", $roli, $user_id);
                $stmt->execute();
                $stmt->close();

                $mesazhi = "Roli u ruajt me sukses.";

            } catch (Throwable $e) {
                $gabim = "Gabim gjatë ndryshimit të rolit. Kontrollo databazën.";
            }
        }
    }
}

// Merr përdoruesit
$perdoruesit = [];

try {
    $rezultati = $conn->query(
        "SELECT id, emri, username, email, roli
         FROM users
         ORDER BY id ASC"
    );

    while ($user = $rezultati->fetch_assoc()) {
        $user["roli_normalizuar"] = normalizoRolin($user["roli"]);
        $perdoruesit[] = $user;
    }

} catch (Throwable $e) {
    exit("Gabim gjatë marrjes së përdoruesve nga databaza.");
}

// Statistikat
$numriNxenesve = 0;
$numriPunetoreve = 0;
$numriAdministratoreve = 0;

foreach ($perdoruesit as $user) {
    if ($user["roli_normalizuar"] === "nxenes/e") {
        $numriNxenesve++;
    } elseif ($user["roli_normalizuar"] === "punetor/e") {
        $numriPunetoreve++;
    } elseif ($user["roli_normalizuar"] === "administrator") {
        $numriAdministratoreve++;
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Menaxhimi i përdoruesve | Biblioteka Ime</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f1f5fa;
            color: #24344d;
        }

        .main-header {
            background: linear-gradient(110deg, #102b50, #173f78);
            min-height: 85px;
            padding: 15px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 20px rgba(10, 35, 70, .15);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border: 2px solid #d6b76b;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 25px;
        }

        .brand h2 {
            font-size: 21px;
        }

        .header-nav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 11px 17px;
            border-radius: 8px;
        }

        .header-nav a:hover {
            background: rgba(255,255,255,.12);
            color: #e8cd8b;
        }

        .header-nav .logout {
            background: #d6b76b;
            color: #173b70;
        }

        .users-page {
            width: 92%;
            max-width: 1250px;
            margin: 40px auto;
        }

        .page-heading {
            margin-bottom: 28px;
        }

        .page-heading h1 {
            color: #173b70;
            font-size: 30px;
            margin-bottom: 9px;
        }

        .page-heading p {
            color: #718096;
            line-height: 1.6;
        }

        .heading-line {
            width: 65px;
            height: 4px;
            background: #d6b76b;
            border-radius: 5px;
            margin-top: 15px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 23px;
            border-radius: 14px;
            border: 1px solid #e5eaf1;
            box-shadow: 0 5px 20px rgba(20,50,90,.04);
        }

        .stat-icon {
            font-size: 25px;
            margin-bottom: 14px;
        }

        .stat-card span {
            display: block;
            color: #718096;
            font-size: 13px;
            margin-bottom: 9px;
        }

        .stat-card strong {
            color: #173b70;
            font-size: 29px;
        }

        .notice {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-weight: 600;
        }

        .success {
            background: #e4f6eb;
            color: #19723b;
        }

        .error {
            background: #fff0ef;
            color: #b83232;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(20,50,90,.06);
            border: 1px solid #e5eaf1;
        }

        .table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }

        .table-heading h2 {
            color: #173b70;
            font-size: 20px;
        }

        .user-count {
            background: #eaf1fc;
            color: #24518b;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #e7ecf3;
            border-radius: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        thead {
            background: #173b70;
            color: white;
        }

        th, td {
            padding: 16px 15px;
            text-align: left;
            border-bottom: 1px solid #edf0f5;
            font-size: 14px;
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
        }

        tbody tr:hover {
            background: #f3f7fd;
        }

        .user-id {
            color: #8290a4;
            font-weight: bold;
        }

        .user-name {
            color: #173b70;
            font-weight: 700;
        }

        .user-email {
            color: #718096;
        }

        .role-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .admin {
            background: #eee9ff;
            color: #6941a5;
        }

        .worker {
            background: #fff0db;
            color: #a56816;
        }

        .student {
            background: #e1efff;
            color: #245ca0;
        }

        .other {
            background: #eeeeee;
            color: #555555;
        }

        .role-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .role-form select {
            min-width: 135px;
            padding: 10px 12px;
            border: 1px solid #d9e2ee;
            border-radius: 8px;
            background: #f8faff;
            color: #24466e;
            font-size: 13px;
        }

        .save-btn {
            padding: 10px 17px;
            border: none;
            border-radius: 8px;
            background: #173b70;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: .3s;
        }

        .save-btn:hover {
            background: #d6b76b;
            color: #173b70;
        }

        .own-account {
            background: #f0f2f5;
            color: #68778b;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 12px;
        }

        .empty-message {
            padding: 35px;
            text-align: center;
            color: #718096;
        }

        footer {
            margin-top: 55px;
            padding: 25px 15px;
            background: #102b50;
            color: white;
            text-align: center;
            font-size: 13px;
        }

        @media(max-width: 950px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 600px) {
            .main-header {
                padding: 15px 5%;
            }

            .users-page {
                width: 94%;
                margin: 25px auto;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .stats {
                gap: 10px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-card strong {
                font-size: 24px;
            }

            .table-card {
                padding: 15px 10px;
            }
        }
    </style>
</head>

<body>

<header class="main-header">
    <a href="index.php" class="brand">
        <div class="brand-icon">📚</div>
        <h2>Biblioteka Ime</h2>
    </a>

    <nav class="header-nav">
        <a href="dashboard.php">Paneli</a>
        <a href="index.php">Ballina</a>
        <a href="logout.php" class="logout">Dil ↗</a>
    </nav>
</header>

<main class="users-page">

    <section class="page-heading">
        <h1>Menaxhimi i përdoruesve</h1>
        <p>Menaxho llogaritë dhe rolet e përdoruesve të bibliotekës.</p>
        <div class="heading-line"></div>
    </section>

    <?php if ($mesazhi !== ""): ?>
        <div class="notice success">✅ <?= h($mesazhi) ?></div>
    <?php endif; ?>

    <?php if ($gabim !== ""): ?>
        <div class="notice error">⚠️ <?= h($gabim) ?></div>
    <?php endif; ?>

    <section class="stats">

        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <span>Gjithsej përdorues</span>
            <strong><?= count($perdoruesit) ?></strong>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <span>Nxënës</span>
            <strong><?= $numriNxenesve ?></strong>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👨‍💼</div>
            <span>Punëtorë</span>
            <strong><?= $numriPunetoreve ?></strong>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔐</div>
            <span>Administratorë</span>
            <strong><?= $numriAdministratoreve ?></strong>
        </div>

    </section>

    <section class="table-card">

        <div class="table-heading">
            <h2>📋 Lista e përdoruesve</h2>
            <span class="user-count">
                <?= count($perdoruesit) ?> përdorues
            </span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Emri</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Roli aktual</th>
                        <th>Ndrysho rolin</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($perdoruesit) > 0): ?>

                    <?php foreach ($perdoruesit as $user): ?>

                        <?php
                        $roliAktual = $user["roli_normalizuar"];

                        $klasa = "other";
                        $etiketa = $user["roli"];

                        if ($roliAktual === "administrator") {
                            $klasa = "admin";
                            $etiketa = "Administrator";
                        } elseif ($roliAktual === "punetor/e") {
                            $klasa = "worker";
                            $etiketa = "Punëtor/e";
                        } elseif ($roliAktual === "nxenes/e") {
                            $klasa = "student";
                            $etiketa = "Nxënës";
                        }
                        ?>

                        <tr>
                            <td class="user-id">#<?= (int)$user["id"] ?></td>

                            <td class="user-name">
                                <?= h($user["emri"]) ?>
                            </td>

                            <td><?= h($user["username"]) ?></td>

                            <td class="user-email">
                                <?= h($user["email"]) ?>
                            </td>

                            <td>
                                <span class="role-badge <?= h($klasa) ?>">
                                    <?= h($etiketa) ?>
                                </span>
                            </td>

                            <td>

                                <?php if ((int)$user["id"] === (int)$_SESSION["user_id"]): ?>

                                    <span class="own-account">
                                        🔒 Llogaria jote
                                    </span>

                                <?php else: ?>

                                    <form method="POST" action="users.php" class="role-form">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= h($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="user_id"
                                               value="<?= (int)$user["id"] ?>">

                                        <select name="roli" required>
                                            <option value="nxenes/e"
                                                <?= $roliAktual === "nxenes/e" ? "selected" : "" ?>>
                                                Nxënës
                                            </option>

                                            <option value="punetor/e"
                                                <?= $roliAktual === "punetor/e" ? "selected" : "" ?>>
                                                Punëtor/e
                                            </option>

                                            <option value="administrator"
                                                <?= $roliAktual === "administrator" ? "selected" : "" ?>>
                                                Administrator
                                            </option>
                                        </select>

                                        <button type="submit" class="save-btn">
                                            Ruaj
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty-message">
                            Nuk ka përdorues për t'u shfaqur.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

</main>

<footer>
    <p>© <?= date("Y") ?> Biblioteka Ime | Paneli i Administrimit</p>
</footer>

</body>
</html>
