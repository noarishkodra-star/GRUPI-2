<?php
require_once "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) && !isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"] ?? $_SESSION["id"];
$role = $_SESSION["roli"] ?? $_SESSION["role"] ?? "";

if ($role !== "administrator") {
    http_response_code(403);
    exit("Nuk keni leje për të hyrë në këtë faqe.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = "";
$error = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirectAdmin() {
    header("Location: admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
        ) {
            throw new Exception("Kërkesa nuk është e vlefshme. Rifresko faqen.");
        }

        $action = $_POST["action"] ?? "";

        switch ($action) {

            case "add_book":
                $title = trim($_POST["titulli"] ?? "");
                $author = trim($_POST["autori"] ?? "");
                $category = trim($_POST["kategoria"] ?? "");
                $quantity = filter_var(
                    $_POST["sasia"] ?? null,
                    FILTER_VALIDATE_INT
                );

                if ($title === "" || $author === "" || $category === "") {
                    throw new Exception("Plotëso të gjitha fushat e librit.");
                }

                if ($quantity === false || $quantity < 1) {
                    throw new Exception("Sasia duhet të jetë së paku 1.");
                }

                $stmt = $conn->prepare(
                    "INSERT INTO books (titulli, autori, kategoria, sasia)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("sssi", $title, $author, $category, $quantity);
                $stmt->execute();
                $stmt->close();

                $_SESSION["admin_message"] = "Libri u shtua me sukses.";
                redirectAdmin();

            case "delete_book":
                $bookId = filter_var(
                    $_POST["book_id"] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (!$bookId || $bookId < 1) {
                    throw new Exception("Libri i zgjedhur nuk është i vlefshëm.");
                }

                $stmt = $conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM borrowings
                     WHERE book_id = ?"
                );
                $stmt->bind_param("i", $bookId);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()["total"];
                $stmt->close();

                if ((int)$count > 0) {
                    throw new Exception(
                        "Ky libër ka histori huazimesh dhe nuk mund të fshihet."
                    );
                }

                $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
                $stmt->bind_param("i", $bookId);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    throw new Exception("Libri nuk u gjet.");
                }

                $stmt->close();

                $_SESSION["admin_message"] = "Libri u fshi me sukses.";
                redirectAdmin();

            case "set_card_expiry":
                $studentId = filter_var(
                    $_POST["student_id"] ?? null,
                    FILTER_VALIDATE_INT
                );
                $expiry = trim($_POST["expiry"] ?? "");

                if (!$studentId || $studentId < 1) {
                    throw new Exception("Nxënësi nuk është i vlefshëm.");
                }

                if ($expiry !== "") {
                    $date = DateTime::createFromFormat("!Y-m-d", $expiry);

                    if (!$date || $date->format("Y-m-d") !== $expiry) {
                        throw new Exception("Data e kartelës nuk është e vlefshme.");
                    }

                    $stmt = $conn->prepare(
                        "UPDATE users
                         SET data_skadimit_karteles = ?
                         WHERE id = ? AND roli = 'nxenes'"
                    );
                    $stmt->bind_param("si", $expiry, $studentId);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE users
                         SET data_skadimit_karteles = NULL
                         WHERE id = ? AND roli = 'nxenes'"
                    );
                    $stmt->bind_param("i", $studentId);
                }

                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    $check = $conn->prepare(
                        "SELECT id FROM users WHERE id = ? AND roli = 'nxenes'"
                    );
                    $check->bind_param("i", $studentId);
                    $check->execute();
                    $exists = $check->get_result()->num_rows > 0;
                    $check->close();

                    if (!$exists) {
                        $stmt->close();
                        throw new Exception("Nxënësi nuk u gjet.");
                    }
                }

                $stmt->close();

                $_SESSION["admin_message"] = "Afati i kartelës u përditësua.";
                redirectAdmin();

            case "extend_due":
                $borrowingId = filter_var(
                    $_POST["borrowing_id"] ?? null,
                    FILTER_VALIDATE_INT
                );
                $newDate = trim($_POST["data_kthimit"] ?? "");

                if (!$borrowingId || $borrowingId < 1) {
                    throw new Exception("Huazimi nuk është i vlefshëm.");
                }

                $date = DateTime::createFromFormat("!Y-m-d", $newDate);

                if (!$date || $date->format("Y-m-d") !== $newDate) {
                    throw new Exception("Data e kthimit nuk është e vlefshme.");
                }

                $today = new DateTime("today");
                $maximum = (clone $today)->modify("+7 days");

                if ($date < $today || $date > $maximum) {
                    throw new Exception(
                        "Data e kthimit duhet të jetë nga sot deri në 7 ditë."
                    );
                }

                $stmt = $conn->prepare(
                    "UPDATE borrowings
                     SET data_kthimit = ?
                     WHERE id = ? AND statusi IN ('rezervuar', 'huazuar')"
                );
                $stmt->bind_param("si", $newDate, $borrowingId);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    $stmt->close();
                    throw new Exception(
                        "Huazimi nuk u gjet ose nuk mund të ndryshohet."
                    );
                }

                $stmt->close();

                $_SESSION["admin_message"] = "Afati i kthimit u përditësua.";
                redirectAdmin();

            case "mark_borrowed":
                $borrowingId = filter_var(
                    $_POST["borrowing_id"] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (!$borrowingId || $borrowingId < 1) {
                    throw new Exception("Huazimi nuk është i vlefshëm.");
                }

                $stmt = $conn->prepare(
                    "UPDATE borrowings
                     SET statusi = 'huazuar'
                     WHERE id = ? AND statusi = 'rezervuar'"
                );
                $stmt->bind_param("i", $borrowingId);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    $stmt->close();
                    throw new Exception(
                        "Rezervimi nuk u gjet ose është përpunuar më parë."
                    );
                }

                $stmt->close();

                $_SESSION["admin_message"] = "Libri u shënua si i huazuar.";
                redirectAdmin();

            case "return_book":
                $borrowingId = filter_var(
                    $_POST["borrowing_id"] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (!$borrowingId || $borrowingId < 1) {
                    throw new Exception("Huazimi nuk është i vlefshëm.");
                }

                $conn->begin_transaction();

                $stmt = $conn->prepare(
                    "SELECT book_id
                     FROM borrowings
                     WHERE id = ? AND statusi IN ('rezervuar', 'huazuar')
                     FOR UPDATE"
                );
                $stmt->bind_param("i", $borrowingId);
                $stmt->execute();
                $borrowing = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$borrowing) {
                    throw new Exception(
                        "Rezervimi nuk u gjet ose libri është kthyer."
                    );
                }

                $bookId = (int)$borrowing["book_id"];

                $stmt = $conn->prepare(
                    "UPDATE borrowings SET statusi = 'kthyer' WHERE id = ?"
                );
                $stmt->bind_param("i", $borrowingId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare(
                    "UPDATE books SET sasia = sasia + 1 WHERE id = ?"
                );
                $stmt->bind_param("i", $bookId);
                $stmt->execute();

                if ($stmt->affected_rows !== 1) {
                    throw new Exception("Libri nuk u gjet në databazë.");
                }

                $stmt->close();
                $conn->commit();

                $_SESSION["admin_message"] = "Libri u kthye me sukses.";
                redirectAdmin();

            default:
                throw new Exception("Veprimi i kërkuar nuk njihet.");
        }

    } catch (Throwable $ex) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }

        $error = $ex instanceof mysqli_sql_exception
            ? "Veprimi dështoi. Kontrollo strukturën e databazës dhe provo përsëri."
            : $ex->getMessage();
    }
}

