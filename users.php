<?php
session_start();
require_once "db.php";

// Vetem administratori mund ta hape kete faqe
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["roli"] ?? "") !== "administrator"
) {
    http_response_code(403);
    exit("Nuk ke leje per ta hapur kete faqe.");
}

// Token per sigurine e formularit
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mesazhi = "";
$gabim = "";

$rolet_lejuara = ["nxenes/e", "punetor/e", "administrator"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $gabim = "Kerkesa nuk eshte e vlefshme. Rifresko faqen.";
    } else {
        $user_id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
        $roli = $_POST["roli"] ?? "";

        if (!$user_id || $user_id < 1) {
            $gabim = "Perdoruesi nuk eshte i vlefshem.";
        } elseif ($user_id === (int) $_SESSION["user_id"]) {
            $gabim = "Nuk mund ta ndryshosh rolin e llogarise sate.";
        } elseif (!in_array($roli, $rolet_lejuara, true)) {
            $gabim = "Roli i zgjedhur nuk eshte i vlefshem.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET roli = ? WHERE id = ?"
            );

            $stmt->bind_param("si", $roli, $user_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $mesazhi = "Roli u ndryshua me sukses.";
                } else {
                    $kontrollo = $conn->prepare(
                        "SELECT id, roli FROM users WHERE id = ?"
                    );

                    $kontrollo->bind_param("i", $user_id);
                    $kontrollo->execute();

                    $perdoruesi = $kontrollo
                        ->get_result()
                        ->fetch_assoc();

                    if ($perdoruesi && $perdoruesi["roli"] === $roli) {
                        $mesazhi = "Perdoruesi tashme e ka kete rol.";
                    } else {
                        $gabim = "Perdoruesi nuk u gjet ose ndryshimi deshtoi.";
                    }

                    $kontrollo->close();
                }
            } else {
                $gabim = "Ndodhi nje gabim gjate ndryshimit te rolit.";
            }

            $stmt->close();
        }
    }
}

// Merr perdoruesit nga databaza
$rezultati = $conn->query(
    "SELECT id, emri, username, email, roli
     FROM users
     ORDER BY id ASC"
);

if (!$rezultati) {
    exit("Gabim gjate marrjes se perdoruesve.");
}

