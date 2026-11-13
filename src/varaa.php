<?php
session_start();

if (!isset($_SESSION["kayttaja_id"])) {
    header("Location: kirjautuminen.php");
    exit;
}

require_once __DIR__ . "/back/db.php";
$kayttajaId = (int) $_SESSION["kayttaja_id"];
$roolikysely = $pdo->prepare("SELECT Rooli, Aktiivinen FROM Kayttajat WHERE KayttajaID = :id");
$roolikysely->execute(["id" => $kayttajaId]);
$istunnonKayttaja = $roolikysely->fetch(PDO::FETCH_ASSOC);
if (!$istunnonKayttaja || !(int) $istunnonKayttaja["Aktiivinen"]) {
    session_unset();
    session_destroy();
    header("Location: kirjautuminen.php?passiivinen=1");
    exit;
}
$rooli = $istunnonKayttaja["Rooli"];

if ($rooli === "Opettaja") {
    header("Location: admin.php");
    exit;
}
if ($rooli !== "kayttaja") {
    http_response_code(403);
    exit("Sinulla ei ole oikeutta tehdä varauksia.");
}

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$viesti = isset($_GET["varattu"]) ? "Varaus tallennettiin." : "";
$virhe = "";
$luokkaId = "";
$aihe = "";
$paivamaara = "";
$alkamisaika = "";
$loppumisaika = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    $luokkaId = filter_input(INPUT_POST, "luokka_id", FILTER_VALIDATE_INT) ?: "";
    $aihe = trim($_POST["aihe"] ?? "");
    $paivamaara = trim($_POST["paivamaara"] ?? "");
    $alkamisaika = str_replace(".", ":", trim($_POST["alkamisaika"] ?? ""));
    $loppumisaika = str_replace(".", ":", trim($_POST["loppumisaika"] ?? ""));
    $paivamaaraTarkistettu = DateTime::createFromFormat("!Y-m-d", $paivamaara);
    $paivamaaraKelvollinen = $paivamaaraTarkistettu
        && $paivamaaraTarkistettu->format("Y-m-d") === $paivamaara;

    if (!is_string($csrfToken) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $virhe = "Lomake vanheni. Päivitä sivu ja yritä uudelleen.";
    } elseif (!$luokkaId || $aihe === "" || $paivamaara === "" || $alkamisaika === "" || $loppumisaika === "") {
        $virhe = "Täytä kaikki varauksen kentät.";
    } elseif (mb_strlen($aihe) > 100) {
        $virhe = "Varauksen aihe voi olla enintään 100 merkkiä.";
    } elseif (!$paivamaaraKelvollinen) {
        $virhe = "Valitse kelvollinen päivämäärä.";
    } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $alkamisaika)
        || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $loppumisaika)) {
        $virhe = "Anna alkamis- ja päättymisaika muodossa 14.00.";
    } elseif ($alkamisaika >= $loppumisaika) {
        $virhe = "Päättymisajan pitää olla alkamisajan jälkeen.";
    } else {
        $luokanTarkistus = $pdo->prepare("SELECT 1 FROM Luokat WHERE LuokkaID = :id");
        $luokanTarkistus->execute(["id" => $luokkaId]);

        if (!$luokanTarkistus->fetchColumn()) {
            $virhe = "Valittua luokkaa ei löytynyt.";
        } else {
            $varaus = $pdo->prepare(
                "INSERT INTO Varaukset (KayttajaID, LuokkaID, Paivamaara, Alkamisaika, Loppumisaika, Aihe)
                 VALUES (:kayttaja_id, :luokka_id, :paivamaara, :alkamisaika, :loppumisaika, :aihe)"
            );
            $varaus->execute([
                "kayttaja_id" => $kayttajaId,
                "luokka_id" => $luokkaId,
                "paivamaara" => $paivamaara,
                "alkamisaika" => $alkamisaika,
                "loppumisaika" => $loppumisaika,
                "aihe" => $aihe,
            ]);
            header("Location: varaa.php?varattu=1");
            exit;
        }
    }
}