$message = $_SESSION["admin_message"] ?? "";
unset($_SESSION["admin_message"]);

$books = $conn->query(
    "SELECT id, titulli, autori, kategoria, sasia
     FROM books ORDER BY titulli"
);

$students = $conn->query(
    "SELECT id, emri, username, email, data_skadimit_karteles
     FROM users
     WHERE roli = 'nxenes'
     ORDER BY emri"
);

$borrowings = $conn->query(
    "SELECT b.id, b.user_id, b.book_id, b.data_huazimit,
            b.data_kthimit, b.statusi,
            u.emri AS nxenesi,
            bk.titulli AS libri
     FROM borrowings b
     LEFT JOIN users u ON u.id = b.user_id
     LEFT JOIN books bk ON bk.id = b.book_id
     ORDER BY
        CASE WHEN b.statusi IN ('rezervuar', 'huazuar') THEN 0 ELSE 1 END,
        b.data_kthimit ASC"
);

$totalBooks = $conn->query(
    "SELECT COUNT(*) AS total FROM books"
)->fetch_assoc()["total"];

$totalStudents = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE roli = 'nxenes'"
)->fetch_assoc()["total"];

$activeBorrowings = $conn->query(
    "SELECT COUNT(*) AS total
     FROM borrowings
     WHERE statusi IN ('rezervuar', 'huazuar')"
)->fetch_assoc()["total"];

