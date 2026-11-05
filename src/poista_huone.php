<?php
 
$apiOsoite = "http://localhost/huoneet.php";
$viesti = "";
$id = $_GET["id"] ?? "";
$apiOsoite2 = "http://localhost/huoneet.php?id=$id";
 
// Kun lomake lähetetään
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $id = trim($_POST["id"] ?? "");
    
    
 
    if ($id === "") {
 
        $viesti = "poista varausten tiedot";
 
    } else {
 
        // Aloitetaan cURL-pyyntö
        $curl = curl_init();
 
        // Asetetaan API:n osoite
        curl_setopt(
            $curl,
            CURLOPT_URL,
            $apiOsoite . "?id=" . rawurlencode($id)
        );
 
        // Pyyntö on DELETE-pyyntö
        curl_setopt(
            $curl,
            CURLOPT_CUSTOMREQUEST,
            "DELETE"
        );
 
        // API:n vastaus tallennetaan muuttujaan
        curl_setopt(
            $curl,
            CURLOPT_RETURNTRANSFER,
            true
        );
 
        // Suoritetaan pyyntö
        $vastaus = curl_exec($curl);
 
        // Tarkistetaan, tapahtuiko cURL-virhe
        if ($vastaus === false) {
 
            $viesti = "cURL-virhe: " . curl_error($curl);
 
        } else {
 
            $vastausTaulukkona = json_decode(
                $vastaus,
                true
            );
 
            $viesti = $vastausTaulukkona["viesti"]
                ?? "API ei palauttanut viestiä.";
        }
 
        // Suljetaan cURL-yhteys
        curl_close($curl); 
        header ("Location: indexhm.php");
    }
}
 
 
// Haetaan kaikki tuotteet API:sta
$curl = curl_init();
 
curl_setopt(
    $curl,
    CURLOPT_URL,
    $apiOsoite2
);
 
curl_setopt(
    $curl,
    CURLOPT_RETURNTRANSFER,
    true
);
 
$vastaus = curl_exec($curl);
 
if ($vastaus === false) {
 
    $tuotteet = [];
    $hakuvirhe = curl_error($curl);
 
} else {
 
    $tuotteet = json_decode(
        $vastaus,
        true
    );
 
    $hakuvirhe = "";
 
    if (!is_array($tuotteet)) {
        $tuotteet = [];
    }
}
print_r($tuotteet);
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
 
    <title>Huonevaraukset</title>
 
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
    </style>
</head>
 
<body>
 
<div class="sisalto">
 
    <h1>Huonevaraukset</h1>
 
    <?php if ($viesti !== ""): ?>
 
        <div class="viesti">
            <?= htmlspecialchars($viesti) ?>
        </div>
 
    <?php endif; ?>
 
    <?php if ($hakuvirhe !== ""): ?>
 
        <div class="virhe">
            Huonevarauksien poistaminen epäonnistui: <br>
            <?= htmlspecialchars($hakuvirhe) ?>
        </div>
 
    <?php endif; ?>
 
    <div class="laatikko">
 
        <h2>Poista varaus   </h2>
 
        <form method="post" action="poista_huone.php">
 
            <label for="nimi">
                Nimi
            </label>
 
            <input
                type="text"
                id="nimi    "
                name="nimi"
                value="<?= htmlspecialchars($tuotteet["nimi"] ?? "") ?>"
                required
            >

            <label for="maa">
                Maa
            </label>
 
            <input
                type="text"
                id="maa"
                name="maa"
                value="<?= htmlspecialchars($tuotteet["maa"] ?? "") ?>"
                required
            >
            
            <label for="nimi2">
                Huone Nro
            </label>

            <input
                type="number"
                id="nimi2"
                name="nimi2"
                value="<?= htmlspecialchars($tuotteet["nimi2"] ?? "") ?>"
                required
            >
            <label for="paivamaara">
                Päivämäärä
            </label>
            
            <input
                type="date"
                id="paivamaara"
                name="paivamaara"
                value="<?= htmlspecialchars($tuotteet["paivamaara"] ?? "") ?>"
                required
            >

            

            
 
            <button type="submit">
                Poista varaus
            </button>
 
        </form>
 <form method="post" action="huoneet.php">
            <button type="submit">
                Takaisin
            </button>
    </div>
 
    
 
</div>
 
</body>
</html>