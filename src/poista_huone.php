<?php
session_start();

if (!isset($_SESSION["kayttaja_id"])) {
    header("Location: kirjautuminen.php");
    exit;
}

require_once __DIR__ . "/back/db.php";
$kayttajaId = (int) $_SESSION["kayttaja_id"];
$kayttajaKysely = $pdo->prepare("SELECT Rooli, Aktiivinen FROM Kayttajat WHERE KayttajaID = :id");
$kayttajaKysely->execute(["id" => $kayttajaId]);
$istunnonKayttaja = $kayttajaKysely->fetch(PDO::FETCH_ASSOC);
if (!$istunnonKayttaja || !(int) $istunnonKayttaja["Aktiivinen"]) {
    session_unset();
    session_destroy();
    header("Location: kirjautuminen.php?passiivinen=1");
    exit;
}
$onYllapitaja = $istunnonKayttaja["Rooli"] === "Opettaja";
$varausEhto = $onYllapitaja
    ? "VarausID = :varaus_id"
    : "VarausID = :varaus_id AND KayttajaID = :kayttaja_id";
$viesti = "";
$virhe = "";
$muokattava = null;

if (isset($_GET["poistettu"])) {
    $viesti = "Varaus poistettiin.";
} elseif (isset($_GET["muokattu"])) {
    $viesti = "Varauksen muutokset tallennettiin.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $varausId = filter_input(INPUT_POST, "varaus_id", FILTER_VALIDATE_INT);
    $csrfToken = $_POST["csrf_token"] ?? "";
    $toiminto = $_POST["toiminto"] ?? "";

    if (!is_string($csrfToken) || !hash_equals($_SESSION["csrf_token"] ?? "", $csrfToken)) {
        $virhe = "Pyyntö vanheni. Päivitä sivu ja yritä uudelleen.";
    } elseif (!$varausId) {
        $virhe = "Varausta ei löytynyt.";
    } elseif ($toiminto === "tallenna") {
        $luokkaId = filter_input(INPUT_POST, "luokka_id", FILTER_VALIDATE_INT);
        $aihe = trim($_POST["aihe"] ?? "");
        $paivamaara = trim($_POST["paivamaara"] ?? "");
        $alkamisaika = str_replace(".", ":", trim($_POST["alkamisaika"] ?? ""));
        $loppumisaika = str_replace(".", ":", trim($_POST["loppumisaika"] ?? ""));
        $paivamaaraTarkistettu = DateTime::createFromFormat("!Y-m-d", $paivamaara);
        $paivamaaraKelvollinen = $paivamaaraTarkistettu
            && $paivamaaraTarkistettu->format("Y-m-d") === $paivamaara;

        if (!$luokkaId || $aihe === "" || $paivamaara === "" || $alkamisaika === "" || $loppumisaika === "") {
            $virhe = "Täytä kaikki varauksen kentät.";
        } elseif (mb_strlen($aihe) > 100) {
            $virhe = "Varauksen aihe on liian pitkä.";
        } elseif (!$paivamaaraKelvollinen) {
            $virhe = "Valitse kelvollinen päivämäärä.";
        } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $alkamisaika) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $loppumisaika)) {
            $virhe = "Anna alkamis- ja päättymisaika muodossa 14.00.";
        } elseif ($alkamisaika >= $loppumisaika) {
            $virhe = "Päättymisajan pitää olla alkamisajan jälkeen.";
        } else {
            $paivitys = $pdo->prepare(
                "UPDATE Varaukset
                 SET LuokkaID = :luokka_id, Aihe = :aihe, Paivamaara = :paivamaara,
                     Alkamisaika = :alkamisaika, Loppumisaika = :loppumisaika
                 WHERE $varausEhto"
            );
            $paivitysParametrit = [
                "luokka_id" => $luokkaId,
                "aihe" => $aihe,
                "paivamaara" => $paivamaara,
                "alkamisaika" => $alkamisaika,
                "loppumisaika" => $loppumisaika,
                "varaus_id" => $varausId,
            ];
            if (!$onYllapitaja) {
                $paivitysParametrit["kayttaja_id"] = $kayttajaId;
            }
            $paivitys->execute($paivitysParametrit);

            if ($paivitys->rowCount() > 0) {
                header("Location: poista_huone.php?muokattu=1");
                exit;
            }

            $omistajuus = $pdo->prepare(
                "SELECT 1 FROM Varaukset WHERE $varausEhto"
            );
            $omistajuusParametrit = ["varaus_id" => $varausId];
            if (!$onYllapitaja) {
                $omistajuusParametrit["kayttaja_id"] = $kayttajaId;
            }
            $omistajuus->execute($omistajuusParametrit);
            if (!$omistajuus->fetchColumn()) {
                $virhe = "Varausta ei löytynyt tai sinulla ei ole oikeutta muokata sitä.";
            } else {
                header("Location: poista_huone.php?muokattu=1");
                exit;
            }
        }

        if ($virhe !== "") {
            $muokattava = [
                "VarausID" => $varausId,
                "LuokkaID" => $luokkaId ?: "",
                "Aihe" => $aihe,
                "Paivamaara" => $paivamaara,
                "Alkamisaika" => $alkamisaika,
                "Loppumisaika" => $loppumisaika,
            ];
        }
    } elseif ($toiminto === "poista") {
        $poisto = $pdo->prepare(
            "DELETE FROM Varaukset WHERE $varausEhto"
        );
        $poistoParametrit = ["varaus_id" => $varausId];
        if (!$onYllapitaja) {
            $poistoParametrit["kayttaja_id"] = $kayttajaId;
        }
        $poisto->execute($poistoParametrit);

        if ($poisto->rowCount() > 0) {
            header("Location: poista_huone.php?poistettu=1");
            exit;
        }

        $virhe = "Varausta ei löytynyt tai sinulla ei ole oikeutta poistaa sitä.";
    } else {
        $virhe = "Tuntematon toiminto.";
    }
}

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$luokat = $pdo->query("SELECT LuokkaID, Nimi FROM Luokat ORDER BY Nimi")->fetchAll(PDO::FETCH_ASSOC);