function h($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Menaxhimi i perdoruesve | Biblioteka Ime</title>

    <link rel="stylesheet" href="style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #1e293b;
            font-family: Arial, Helvetica, sans-serif;
        }

        header {
            background: #102b4e;
            color: white;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }

        header h2 {
            margin: 0;
            font-size: 23px;
        }

        header nav {
            display: flex;
            gap: 22px;
            flex-wrap: wrap;
        }

        header nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        header nav a:hover {
            color: #93c5fd;
        }

        .users-page {
            max-width: 1250px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.07);
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0 0 12px;
            color: #102b4e;
            font-size: 30px;
        }

        .page-heading p {
            margin: 0;
            color: #64748b;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin: 25px 0;
        }

        .stat-card {
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .stat-card span {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .stat-card strong {
            color: #102b4e;
            font-size: 27px;
        }

        .notice {
            padding: 15px 18px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: bold;
            line-height: 1.5;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border-left: 5px solid #22c55e;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 5px solid #ef4444;
        }

        .table-title {
            margin: 30px 0 15px;
            color: #102b4e;
            font-size: 20px;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: #102b4e;
            color: white;
        }

        th {
            padding: 16px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        td {
            padding: 15px 16px;
            border-bottom: 1px solid #e9eef5;
            font-size: 14px;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f0f7ff;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .user-id {
            color: #64748b;
            font-weight: bold;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .role-badge.admin {
            background: #ede9fe;
            color: #6d28d9;
        }

        .role-badge.worker {
            background: #ffedd5;
            color: #9a3412;
        }

        .role-badge.student {
            background: #dcfce7;
            color: #166534;
        }

        .role-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .role-form select {
            min-width: 130px;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            outline: none;
        }

        .role-form select:focus {
            border-color: #2563eb;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .own-account {
            color: #64748b;
            font-size: 13px;
            font-weight: bold;
        }

        .empty-message {
            padding: 30px;
            text-align: center;
            color: #64748b;
        }

        footer {
            margin-top: 50px;
            padding: 25px;
            background: #102b4e;
            color: white;
            text-align: center;
        }

        footer p {
            margin: 0;
        }

        @media (max-width: 700px) {
            header {
                padding: 18px;
            }

            header nav {
                gap: 14px;
            }

            .users-page {
                margin: 20px 10px;
                padding: 18px 12px;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            th,
            td {
                padding: 12px 10px;
                font-size: 12px;
            }

            .role-form select {
                min-width: 110px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>📚 Biblioteka Ime</h2>

    <nav>
        <a href="dashboard.php">Paneli</a>
        <a href="index.php">Ballina</a>
        <a href="logout.php">Dil</a>
    </nav>
</header>

<main class="users-page">

    <div class="page-heading">
        <h1>👥 Menaxhimi i perdoruesve</h1>
        <p>
            Mire se erdhe ne panelin e administrimit.
            Ketu mund t'i shikosh perdoruesit dhe t'ua ndryshosh rolet.
        </p>
    </div>

    <?php if ($mesazhi !== ""): ?>
        <div class="notice success">
            ✅ <?= h($mesazhi) ?>
        </div>
    <?php endif; ?>

    <?php if ($gabim !== ""): ?>
        <div class="notice error">
            ⚠️ <?= h($gabim) ?>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card">
            <span>👥 Gjithsej perdorues</span>
            <strong><?= (int)$rezultati->num_rows ?></strong>
        </div>

        <div class="stat-card">
            <span>🔐 Llogaria jote</span>
            <strong>Administrator</strong>
        </div>
    </div>

    <h2 class="table-title">📋 Lista e perdoruesve</h2>

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
            <?php if ($rezultati->num_rows > 0): ?>

                <?php while ($user = $rezultati->fetch_assoc()): ?>
                    <?php
                    $klasa_roli = "";

                    if ($user["roli"] === "administrator") {
                        $klasa_roli = "admin";
                    } elseif ($user["roli"] === "punetor/e") {
                        $klasa_roli = "worker";
                    } elseif ($user["roli"] === "nxenes/e") {
                        $klasa_roli = "student";
                    }
                    ?>

                    <tr>
                        <td class="user-id">
                            #<?= h($user["id"]) ?>
                        </td>

                        <td>
                            <strong><?= h($user["emri"]) ?></strong>
                        </td>

                        <td><?= h($user["username"]) ?></td>

                        <td><?= h($user["email"]) ?></td>

                        <td>
                            <span class="role-badge <?= h($klasa_roli) ?>">
                                <?= h($user["roli"]) ?>
                            </span>
                        </td>

                        <td>
                            <?php if ((int)$user["id"] === (int)$_SESSION["user_id"]): ?>

                                <span class="own-account">
                                    🔒 Llogaria jote
                                </span>

                            <?php else: ?>

                                <form method="POST" action="users.php" class="role-form">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h($_SESSION["csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= h($user["id"]) ?>"
                                    >

                                    <select name="roli" required>
                                        <option value="nxenes/e"
                                            <?= $user["roli"] === "nxenes/e" ? "selected" : "" ?>>
                                            Nxenes/e
                                        </option>

                                        <option value="punetor/e"
                                            <?= $user["roli"] === "punetor/e" ? "selected" : "" ?>>
                                            Punetor/e
                                        </option>

                                        <option value="administrator"
                                            <?= $user["roli"] === "administrator" ? "selected" : "" ?>>
                                            Administrator
                                        </option>
                                    </select>

                                    <button type="submit" class="btn">
                                        Ruaj
                                    </button>

                                </form>

                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="empty-message">
                        Nuk ka perdorues per t'u shfaqur.
                    </td>
                </tr>

            <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<footer>
    <p>© 2026 Biblioteka Ime | Paneli i Administrimit</p>
</footer>

</body>
</html>
