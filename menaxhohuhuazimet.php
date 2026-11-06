
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$roli = strtolower(trim($_SESSION["roli"] ?? ""));

if ($roli !== "administrator") {
    die("Nuk ke leje për këtë faqe.");
}

$mesazhi = "";
$lloji = "";

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

// Konfirmimi i kthimit të librit
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "konfirmo_kthimin") {

    $huazimi_id = (int)($_POST["huazimi_id"] ?? 0);
    $filloi = false;

    try {
        if ($huazimi_id <= 0) {
            throw new Exception("Huazimi nuk është i vlefshëm.");
        }

        $conn->begin_transaction();
        $filloi = true;

        // Merr huazimin dhe blloko rreshtin
        $stmt = $conn->prepare(
            "SELECT id, book_id, statusi
             FROM borrowings
             WHERE id = ?
             FOR UPDATE"
        );

        $stmt->bind_param("i", $huazimi_id);
        $stmt->execute();

        $huazimi = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$huazimi || $huazimi["statusi"] !== "kerkese_kthimi") {
            throw new Exception("Kjo kërkesë është përpunuar ose nuk ekziston.");
        }

        // Konfirmo kthimin
        $stmt = $conn->prepare(
            "UPDATE borrowings
             SET statusi = 'kthyer'
             WHERE id = ? AND statusi = 'kerkese_kthimi'"
        );

        $stmt->bind_param("i", $huazimi_id);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            throw new Exception("Kthimi nuk u konfirmua.");
        }

        $stmt->close();

        // Shto përsëri një kopje në stok
        $stmt = $conn->prepare(
            "UPDATE books SET sasia = sasia + 1 WHERE id = ?"
        );

        $stmt->bind_param("i", $huazimi["book_id"]);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            throw new Exception("Sasia e librit nuk u përditësua.");
        }

        $stmt->close();

        $conn->commit();
        $filloi = false;

        $mesazhi = "Kthimi i librit u konfirmua me sukses!";
        $lloji = "sukses";

    } catch (Throwable $e) {

        if ($filloi) {
            $conn->rollback();
        }

        $mesazhi = "Gabim: " . $e->getMessage();
        $lloji = "gabim";
    }
}

// Merr të gjitha huazimet dhe emrat e nxënësve
$sql = "
    SELECT
        br.id,
        br.user_id,
        br.book_id,
        br.data_huazimit,
        br.data_kthimit,
        br.statusi,
        u.emri,
        u.username,
        b.titulli,
        b.autori
    FROM borrowings br
    INNER JOIN users u ON br.user_id = u.id
    INNER JOIN books b ON br.book_id = b.id
    ORDER BY
        CASE
            WHEN br.statusi = 'kerkese_kthimi' THEN 0
            WHEN br.statusi = 'huazuar' THEN 1
            ELSE 2
        END,
        br.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Gabim gjatë marrjes së huazimeve: " . h($conn->error));
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menaxhimi i huazimeve</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f3f6fb;
    color: #102442;
}

header {
    background: #102442;
    color: white;
    padding: 20px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

header a {
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.container {
    width: 94%;
    max-width: 1250px;
    margin: 35px auto;
}

h1 {
    margin-bottom: 10px;
}

.pershkrimi {
    color: #64748b;
    margin-bottom: 25px;
}

.mesazhi {
    padding: 15px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-weight: bold;
}

.sukses {
    background: #d1fae5;
    color: #065f46;
}

.gabim {
    background: #fee2e2;
    color: #991b1b;
}

.tabela {
    background: white;
    border-radius: 13px;
    overflow-x: auto;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e5eaf1;
}

th {
    background: #102442;
    color: white;
}

tr:hover td {
    background: #f8fafc;
}

.statusi {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.huazuar {
    background: #fef3c7;
    color: #92400e;
}

.rezervuar {
    background: #dbeafe;
    color: #1d4ed8;
}

.kerkese_kthimi {
    background: #ede9fe;
    color: #6d28d9;
}

.kthyer {
    background: #d1fae5;
    color: #065f46;
}

.btn {
    border: none;
    background: #d8b66a;
    color: #102442;
    padding: 10px 13px;
    border-radius: 7px;
    font-weight: bold;
    cursor: pointer;
}

.btn:hover {
    background: #c5a04e;
}

.pritje {
    color: #6d28d9;
    font-weight: bold;
}

.bosh {
    background: white;
    padding: 35px;
    border-radius: 12px;
    text-align: center;
}
</style>
</head>

<body>

<header>
    <strong>📚 Biblioteka Ime — Administratori</strong>
    <a href="dashboard.php">Kthehu te paneli</a>
</header>

<div class="container">

    <h1>Menaxhimi i huazimeve</h1>

    <p class="pershkrimi">
        Shiko cilët nxënës kanë huazuar libra dhe konfirmo kthimet.
    </p>

    <?php if ($mesazhi !== ""): ?>
        <div class="mesazhi <?= h($lloji) ?>">
            <?= h($mesazhi) ?>
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>

        <div class="tabela">

            <table>
                <thead>
                    <tr>
                        <th>Nxënësi</th>
                        <th>Username</th>
                        <th>Libri</th>
                        <th>Autori</th>
                        <th>Data e huazimit</th>
                        <th>Data e kthimit</th>
                        <th>Statusi</th>
                        <th>Veprimi</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($r = $result->fetch_assoc()): ?>

                    <tr>
                        <td><?= h($r["emri"]) ?></td>
                        <td><?= h($r["username"]) ?></td>
                        <td><?= h($r["titulli"]) ?></td>
                        <td><?= h($r["autori"]) ?></td>
                        <td><?= h($r["data_huazimit"]) ?></td>
                        <td><?= h($r["data_kthimit"]) ?></td>

                        <td>
                            <span class="statusi <?= h($r["statusi"]) ?>">
                                <?php
                                $emrat = [
                                    "rezervuar" => "I rezervuar",
                                    "huazuar" => "I huazuar",
                                    "kerkese_kthimi" => "Kërkesë kthimi",
                                    "kthyer" => "I kthyer"
                                ];

                                echo h($emrat[$r["statusi"]] ?? $r["statusi"]);
                                ?>
                            </span>
                        </td>

                        <td>

                            <?php if ($r["statusi"] === "kerkese_kthimi"): ?>

                                <form method="POST" action="menaxhohuazimet.php"
                                      onsubmit="return confirm('A e ke pranuar fizikisht librin dhe dëshiron ta konfirmosh kthimin?');">

                                    <input type="hidden" name="huazimi_id"
                                           value="<?= (int)$r["id"] ?>">

                                    <button class="btn" type="submit"
                                            name="action"
                                            value="konfirmo_kthimin">
                                        Konfirmo kthimin
                                    </button>

                                </form>

                            <?php elseif ($r["statusi"] === "huazuar"): ?>

                                <span>Ende i huazuar</span>

                            <?php elseif ($r["statusi"] === "kthyer"): ?>

                                <span>U kthye</span>

                            <?php else: ?>

                                <span>Pa veprim</span>

                            <?php endif; ?>

                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        </div>

    <?php else: ?>

        <div class="bosh">
            <h3>Nuk ka ende huazime të regjistruara.</h3>
        </div>

    <?php endif; ?>

</div>

</body>
</html>