if ($muokattava === null && isset($_GET["muokkaa"])) {
    $muokkaaId = filter_input(INPUT_GET, "muokkaa", FILTER_VALIDATE_INT);
    if ($muokkaaId) {
        $muokkausKysely = $pdo->prepare(
            "SELECT VarausID, LuokkaID, Paivamaara, Alkamisaika, Loppumisaika, Aihe
             FROM Varaukset WHERE $varausEhto"
        );
        $muokkausParametrit = ["varaus_id" => $muokkaaId];
        if (!$onYllapitaja) {
            $muokkausParametrit["kayttaja_id"] = $kayttajaId;
        }
        $muokkausKysely->execute($muokkausParametrit);
        $muokattava = $muokkausKysely->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($muokattava === null) {
            $virhe = "Varausta ei löytynyt tai sinulla ei ole oikeutta muokata sitä.";
        }
    }
}

$varausSql =
    "SELECT v.VarausID, v.KayttajaID, v.LuokkaID, l.Nimi AS LuokanNimi, v.Paivamaara,
            v.Alkamisaika, v.Loppumisaika, v.Aihe
     FROM Varaukset v
     LEFT JOIN Luokat l ON l.LuokkaID = v.LuokkaID
";
if (!$onYllapitaja) {
    $varausSql .= " WHERE v.KayttajaID = :kayttaja_id";
}
$varausSql .= " ORDER BY v.Paivamaara DESC, v.Alkamisaika DESC";
$varauskysely = $pdo->prepare($varausSql);
$varauskysely->execute($onYllapitaja ? [] : ["kayttaja_id" => $kayttajaId]);
$varaukset = $varauskysely->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
 
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
 
    <title>Huonevaraukset</title>
 
    <style>
        body {
            margin: 0;
            padding: 30px;
            background-color: #5485ce;
            font-family: Arial, sans-serif;
        }
 
        .sisalto {
            max-width: 800px;
            margin: 0 auto;
        }
 
        h1 {
            color: #243447;
        }
 
        .laatikko {
            margin-bottom: 25px;
            padding: 25px;
            background-color: white;
            border-radius: 8px;
        }
 
        label {
            display: block;
            margin-top: 12px;
            margin-bottom: 5px;
            font-weight: bold;
        }
 
        input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #b8c0cc;
            border-radius: 4px;
        }
 
        button {
            margin-top: 18px;
            padding: 10px 20px;
            color: white;
            background-color: #1769aa;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
 
        button:hover {
            background-color: #12558a;
        }
 
        .viesti {
            padding: 12px;
            margin-bottom: 20px;
            background-color: #e7f3ff;
            border-left: 5px solid #1769aa;
        }
 
        .virhe {
            padding: 12px;
            margin-bottom: 20px;
            background-color: #ffe7e7;
            border-left: 5px solid #b00020;
        }
 
        table {
            width: 100%;
            border-collapse: collapse;
        }
 
        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #dddddd;
            text-align: left;
        }
 
        th {
            color: white;
            background-color: #243447;
        }
 
        tr:hover {
            background-color: #f5f7fa;
        }
 
        .ei-tuotteita {
            color: #666666;
        }

        .toiminnot {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toiminnot form,
        .toiminnot a {
            margin: 0;
        }

        .toiminnot button {
            margin: 0;
        }
    </style>
</head>
 
<body>
 
<div class="sisalto">
 
    <h1><?= $onYllapitaja ? "Kaikkien huonevaraukset" : "Omat huonevaraukset" ?></h1>
 
    <?php if ($viesti !== ""): ?>
        <div class="viesti"><?= htmlspecialchars($viesti, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if ($virhe !== ""): ?>
        <div class="virhe"><?= htmlspecialchars($virhe, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if ($muokattava !== null): ?>
        <div class="laatikko">
            <h2>Muokkaa varausta</h2>
            <form method="post" action="poista_huone.php">
                <input type="hidden" name="toiminto" value="tallenna">
                <input type="hidden" name="varaus_id" value="<?= (int) $muokattava["VarausID"] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">

                <label for="luokka_id">Luokka tai huone</label>
                <select id="luokka_id" name="luokka_id" required>
                    <?php foreach ($luokat as $luokka): ?>
                        <option value="<?= (int) $luokka["LuokkaID"] ?>" <?= (int) $muokattava["LuokkaID"] === (int) $luokka["LuokkaID"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($luokka["Nimi"], ENT_QUOTES, "UTF-8") ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="aihe">Varauksen aihe</label>
                <input type="text" id="aihe" name="aihe" maxlength="100" value="<?= htmlspecialchars($muokattava["Aihe"], ENT_QUOTES, "UTF-8") ?>" required>

                <label for="paivamaara">Päivämäärä</label>
                <input type="date" id="paivamaara" name="paivamaara" value="<?= htmlspecialchars($muokattava["Paivamaara"], ENT_QUOTES, "UTF-8") ?>" required>

                <label for="alkamisaika">Alkamisaika</label>
                <input type="text" id="alkamisaika" name="alkamisaika" maxlength="5" placeholder="14.00" pattern="(?:[01][0-9]|2[0-3])[.:][0-5][0-9]" value="<?= htmlspecialchars(str_replace(":", ".", substr($muokattava["Alkamisaika"], 0, 5)), ENT_QUOTES, "UTF-8") ?>" required>

                <label for="loppumisaika">Päättymisaika</label>
                <input type="text" id="loppumisaika" name="loppumisaika" maxlength="5" placeholder="15.00" pattern="(?:[01][0-9]|2[0-3])[.:][0-5][0-9]" value="<?= htmlspecialchars(str_replace(":", ".", substr($muokattava["Loppumisaika"], 0, 5)), ENT_QUOTES, "UTF-8") ?>" required>

                <button type="submit">Tallenna muutokset</button>
                <a href="poista_huone.php">Peruuta</a>
            </form>
        </div>
    <?php elseif (isset($_GET["muokkaa"])): ?>
        <div class="virhe">Varausta ei löytynyt tai se ei kuulu sinulle.</div>
    <?php endif; ?>

    <div class="laatikko">
        <h2><?= $onYllapitaja ? "Varausten hallinta" : "Omat varaukset" ?></h2>

        <?php if ($varaukset === []): ?>
            <p><?= $onYllapitaja ? "Varauksia ei ole." : "Sinulla ei ole varauksia." ?></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Luokka/huone</th>
                        <th>Aihe</th>
                        <th>Päivämäärä</th>
                        <th>Aika</th>
                        <th>Toiminnot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($varaukset as $varaus): ?>
                        <tr>
                            <td><?= (int) $varaus["VarausID"] ?></td>
                            <td><?= htmlspecialchars($varaus["LuokanNimi"] ?? "Luokka " . $varaus["LuokkaID"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($varaus["Aihe"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($varaus["Paivamaara"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars(substr($varaus["Alkamisaika"], 0, 5) . " - " . substr($varaus["Loppumisaika"], 0, 5), ENT_QUOTES, "UTF-8") ?></td>
                            <td class="toiminnot">
                                <a href="poista_huone.php?muokkaa=<?= (int) $varaus["VarausID"] ?>">Muokkaa</a>
                                <form method="post" action="poista_huone.php" onsubmit="return confirm('Poistetaanko tämä varaus?');">
                                    <input type="hidden" name="toiminto" value="poista">
                                    <input type="hidden" name="varaus_id" value="<?= (int) $varaus["VarausID"] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
                                    <button type="submit">Poista</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p><a href="<?= $onYllapitaja ? "admin.php" : "yleis.php" ?>">Takaisin</a></p>
    </div>
</div>
 
</body>
</html>