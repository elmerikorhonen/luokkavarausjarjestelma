<?php
header("Content-Type: text/html; charset=UTF-8");
session_start();

if (!isset($_SESSION["kayttaja_id"])) {
    header("Location: kirjautuminen.php");
    exit;
}
require_once __DIR__ . "/back/db.php";
$roolikysely = $pdo->prepare("SELECT Rooli, Aktiivinen FROM Kayttajat WHERE KayttajaID = :id");
$roolikysely->execute(["id" => $_SESSION["kayttaja_id"]]);
$istunnonKayttaja = $roolikysely->fetch(PDO::FETCH_ASSOC);
if (!$istunnonKayttaja || !(int) $istunnonKayttaja["Aktiivinen"]) {
    session_unset();
    session_destroy();
    header("Location: kirjautuminen.php?passiivinen=1");
    exit;
}
$rooli = $istunnonKayttaja["Rooli"];
if ($rooli !== "Opettaja") {
    header("Location: yleis.php");
    exit;
}
$_SESSION["kayttajan_rooli"] = $rooli;
$kayttaja_id = $_SESSION["kayttaja_id"];
$apiOsoitevaraukset = "http://localhost/api.php?taulu=varaukset";
$apiOsoiteluokat= "http://localhost/api.php?taulu=luokat";

$viesti = isset($_GET["varattu"]) ? "Lisääminen onnistui" : "";
$viestiluokka = "viesti";
$hakuvirhe = "";
$luokka = "";
$paivamaara = "";
$alkamisaika = "";
$loppumisaika = "";
$aihe = "";
$varaukset = [];
$huoneViesti = isset($_GET["huone"]) && $_GET["huone"] === "lisatty"
    ? "Huone lisättiin."
    : (isset($_GET["huone"]) && $_GET["huone"] === "paivitetty" ? "Huoneen tiedot päivitettiin." : "");