$luokat = $pdo->query("SELECT LuokkaID, Nimi FROM Luokat ORDER BY Nimi")->fetchAll(PDO::FETCH_ASSOC);
$varaukset = $pdo->query(
    "SELECT DISTINCT l.Nimi AS LuokanNimi, v.Aihe, v.Paivamaara, v.Alkamisaika, v.Loppumisaika
     FROM Varaukset v
     JOIN Luokat l ON l.LuokkaID = v.LuokkaID
     ORDER BY v.Paivamaara, v.Alkamisaika"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varaa huone | Luokkien varaus</title>
    <style>
        :root {
            --blue: #365d81;
            --blue-dark: #243f59;
            --blue-soft: #e7eef5;
            --paper: #ffffff;
            --ink: #263746;
            --muted: #627384;
            --line: #d3dee8;
            --danger: #a52b35;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--blue-soft);
            color: var(--ink);
            font-family: Arial, sans-serif;
        }

        header {
            background: var(--blue);
            color: var(--paper);
            box-shadow: 0 3px 12px rgb(23 45 67 / 18%);
        }

        .ylapalkin-sisalto,
        main {
            width: min(100% - 48px, 1060px);
            margin: 0 auto;
        }

        .ylapalkin-sisalto {
            display: flex;
            min-height: 76px;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        h1 { margin: 0; font-size: 24px; }
        h2 { margin: 0 0 18px; font-size: 20px; }
        header a { color: var(--paper); }
        main { padding: 28px 0 40px; }

        .palsta {
            display: grid;
            grid-template-columns: minmax(260px, 340px) minmax(0, 1fr);
            align-items: start;
            gap: 18px;
        }

        .osio {
            padding: 22px;
            border: 1px solid var(--line);
            background: var(--paper);
        }

        label {
            display: block;
            margin: 13px 0 5px;
            font-size: 14px;
            font-weight: 700;
        }

        input,
        select {
            width: 100%;
            min-height: 42px;
            padding: 9px 10px;
            border: 1px solid #b8c5d1;
            border-radius: 3px;
            background: var(--paper);
            color: var(--ink);
            font: inherit;
        }

        input:focus,
        select:focus {
            border-color: var(--blue);
            outline: 2px solid rgb(54 93 129 / 18%);
        }

        button {
            min-height: 40px;
            margin-top: 18px;
            padding: 9px 14px;
            border: 0;
            border-radius: 3px;
            background: var(--blue);
            color: var(--paper);
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }

        button:hover { background: var(--blue-dark); }

        .viesti,
        .virhe {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-left: 4px solid var(--blue);
            background: var(--paper);
        }

        .virhe { border-left-color: var(--danger); color: #77232a; }
        .taulukko-kuori { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--paper);
            font-size: 14px;
        }

        th,
        td {
            padding: 11px 10px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: var(--blue-dark);
            color: var(--paper);
            white-space: nowrap;
        }

        .tyhja { color: var(--muted); }

        @media (max-width: 760px) {
            .ylapalkin-sisalto,
            main { width: min(100% - 32px, 1060px); }
            .ylapalkin-sisalto { min-height: 0; align-items: flex-start; flex-direction: column; gap: 8px; padding: 16px 0; }
            .palsta { grid-template-columns: 1fr; }
            .osio { padding: 18px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="ylapalkin-sisalto">
            <h1>Varaa huone</h1>
            <nav aria-label="Päänavigaatio">
                <a href="yleis.php">Etusivu</a>
                <a href="profiili.php">Profiili</a>
                <a href="uloskirjautuminen.php">Kirjaudu ulos</a>
            </nav>
        </div>
    </header>

    <main>
        <?php if ($viesti !== ""): ?>
            <div class="viesti" role="status"><?= htmlspecialchars($viesti, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>
        <?php if ($virhe !== ""): ?>
            <div class="virhe" role="alert"><?= htmlspecialchars($virhe, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <div class="palsta">
            <section class="osio" aria-labelledby="varaus-otsikko">
                <h2 id="varaus-otsikko">Uusi varaus</h2>
                <?php if ($luokat === []): ?>
                    <p class="tyhja">Varattavia luokkia ei ole saatavilla.</p>
                <?php else: ?>
                    <form method="post" action="varaa.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">

                        <label for="luokka_id">Luokka tai huone</label>
                        <select id="luokka_id" name="luokka_id" required>
                            <option value="">Valitse luokka</option>
                            <?php foreach ($luokat as $luokka): ?>
                                <option value="<?= (int) $luokka["LuokkaID"] ?>" <?= (string) $luokkaId === (string) $luokka["LuokkaID"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($luokka["Nimi"], ENT_QUOTES, "UTF-8") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="aihe">Varauksen aihe</label>
                        <input type="text" id="aihe" name="aihe" maxlength="100" value="<?= htmlspecialchars($aihe, ENT_QUOTES, "UTF-8") ?>" required>

                        <label for="paivamaara">Päivämäärä</label>
                        <input type="date" id="paivamaara" name="paivamaara" value="<?= htmlspecialchars($paivamaara, ENT_QUOTES, "UTF-8") ?>" required>

                        <label for="alkamisaika">Alkamisaika</label>
                        <input type="text" id="alkamisaika" name="alkamisaika" maxlength="5" placeholder="14.00" pattern="(?:[01][0-9]|2[0-3])[.:][0-5][0-9]" title="Esimerkiksi 14.00 tai 14:00" value="<?= htmlspecialchars(str_replace(":", ".", $alkamisaika), ENT_QUOTES, "UTF-8") ?>" required>

                        <label for="loppumisaika">Päättymisaika</label>
                        <input type="text" id="loppumisaika" name="loppumisaika" maxlength="5" placeholder="15.00" pattern="(?:[01][0-9]|2[0-3])[.:][0-5][0-9]" title="Esimerkiksi 15.00 tai 15:00" value="<?= htmlspecialchars(str_replace(":", ".", $loppumisaika), ENT_QUOTES, "UTF-8") ?>" required>

                        <button type="submit">Varaa</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="osio" aria-labelledby="varaukset-otsikko">
                <h2 id="varaukset-otsikko">Varaukset</h2>
                <?php if ($varaukset === []): ?>
                    <p class="tyhja">Varauksia ei ole vielä tehty.</p>
                <?php else: ?>
                    <div class="taulukko-kuori">
                        <table>
                            <thead>
                                <tr>
                                    <th>Luokka/huone</th>
                                    <th>Aihe</th>
                                    <th>Päivämäärä</th>
                                    <th>Aika</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($varaukset as $varaus): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($varaus["LuokanNimi"], ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars($varaus["Aihe"], ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars($varaus["Paivamaara"], ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars(substr($varaus["Alkamisaika"], 0, 5) . " - " . substr($varaus["Loppumisaika"], 0, 5), ENT_QUOTES, "UTF-8") ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
