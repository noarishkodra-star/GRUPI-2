<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* Merr të dhënat dhe rolin nga databaza */
$stmt = $conn->prepare("
    SELECT emri, roli
    FROM users
    WHERE id = ?
");

if (!$stmt) {
    die("Gabim gjatë kontrollimit të përdoruesit.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$rezultati = $stmt->get_result();
$perdoruesi = $rezultati->fetch_assoc();
$stmt->close();

if (!$perdoruesi) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$emri = $perdoruesi["emri"] ?? "Përdorues";
$roli = strtolower(trim($perdoruesi["roli"] ?? ""));

/* Rregullo rolin nëse është bosh */
if ($roli === "" || $roli === "pa rol") {
    $roli = "nxenes/e";

    $ndrysho = $conn->prepare("
        UPDATE users
        SET roli = ?
        WHERE id = ?
    ");

    $ndrysho->bind_param("si", $roli, $user_id);
    $ndrysho->execute();
    $ndrysho->close();
}

/* Njohja e roleve */
$roletNxenes = [
    "nxenes",
    "nxenes/e",
    "nxënës",
    "nxënës/e"
];

$roletAdmin = [
    "administrator",
    "admin"
];

$eshteNxenes = in_array($roli, $roletNxenes, true);
$eshteAdmin = in_array($roli, $roletAdmin, true);

$_SESSION["emri"] = $emri;
$_SESSION["roli"] = $roli;

/* Funksioni për sigurinë e tekstit */
function h($teksti) {
    return htmlspecialchars(
        (string)$teksti,
        ENT_QUOTES,
        "UTF-8"
    );
}

/* Mesazhet */
$mesazhi = "";
$lloji = "";

if (isset($_SESSION["mesazhi_huazimit"])) {
    $mesazhi = $_SESSION["mesazhi_huazimit"]["teksti"];
    $lloji = $_SESSION["mesazhi_huazimit"]["lloji"];

    unset($_SESSION["mesazhi_huazimit"]);
}

/* Kërkesa për kthimin e librit */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (($_POST["action"] ?? "") === "kerko_kthimin") {

        try {

            if (!$eshteNxenes) {
                throw new Exception(
                    "Vetëm nxënësit mund të kërkojnë kthimin e librave."
                );
            }

            $huazimi_id = (int)($_POST["huazimi_id"] ?? 0);

            if ($huazimi_id <= 0) {
                throw new Exception("Huazimi nuk është i vlefshëm.");
            }

            /*
             * Lejohet kërkesa për kthim si për librin e rezervuar,
             * ashtu edhe për librin e huazuar.
             */
            $stmt = $conn->prepare("
                UPDATE borrowings
                SET statusi = 'kerkese_kthimi'
                WHERE id = ?
                AND user_id = ?
                AND statusi IN ('huazuar', 'rezervuar')
            ");

            if (!$stmt) {
                throw new Exception("Gabim në përgatitjen e kërkesës.");
            }

            $stmt->bind_param(
                "ii",
                $huazimi_id,
                $user_id
            );

            $stmt->execute();

            if ($stmt->affected_rows === 1) {

                $_SESSION["mesazhi_huazimit"] = [
                    "teksti" => "Kërkesa për kthimin e librit u dërgua me sukses te administratori!",
                    "lloji" => "sukses"
                ];

            } else {

                $_SESSION["mesazhi_huazimit"] = [
                    "teksti" => "Kërkesa nuk u dërgua. Libri mund të jetë kthyer ose kërkesa është dërguar më parë.",
                    "lloji" => "gabim"
                ];
            }

            $stmt->close();

        } catch (Throwable $e) {

            $_SESSION["mesazhi_huazimit"] = [
                "teksti" => $e->getMessage(),
                "lloji" => "gabim"
            ];
        }

        header("Location: huazimet.php");
        exit;
    }
}

/* Merr huazimet e përdoruesit */
$huazimet = [];
$gabimDB = false;

try {

    $stmt = $conn->prepare("
        SELECT
            br.id,
            br.data_huazimit,
            br.data_kthimit,
            br.statusi,
            b.titulli,
            b.autori,
            b.kategoria
        FROM borrowings br
        INNER JOIN books b ON br.book_id = b.id
        WHERE br.user_id = ?
        ORDER BY br.id DESC
    ");

    if (!$stmt) {
        throw new Exception("Gabim gjatë marrjes së huazimeve.");
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $rezultati = $stmt->get_result();

    while ($rreshti = $rezultati->fetch_assoc()) {
        $huazimet[] = $rreshti;
    }

    $stmt->close();

} catch (Throwable $e) {
    $gabimDB = true;
}

/* Përkthimi i statusit */
function statusiShqip($statusi) {

    switch (strtolower(trim($statusi))) {

        case "rezervuar":
            return "I rezervuar";

        case "huazuar":
            return "I huazuar";

        case "kerkese_kthimi":
            return "Kërkesë për kthim";

        case "kthyer":
            return "I kthyer";

        default:
            return "Status i panjohur";
    }
}
?>

<!DOCTYPE html>
<html lang="sq">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Huazimet e mia | Biblioteka Ime</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f4f9;
            color: #172b49;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(135deg, #102442, #173f70);
            color: white;
            padding: 25px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h1 {
            font-size: 25px;
        }

        header p {
            color: #d8e2ef;
            margin-top: 6px;
        }

        .butoni-panelit {
            display: inline-block;
            background: #d8b66a;
            color: #102442;
            padding: 12px 19px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .butoni-panelit:hover {
            background: white;
            transform: translateY(-2px);
        }

        .container {
            width: 92%;
            max-width: 1150px;
            margin: 35px auto;
        }

        .titulli {
            margin-bottom: 25px;
        }

        .titulli h2 {
            font-size: 27px;
            color: #102442;
            margin-bottom: 8px;
        }

        .titulli p {
            color: #68778c;
        }

        .mesazhi {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .sukses {
            background: #e1f7e9;
            color: #176b35;
            border: 1px solid #a8dfb8;
        }

        .gabim {
            background: #ffe6e6;
            color: #a32222;
            border: 1px solid #f2b4b4;
        }

        .lista {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 22px;
        }

        .karta {
            background: white;
            border-radius: 15px;
            padding: 23px;
            box-shadow: 0 5px 20px rgba(16, 36, 66, 0.08);
            border: 1px solid #e5eaf1;
            transition: 0.3s;
        }

        .karta:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(16, 36, 66, 0.13);
        }

        .ikona {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: #eaf0f8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            margin-bottom: 17px;
        }

        .karta h3 {
            color: #102442;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .detajet {
            color: #64748b;
            line-height: 1.8;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .statusi {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 17px;
        }

        .status-rezervuar {
            background: #e7efff;
            color: #2454a6;
        }

        .status-huazuar {
            background: #fff1d6;
            color: #9b6500;
        }

        .status-kerkese_kthimi {
            background: #f0e9ff;
            color: #6539a5;
        }

        .status-kthyer {
            background: #e0f5e7;
            color: #18733a;
        }

        .butoni-kthimit {
            display: block;
            width: 100%;
            border: none;
            background: #d8b66a;
            color: #102442;
            padding: 13px;
            border-radius: 9px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
        }

        .butoni-kthimit:hover {
            background: #102442;
            color: white;
        }

        .butoni-kthimit:disabled {
            background: #e5e7eb;
            color: #777;
            cursor: not-allowed;
        }

        .bosh {
            background: white;
            padding: 45px 20px;
            text-align: center;
            border-radius: 15px;
            color: #64748b;
            box-shadow: 0 5px 20px rgba(16, 36, 66, 0.06);
        }

        .bosh h3 {
            color: #102442;
            margin-bottom: 10px;
        }

        footer {
            text-align: center;
            color: #8490a0;
            padding: 30px 15px;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            header {
                padding: 20px;
            }

            header h1 {
                font-size: 21px;
            }

            .container {
                margin: 25px auto;
            }

            .titulli h2 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<header>

    <div>
        <h1>📚 Biblioteka Ime</h1>
        <p>Mirë se erdhe, <?= h($emri) ?>!</p>
    </div>

    <a href="dashboard.php" class="butoni-panelit">
        ← Kthehu te paneli
    </a>

</header>

<main class="container">

    <div class="titulli">
        <h2>Huazimet e mia</h2>
        <p>Këtu mund t'i shikosh librat që ke rezervuar ose huazuar.</p>
    </div>

    <?php if ($mesazhi !== ""): ?>

        <div class="mesazhi <?= h($lloji) ?>">
            <?= h($mesazhi) ?>
        </div>

    <?php endif; ?>

    <?php if ($gabimDB): ?>

        <div class="mesazhi gabim">
            Nuk mund të ngarkohen huazimet. Kontrollo tabelat dhe kolonat në databazë.
        </div>

    <?php elseif (count($huazimet) === 0): ?>

        <div class="bosh">
            <h3>📖 Nuk ke ende huazime</h3>
            <p>Kur të rezervosh një libër, ai do të shfaqet këtu.</p>
            <br>

            <a href="explorimilibrat.php" class="butoni-panelit">
                Eksploro librat
            </a>
        </div>

    <?php else: ?>

        <div class="lista">

            <?php foreach ($huazimet as $huazimi): ?>

                <?php
                    $statusi = strtolower(trim($huazimi["statusi"] ?? ""));
                    $klasaStatusit = "status-" . $statusi;
                ?>

                <div class="karta">

                    <div class="ikona">📘</div>

                    <h3><?= h($huazimi["titulli"]) ?></h3>

                    <div class="detajet">

                        <strong>Autori:</strong>
                        <?= h($huazimi["autori"]) ?>
                        <br>

                        <strong>Kategoria:</strong>
                        <?= h($huazimi["kategoria"]) ?>
                        <br>

                        <strong>Data e huazimit:</strong>
                        <?= h($huazimi["data_huazimit"]) ?>
                        <br>

                        <strong>Data e kthimit:</strong>
                        <?= h($huazimi["data_kthimit"]) ?>

                    </div>

                    <div>
                        <span class="statusi <?= h($klasaStatusit) ?>">
                            <?= h(statusiShqip($statusi)) ?>
                        </span>
                    </div>

                    <?php if (
                        $eshteNxenes &&
                        in_array($statusi, ["huazuar", "rezervuar"], true)
                    ): ?>

                        <form method="POST" action="huazimet.php"
                              onsubmit="return confirm('A dëshiron të dërgosh kërkesën për kthimin e këtij libri?');">

                            <input
                                type="hidden"
                                name="action"
                                value="kerko_kthimin"
                            >

                            <input
                                type="hidden"
                                name="huazimi_id"
                                value="<?= (int)$huazimi["id"] ?>"
                            >

                            <button
                                type="submit"
                                class="butoni-kthimit"
                            >
                                ↩ Kthe librin
                            </button>

                        </form>

                    <?php elseif ($statusi === "kerkese_kthimi"): ?>

                        <button class="butoni-kthimit" disabled>
                            Në pritje të konfirmimit nga administratori
                        </button>

                    <?php elseif ($statusi === "kthyer"): ?>

                        <button class="butoni-kthimit" disabled>
                            ✓ Libri është kthyer
                        </button>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer>
    © <?= date("Y") ?> Biblioteka Ime - Të gjitha të drejtat e rezervuara.
</footer>

</body>
</html>