$huoneVirhe = "";
$huoneForm = ["id" => "", "nimi" => "", "sijainti" => "", "kapasiteetti" => ""];

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["toiminto"] ?? "") === "huone_tallenna") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    $huoneId = filter_input(INPUT_POST, "huone_id", FILTER_VALIDATE_INT) ?: "";
    $huoneForm = [
        "id" => $huoneId,
        "nimi" => trim($_POST["huone_nimi"] ?? ""),
        "sijainti" => trim($_POST["huone_sijainti"] ?? ""),
        "kapasiteetti" => trim($_POST["huone_kapasiteetti"] ?? ""),
    ];
    $kapasiteetti = filter_var($huoneForm["kapasiteetti"], FILTER_VALIDATE_INT);

    if (!is_string($csrfToken) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $huoneVirhe = "Lomake vanheni. Päivitä sivu ja yritä uudelleen.";
    } elseif ($huoneForm["nimi"] === "" || $huoneForm["sijainti"] === "" || $huoneForm["kapasiteetti"] === "") {
        $huoneVirhe = "Täytä huoneen nimi, sijainti ja kapasiteetti.";
    } elseif (mb_strlen($huoneForm["nimi"]) > 100 || mb_strlen($huoneForm["sijainti"]) > 100) {
        $huoneVirhe = "Nimi ja sijainti voivat olla enintään 100 merkkiä.";
    } elseif ($kapasiteetti === false || $kapasiteetti < 1) {
        $huoneVirhe = "Kapasiteetin pitää olla positiivinen kokonaisluku.";
    } else {
        if ($huoneId !== "") {
            $paivitys = $pdo->prepare(
                "UPDATE Luokat
                 SET Nimi = :nimi, Sijainti = :sijainti, Kapasiteetti = :kapasiteetti,
                     Paivitetty = CURRENT_TIMESTAMP
                 WHERE LuokkaID = :id"
            );
            $paivitys->execute([
                "nimi" => $huoneForm["nimi"],
                "sijainti" => $huoneForm["sijainti"],
                "kapasiteetti" => $kapasiteetti,
                "id" => $huoneId,
            ]);
            if ($paivitys->rowCount() === 0) {
                $olemassaolo = $pdo->prepare("SELECT 1 FROM Luokat WHERE LuokkaID = :id");
                $olemassaolo->execute(["id" => $huoneId]);
                if (!$olemassaolo->fetchColumn()) {
                    $huoneVirhe = "Muokattavaa huonetta ei löytynyt.";
                }
            }
            $tila = "paivitetty";
        } else {
            $lisays = $pdo->prepare(
                "INSERT INTO Luokat (Nimi, Sijainti, Kapasiteetti)
                 VALUES (:nimi, :sijainti, :kapasiteetti)"
            );
            $lisays->execute([
                "nimi" => $huoneForm["nimi"],
                "sijainti" => $huoneForm["sijainti"],
                "kapasiteetti" => $kapasiteetti,
            ]);
            $tila = "lisatty";
        }

        if ($huoneVirhe === "") {
            header("Location: huoneet.php?huone=" . $tila . "#huonehallinta");
            exit;
        }
    }
} elseif (isset($_GET["muokkaa_huonetta"])) {
    $huoneId = filter_input(INPUT_GET, "muokkaa_huonetta", FILTER_VALIDATE_INT);
    if ($huoneId) {
        $huoneKysely = $pdo->prepare("SELECT LuokkaID, Nimi, Sijainti, Kapasiteetti FROM Luokat WHERE LuokkaID = :id");
        $huoneKysely->execute(["id" => $huoneId]);
        $huone = $huoneKysely->fetch(PDO::FETCH_ASSOC);
        if ($huone) {
            $huoneForm = [
                "id" => $huone["LuokkaID"],
                "nimi" => $huone["Nimi"],
                "sijainti" => $huone["Sijainti"],
                "kapasiteetti" => $huone["Kapasiteetti"],
            ];
        } else {
            $huoneVirhe = "Muokattavaa huonetta ei löytynyt.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["toiminto"] ?? "") !== "huone_tallenna") {
    $luokka = trim($_POST["luokka"] ?? "");
    $paivamaara = trim($_POST["paivamaara"] ?? "");
    $alkamisaika = str_replace(".", ":", trim($_POST["alkamisaika"] ?? ""));
    $loppumisaika = str_replace(".", ":", trim($_POST["loppumisaika"] ?? ""));
    $aihe = trim($_POST["aihe"] ?? "");

    $paivamaaraTarkistettu = DateTime::createFromFormat("!Y-m-d", $paivamaara);
    $paivamaaraKelvollinen = $paivamaaraTarkistettu
        && $paivamaaraTarkistettu->format("Y-m-d") === $paivamaara;

    if ($luokka === "" || $paivamaara === "" || $alkamisaika === "" || $loppumisaika === "" || $aihe === "") {
        $viesti = "Täytä kaikki varauksen kentät.";
        $viestiluokka = "virhe";
    } elseif (mb_strlen($luokka) > 50 || mb_strlen($aihe) > 100) {
        $viesti = "Luokan nimi tai varauksen aihe on liian pitkä.";
        $viestiluokka = "virhe";
    } elseif (!$paivamaaraKelvollinen) {
        $viesti = "Valitse kelvollinen päivämäärä.";
        $viestiluokka = "virhe";
    } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $alkamisaika) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $loppumisaika)) {
        $viesti = "Anna alkamis- ja päättymisaika 24 tunnin muodossa (HH:MM).";
        $viestiluokka = "virhe";
    } elseif ($alkamisaika >= $loppumisaika) {
        $viesti = "Päättymisajan pitää olla alkamisajan jälkeen.";
        $viestiluokka = "virhe";
    } else {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiOsoitevaraukset);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            json_encode([
                "kayttajaid" => $kayttaja_id,
                "luokkaid" => $luokka,
                "paivamaara" => $paivamaara,
                "alkamisaika" => $alkamisaika,
                "loppumisaika" => $loppumisaika,
                "aihe" => $aihe,
            ], JSON_UNESCAPED_UNICODE)
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $vastaus = curl_exec($curl);
        if ($vastaus === false) {
            $viesti = "cURL-virhe: " . curl_error($curl);
            $viestiluokka = "virhe";
        } else {
            $httpStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $vastausTaulukkona = json_decode($vastaus, true);
            $viesti = $vastausTaulukkona["viesti"]
                ?? "API ei palauttanut kelvollista viestiä (HTTP $httpStatus).";
            if ($httpStatus >= 400) {
                $viestiluokka = "virhe";
            } elseif ($httpStatus === 201) {
                header("Location: huoneet.php?varattu=1");
                exit;
            }
        }

        curl_close($curl);
    }
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $apiOsoitevaraukset);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$vastaus = curl_exec($curl);
if ($vastaus === false) {
    $hakuvirhe = curl_error($curl);
} else {
    $httpStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $varaukset = json_decode($vastaus, true);

    if ($httpStatus >= 400 || !is_array($varaukset) || isset($varaukset["onnistui"])) {
        $hakuvirhe = $varaukset["viesti"] ?? "API palautti virheellisen vastauksen (HTTP $httpStatus).";
        $varaukset = [];
    }
}