$lateBorrowings = $conn->query(
    "SELECT COUNT(*) AS total
     FROM borrowings
     WHERE statusi IN ('rezervuar', 'huazuar')
       AND data_kthimit < CURDATE()"
)->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrimi | Biblioteka Ime</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172033;
        }

        header {
            background: #14213d;
            color: white;
            padding: 22px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header h1 {
            margin: 0;
            font-size: 25px;
        }

        header a {
            color: white;
            text-decoration: none;
            background: #e63946;
            padding: 10px 16px;
            border-radius: 8px;
        }

        main {
            width: 94%;
            max-width: 1400px;
            margin: 28px auto;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        .stat, .panel {
            background: white;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .06);
        }

        .stat span {
            color: #64748b;
            font-size: 14px;
        }

        .stat h2 {
            margin: 10px 0 0;
            font-size: 30px;
        }

        .panel {
            margin-bottom: 24px;
            overflow: hidden;
        }

        .panel h2 {
            margin-top: 0;
            font-size: 21px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        input, select, button {
            font: inherit;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            margin-top: 6px;
        }

        label {
            font-size: 13px;
            color: #475569;
        }

        button, .button {
            border: 0;
            border-radius: 8px;
            padding: 10px 13px;
            cursor: pointer;
            background: #2563eb;
            color: white;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }

        button:hover, .button:hover {
            opacity: .86;
        }

        .danger {
            background: #dc2626;
        }

        .success {
            background: #15803d;
        }

        .warning {
            background: #b45309;
        }

        .muted {
            color: #64748b;
        }

        .notice {
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 18px;
        }

        .notice.ok {
            background: #dcfce7;
            color: #166534;
        }

        .notice.bad {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            text-align: left;
            padding: 13px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        th {
            color: #475569;
            font-size: 13px;
            background: #f8fafc;
        }

        td {
            font-size: 14px;
        }

        td form {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        td form input {
            min-width: 135px;
            margin: 0;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #e2e8f0;
            font-size: 12px;
        }

        .badge.active {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge.late {
            background: #fee2e2;
            color: #b91c1c;
        }

        @media (max-width: 900px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 550px) {
            .stats, .form-grid {
                grid-template-columns: 1fr;
            }

            main {
                width: 92%;
            }

            header h1 {
                font-size: 21px;
            }

            .stat, .panel {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>📚 Biblioteka Ime — Administrimi</h1>
    <a href="logout.php">Dil nga llogaria</a>
</header>

<main>

    <?php if ($message !== ""): ?>
        <div class="notice ok"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="notice bad"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat">
            <span>Gjithsej libra</span>
            <h2><?= (int)$totalBooks ?></h2>
        </div>

        <div class="stat">
            <span>Nxënës të regjistruar</span>
            <h2><?= (int)$totalStudents ?></h2>
        </div>

        <div class="stat">
            <span>Rezervime / huazime aktive</span>
            <h2><?= (int)$activeBorrowings ?></h2>
        </div>

        <div class="stat">
            <span>Me afat të kaluar</span>
            <h2><?= (int)$lateBorrowings ?></h2>
        </div>
    </section>

    <section class="panel">
        <h2>➕ Shto libër të ri</h2>

        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">
            <input type="hidden" name="action" value="add_book">

            <label>
                Titulli
                <input type="text" name="titulli" maxlength="255" required>
            </label>

            <label>
                Autori
                <input type="text" name="autori" maxlength="255" required>
            </label>

            <label>
                Kategoria
                <input type="text" name="kategoria" maxlength="100" required>
            </label>

            <label>
                Sasia e librave
                <input type="number" name="sasia" min="1" required>
            </label>

            <button type="submit">Shto librin</button>
        </form>
    </section>

    <section class="panel">
        <h2>📖 Menaxhimi i librave</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titulli</th>
                        <th>Autori</th>
                        <th>Kategoria</th>
                        <th>Sasia e lirë</th>
                        <th>Veprimi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($book = $books->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$book["id"] ?></td>
                        <td><?= e($book["titulli"]) ?></td>
                        <td><?= e($book["autori"]) ?></td>
                        <td><?= e($book["kategoria"]) ?></td>
                        <td><?= (int)$book["sasia"] ?></td>
                        <td>
                            <form method="POST"
                                  onsubmit="return confirm('A dëshiron ta fshish këtë libër?')">
                                <input type="hidden" name="csrf_token"
                                       value="<?= e($_SESSION["csrf_token"]) ?>">
                                <input type="hidden" name="action"
                                       value="delete_book">
                                <input type="hidden" name="book_id"
                                       value="<?= (int)$book["id"] ?>">
                                <button class="danger" type="submit">Fshi</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>👨‍🎓 Nxënësit dhe kartelat</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nxënësi</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Skadimi i kartelës</th>
                        <th>Ndrysho afatin</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($student["emri"]) ?></td>
                        <td><?= e($student["username"]) ?></td>
                        <td><?= e($student["email"]) ?></td>
                        <td>
                            <?php
                            $expiry = $student["data_skadimit_karteles"];
                            if (!$expiry) {
                                echo '<span class="badge">Pa afat</span>';
                            } elseif ($expiry < date("Y-m-d")) {
                                echo '<span class="badge late">Ka skaduar: '
                                    . e($expiry) . '</span>';
                            } else {
                                echo '<span class="badge active">'
                                    . e($expiry) . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="csrf_token"
                                       value="<?= e($_SESSION["csrf_token"]) ?>">
                                <input type="hidden" name="action"
                                       value="set_card_expiry">
                                <input type="hidden" name="student_id"
                                       value="<?= (int)$student["id"] ?>">
                                <input type="date" name="expiry"
                                       value="<?= e($expiry ?? "") ?>">
                                <button type="submit">Ruaj</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <p class="muted">
            Për ta hequr afatin e kartelës, lëre fushën e datës bosh dhe kliko Ruaj.
        </p>
    </section>

    <section class="panel">
        <h2>📋 Rezervimet dhe huazimet</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nxënësi</th>
                        <th>Libri</th>
                        <th>Rezervuar / huazuar më</th>
                        <th>Afati i kthimit</th>
                        <th>Statusi</th>
                        <th>Veprimet</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($loan = $borrowings->fetch_assoc()): ?>
                    <?php
                    $isActive = in_array(
                        $loan["statusi"],
                        ["rezervuar", "huazuar"],
                        true
                    );

                    $isLate = $isActive
                        && !empty($loan["data_kthimit"])
                        && $loan["data_kthimit"] < date("Y-m-d");
                    ?>
                    <tr>
                        <td><?= e($loan["nxenesi"] ?? "Nxënës i panjohur") ?></td>
                        <td><?= e($loan["libri"] ?? "Libër i panjohur") ?></td>
                        <td><?= e($loan["data_huazimit"]) ?></td>
                        <td>
                            <?php if ($isLate): ?>
                                <span class="badge late">
                                    <?= e($loan["data_kthimit"]) ?> — Vonesë
                                </span>
                            <?php else: ?>
                                <?= e($loan["data_kthimit"]) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge"><?= e($loan["statusi"]) ?></span>
                        </td>
                        <td>
                            <?php if ($loan["statusi"] === "rezervuar"): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="action"
                                           value="mark_borrowed">
                                    <input type="hidden" name="borrowing_id"
                                           value="<?= (int)$loan["id"] ?>">
                                    <button class="success" type="submit">
                                        Shëno huazimin
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($isActive): ?>
                                <form method="POST" style="margin-top: 7px;"
                                      onsubmit="return confirm('A është kthyer libri?')">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="action"
                                           value="return_book">
                                    <input type="hidden" name="borrowing_id"
                                           value="<?= (int)$loan["id"] ?>">
                                    <button class="success" type="submit">
                                        Shëno kthimin
                                    </button>
                                </form>

                                <form method="POST" style="margin-top: 7px;">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="action"
                                           value="extend_due">
                                    <input type="hidden" name="borrowing_id"
                                           value="<?= (int)$loan["id"] ?>">
                                    <input type="date" name="data_kthimit"
                                           min="<?= date("Y-m-d") ?>"
                                           max="<?= date("Y-m-d", strtotime("+7 days")) ?>"
                                           value="<?= e($loan["data_kthimit"]) ?>"
                                           required>
                                    <button class="warning" type="submit">
                                        Ndrysho afatin
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

</body>
</html>
