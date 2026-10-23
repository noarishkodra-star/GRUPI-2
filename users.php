<?php
session_start();
require_once "db.php";

// Vetëm administratori mund ta hapë këtë faqe
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["roli"] ?? "") !== "administrator"
) {
    http_response_code(403);
    exit("Nuk ke leje për ta hapur këtë faqe.");
}

// Token për mbrojtjen e formularit
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mesazhi = "";
$gabim = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $gabim = "Kërkesa nuk është e vlefshme. Rifresko faqen.";
    } else {
        $user_id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
        $roli = $_POST["roli"] ?? "";

        $rolet_lejuara = ["nxenes/e", "punetor/e", "administrator"];

        if (!$user_id || !in_array($roli, $rolet_lejuara, true)) {
            $gabim = "Të dhënat nuk janë të vlefshme.";
        } elseif ($user_id === (int) $_SESSION["user_id"]) {
            $gabim = "Nuk mund ta ndryshosh rolin e llogarisë sate këtu.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET roli = ? WHERE id = ?"
            );
            $stmt->bind_param("si", $roli, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $mesazhi = "Roli u ndryshua me sukses.";
            } else {
                $kontrollo = $conn->prepare(
                    "SELECT id, roli FROM users WHERE id = ?"
                );
                $kontrollo->bind_param("i", $user_id);
                $kontrollo->execute();
                $rez = $kontrollo->get_result()->fetch_assoc();

                if ($rez && $rez["roli"] === $roli) {
                    $mesazhi = "Përdoruesi tashmë e ka këtë rol.";
                } else {
                    $gabim = "Përdoruesi nuk u gjet ose ndryshimi dështoi.";
                }
                $kontrollo->close();
            }

            $stmt->close();
        }
    }
}

$rezultati = $conn->query(
    "SELECT id, emri, username, email, roli
     FROM users ORDER BY id ASC"
);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menaxho përdoruesit - Biblioteka</title>
    <link rel="stylesheet" href="style.css">
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
    <h1>Menaxhimi i përdoruesve</h1>
    <p>Këtu administratori mund t'ua ndryshojë rolet përdoruesve.</p>

    <?php if ($mesazhi !== ""): ?>
        <p class="notice success"><?= h($mesazhi) ?></p>
    <?php endif; ?>

    <?php if ($gabim !== ""): ?>
        <p class="notice error"><?= h($gabim) ?></p>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Emri</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Roli</th>
                    <th>Ndrysho rolin</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($user = $rezultati->fetch_assoc()): ?>
                <tr>
                    <td><?= h($user["id"]) ?></td>
                    <td><?= h($user["emri"]) ?></td>
                    <td><?= h($user["username"]) ?></td>
                    <td><?= h($user["email"]) ?></td>
                    <td><?= h($user["roli"]) ?></td>
                    <td>
                        <?php if ((int)$user["id"] === (int)$_SESSION["user_id"]): ?>
                            <span>Llogaria jote</span>
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
                                        Nxënës/e
                                    </option>
                                    <option value="punetor/e"
                                        <?= $user["roli"] === "punetor/e" ? "selected" : "" ?>>
                                        Punëtor/e
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
            </tbody>
        </table>
    </div>
</main>

<footer>
    <p>© 2026 Biblioteka Ime</p>
</footer>

</body>
</html>

