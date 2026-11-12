<?php
session_start();

if (!isset($_SESSION["kayttaja_id"])) {
    header("Location: kirjautuminen.php");
    exit;
}
$kayttaja_id = $_SESSION["kayttaja_id"];
$apiOsoitevaraukset = "http://localhost/api.php?taulu=varaukset";
$apiOsoiteluokat= "http://localhost/api.php?taulu=luokat";

$viesti = "";
$viestiluokka = "viesti";
$hakuvirhe = "";
$luokka = "";
$paivamaara = "";
$alkamisaika = "";
$loppumisaika = "";
$aihe = "";
$varaukset = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $luokka = trim($_POST["luokka"] ?? "");
    $paivamaara = trim($_POST["paivamaara"] ?? "");
    $alkamisaika = trim($_POST["alkamisaika"] ?? "");
    $loppumisaika = trim($_POST["loppumisaika"] ?? "");
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
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $alkamisaika) || !preg_match('/^\d{2}:\d{2}$/', $loppumisaika)) {
        $viesti = "Valitse kelvollinen alkamis- ja päättymisaika.";
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

?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
 
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
 
    <title>Huoneiden varaus</title>
 
    <style>
        body {
            margin: 0;
            padding: 30px;
            background-color: #f2f4f7;
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
                    
                    echo 'value="'. $luokat2["LuokkaID"]. '">'.$luokat2["Nimi"]. '</option>';
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

            <label for="paivamaara">
                Päivämäärä
            </label>
            <input
                type="date"
                id="paivamaara"
                name="paivamaara"
                value="<?= htmlspecialchars($paivamaara, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="alkamisaika">Alkamisaika</label>
            <input
                type="time"
                id="alkamisaika"
                name="alkamisaika"
                value="<?= htmlspecialchars($alkamisaika, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="loppumisaika">Päättymisaika</label>
            <input
                type="time"
                id="loppumisaika"
                name="loppumisaika"
                value="<?= htmlspecialchars($loppumisaika, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <button type="submit">Varaa</button>
        </form>
        <p><a href="admin.php">Takaisin hallintaan</a></p>
    </div>

    
 <div class="laatikko">
 
        <h2>Tallennetut huonevaraukset</h2>
 
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
    
 
</div>
 
</body>
</html>