curl_close($curl);
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $apiOsoiteluokat);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$vastaus = curl_exec($curl);
if ($vastaus === false) {
    $hakuvirhe = curl_error($curl);
} else {
    $httpStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $luokat = json_decode($vastaus, true);

    if ($httpStatus >= 400 || !is_array($luokat) || isset($luokat["onnistui"])) {
        $hakuvirhe = $luokat["viesti"] ?? "API palautti virheellisen vastauksen (HTTP $httpStatus).";
        $luokat = [];
    }
}

curl_close($curl);

$hallintaHuoneet = $pdo->query(
    "SELECT LuokkaID, Nimi, Sijainti, Kapasiteetti FROM Luokat ORDER BY Nimi"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fi">
<head>
    
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
 
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
 
    <title>Huoneiden varaus</title>
 
    <style>
        body {
            display: block;
            margin: 0;
            padding: 30px;
            background-color: #4b77b9;
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
            border: 1px solid #b8bfcc;
            border-radius: 4px;
        }

        .kalenteri {
            width: min(100%, 380px);
            margin-top: 8px;
            padding: 16px;
            border: 1px solid #d3dee8;
            border-radius: 4px;
            background: #fff;
            box-shadow: none;
        }

        .kalenteri .otsikko {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin: 0 0 14px;
        }

        .kalenteri .otsikko h2 {
            margin: 0;
            font-size: 17px;
        }

        .kalenteri .otsikko button {
            min-width: 36px;
            margin: 0;
            padding: 6px 10px;
        }

        .viikonpaivat,
        .paivat {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 4px;
            text-align: center;
        }

        .viikonpaivat div {
            padding: 5px 0;
            color: #596777;
            font-size: 13px;
            font-weight: 700;
        }

        .paivat div {
            display: grid;
            min-height: 36px;
            place-items: center;
            padding: 4px;
            border-radius: 4px;
            cursor: pointer;
        }

        .paivat div:not(.tyhja):hover,
        .paivat div:not(.tyhja):focus-visible {
            background: #e7eef5;
            outline: 2px solid #365d81;
        }

        .paivat .tanaan {
            background: #365d81;
            color: #fff;
            font-weight: 700;
        }

        .paivat .valittu {
            outline: 2px solid #1769aa;
            font-weight: 700;
        }

        .paivat .tyhja {
            cursor: default;
        }

        #valittuInfo {
            margin: 10px 0 0;
            color: #596777;
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
            border-left: 5px solid #87b3d6;
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
            color: #5f5e5e;
        }

        .huonehallinta {
            scroll-margin-top: 20px;
        }

        .huonehallinta input[type="number"] {
            max-width: 220px;
        }

        .huonehallinta .toiminnot {
            white-space: nowrap;
        }

        .huonehallinta .toiminnot a {
            font-weight: 700;
        }

        .varausotsikko {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }

        .varausotsikko h2 {
            margin: 0;
        }

        .muokkaa-varauksia {
            display: inline-block;
            padding: 9px 13px;
            border-radius: 4px;
            background: #1769aa;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }

        .muokkaa-varauksia:hover,
        .muokkaa-varauksia:focus-visible {
            background: #12558a;
            color: #fff;
        }

 
        title {
            color: #243447;
        }
        a:link {
  color: blue;
  background-color: transparent;
  text-decoration: none;
}

a:visited {
  color: #87b3d6;
  background-color: transparent;
  text-decoration: none;
}

a:hover {
  color: blue;
  background-color: transparent;
  text-decoration: underline;
}

a:active {
  color: #2a455c;
  background-color: transparent;
  text-decoration: underline;
}
    </style>
</head>
 
<body>
 
<div class="sisalto">
 
    <h1>Huoneiden varaus</h1>
 
    <?php if ($viesti !== ""): ?>
 
        <div class="viesti">
            <?= htmlspecialchars($viesti) ?>
        </div>
 
    <?php endif; ?>
 
    <?php if ($hakuvirhe !== ""): ?>
 
        <div class="virhe">
            Varausten hakeminen epäonnistui:
            <?= htmlspecialchars($hakuvirhe) ?>
        </div>
 
    <?php endif; ?>
 
    <div class="laatikko">
 
        <h2>Varaa huone</h2>
        <form method="post" action="huoneet.php">
            <label for="luokka">Luokka tai huone</label>
            
                <select
                id="luokka"
                name="luokka">
                <?php foreach($luokat as $luokat2){
                    echo  '<option ';
                    if($luokka == $luokat2["Nimi"]){
                        echo ' selected ';
                    }
                    
                    echo 'value="' . (int) $luokat2["LuokkaID"] . '">'
                        . htmlspecialchars($luokat2["Nimi"], ENT_QUOTES, "UTF-8") . '</option>';
                }
                ?>
               
               </select>
            

            <label for="aihe">Varauksen aihe</label>
            <input
                type="text"
                id="aihe"
                name="aihe"
                maxlength="100"
                value="<?= htmlspecialchars($aihe, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="paivamaara_nakyma">Päivämäärä</label>
            <input
                type="hidden"
                id="paivamaara"
                name="paivamaara"
                value="<?= htmlspecialchars($paivamaara, ENT_QUOTES, "UTF-8") ?>"
            >
            <input
                type="text"
                id="paivamaara_nakyma"
                value=""
                placeholder="Valitse päivä kalenterista"
                readonly
                aria-describedby="valittuInfo"
            >
            <div class="kalenteri" id="kalenteri">
                <div class="otsikko">
                    <button type="button" id="edellinen" aria-label="Edellinen kuukausi">&lt;</button>
                    <h2 id="kuukausiVuosi"></h2>
                    <button type="button" id="seuraava" aria-label="Seuraava kuukausi">&gt;</button>
                </div>
                <div class="viikonpaivat" aria-hidden="true">
                    <div>Ma</div>
                    <div>Ti</div>
                    <div>Ke</div>
                    <div>To</div>
                    <div>Pe</div>
                    <div>La</div>
                    <div>Su</div>
                </div>
                <div class="paivat" id="paivatRuudukko" role="group" aria-label="Valitse varauspäivä"></div>
            </div>
            <p id="valittuInfo" aria-live="polite">Valitse päivä kalenterista.</p>

            <label for="alkamisaika">Alkamisaika</label>
            <input
                type="text"
                id="alkamisaika"
                name="alkamisaika"
                maxlength="5"
                placeholder="14.00"
                pattern="(?:[01][0-9]|2[0-3])[.:][0-5][0-9]"
                title="Esimerkiksi 14.00 tai 14:00"
                value="<?= htmlspecialchars(str_replace(":", ".", $alkamisaika), ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="loppumisaika">Päättymisaika</label>
            <input
                type="text"
                id="loppumisaika"
                name="loppumisaika"
                maxlength="5"
                placeholder="14.00"
                pattern="(?:[01][0-9]|2[0-3])[.:][0-5][0-9]"
                title="Esimerkiksi 14.00 tai 14:00"
                value="<?= htmlspecialchars(str_replace(":", ".", $loppumisaika), ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <button type="submit">Varaa</button>
        </form>
        <p><a href="admin.php">Takaisin hallintaan</a></p>
    </div>

    
 <div class="laatikko">
 
        <div class="varausotsikko">
            <h2>Tallennetut huonevaraukset</h2>
            <a class="muokkaa-varauksia" href="poista_huone.php">Muokkaa varauksia</a>
        </div>
 
        <?php if (count($varaukset) === 0): ?>
 
            <p class="ei-tuotteita">
                ei huonevarauksia tallennettu
            </p>
 
        <?php else: ?>
 
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
                        
 
                        <td>
                            <?= htmlspecialchars($varaus["LuokkaID"], ENT_QUOTES, "UTF-8") ?>
                        </td>
 
                        <td>
                            <?= htmlspecialchars($varaus["Aihe"], ENT_QUOTES, "UTF-8") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($varaus["Paivamaara"], ENT_QUOTES, "UTF-8") ?>
                        </td>
                        
                        <td>
                            <?= htmlspecialchars(substr($varaus["Alkamisaika"], 0, 5), ENT_QUOTES, "UTF-8") ?>
                            -
                            <?= htmlspecialchars(substr($varaus["Loppumisaika"], 0, 5), ENT_QUOTES, "UTF-8") ?>
                        </td>

                        

                    </tr>
                   
                <?php endforeach; ?>
 
                </tbody>
 
            </table>
 
        <?php endif; ?>
 
    </div>

    <div class="laatikko huonehallinta" id="huonehallinta">
        <h2>Huoneiden hallinta</h2>

        <?php if ($huoneViesti !== ""): ?>
            <div class="viesti" role="status"><?= htmlspecialchars($huoneViesti, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <?php if ($huoneVirhe !== ""): ?>
            <div class="virhe" role="alert"><?= htmlspecialchars($huoneVirhe, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <?php if ($hallintaHuoneet === []): ?>
            <p class="ei-tuotteita">Huoneita ei ole vielä lisätty.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Huone</th>
                        <th>Sijainti</th>
                        <th>Kapasiteetti</th>
                        <th>Toiminto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hallintaHuoneet as $hallintaHuone): ?>
                        <tr>
                            <td><?= htmlspecialchars($hallintaHuone["Nimi"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($hallintaHuone["Sijainti"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= (int) $hallintaHuone["Kapasiteetti"] ?></td>
                            <td class="toiminnot">
                                <a href="huoneet.php?muokkaa_huonetta=<?= (int) $hallintaHuone["LuokkaID"] ?>#huonehallinta">Muokkaa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2><?= $huoneForm["id"] !== "" ? "Muokkaa huonetta" : "Lisää huone" ?></h2>
        <form method="post" action="huoneet.php#huonehallinta">
            <input type="hidden" name="toiminto" value="huone_tallenna">
            <input type="hidden" name="huone_id" value="<?= htmlspecialchars((string) $huoneForm["id"], ENT_QUOTES, "UTF-8") ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">

            <label for="huone_nimi">Huoneen nimi</label>
            <input type="text" id="huone_nimi" name="huone_nimi" maxlength="100" value="<?= htmlspecialchars($huoneForm["nimi"], ENT_QUOTES, "UTF-8") ?>" required>

            <label for="huone_sijainti">Sijainti</label>
            <input type="text" id="huone_sijainti" name="huone_sijainti" maxlength="100" value="<?= htmlspecialchars($huoneForm["sijainti"], ENT_QUOTES, "UTF-8") ?>" required>

            <label for="huone_kapasiteetti">Kapasiteetti</label>
            <input type="number" id="huone_kapasiteetti" name="huone_kapasiteetti" min="1" step="1" value="<?= htmlspecialchars((string) $huoneForm["kapasiteetti"], ENT_QUOTES, "UTF-8") ?>" required>

            <button type="submit"><?= $huoneForm["id"] !== "" ? "Tallenna muutokset" : "Lisää huone" ?></button>
            <?php if ($huoneForm["id"] !== ""): ?>
                <a href="huoneet.php#huonehallinta">Peruuta</a>
            <?php endif; ?>
        </form>
    </div>
    
 
</div>
<script src="script.js?v=2"></script>
</body>
</html>