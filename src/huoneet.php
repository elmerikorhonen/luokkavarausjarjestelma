<?php
 
$apiOsoite = "http://localhost/trel.php";
$viesti = "";
 
 
// Kun lomake lähetetään
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $nimi = trim($_POST["nimi"] ?? "");
    $maa = $_POST["maa"] ?? "";
    $nimi2 = $_POST["nimi2"] ?? "";
    $paivamaara = trim($_POST["paivamaara"] ?? "");
 
    if ($nimi === "" || $maa === "" || $nimi2 === "" || $paivamaara === "") {
 
        $viesti = "Täytä tiedot";
 
    } else {
 
        // Aloitetaan cURL-pyyntö
        $curl = curl_init();
 
        // Asetetaan API:n osoite
        curl_setopt(
            $curl,
            CURLOPT_URL,
            $apiOsoite
        );
 
        // Pyyntö on POST-pyyntö
        curl_setopt(
            $curl,
            CURLOPT_POST,
            true
        );
 
        // Lähetettävät tiedot
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            [
                "nimi" => $nimi,
                "maa" => $maa,
                "nimi2" => $nimi2,
                "paivamaara" => $paivamaara

            ]
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
    }
}
 
 
// Haetaan kaikki tuotteet API:sta
$curl = curl_init();
 
curl_setopt(
    $curl,
    CURLOPT_URL,
    $apiOsoite
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
            Tuotteiden hakeminen epäonnistui:
            <?= htmlspecialchars($hakuvirhe) ?>
        </div>
 
    <?php endif; ?>
 
    <div class="laatikko">
 
        <h2>Varaa huone</h2>
             <form action="trel.php" method="get" target="_blank">
            <form action="varaukset.php" method="get" target="_blank">
        <form action="kayttajat.php" method="get" target="_blank">   
            <label for="nimi">
                Nimi
            </label>
 
            <input
                type="text"
                id="nimi"
                name="nimi"
                required
            >

            <label for="maa">
                Maa
            </label>
 
            <input
                type="text"
                id="maa"
                name="maa"
                required
            >
        </form>

            <label for="nimi2">
                Huone Nro
            </label>
         </form>
            <input
                type="number"
                id="nimi2"
                name="nimi2"
                required
            >
           </form> 
            <label for="paivamaara">
                Päivämäärä
                
            </label>

            <input
                type="date"
                id="paivamaara"
                name="paivamaara"
                
                
                required
            >
            

   
            <form method="post" action="huoneet.php">
 
            <button type="submit">
                Varaa
            </button>
            </form>
    
</form>


        
 
<form method="post" action="trel.php">
            <button type="submit">
                Takaisin
            </button>
 </form>
 <p></P>
 <a href="poista_huone.php">Poista varaus</a>
    </div>

    
 <div class="laatikko">
 
        <h2>Tallennetut huonevaraukset</h2>
 
        <?php if (count($tuotteet) === 0): ?>
 
            <p class="ei-tuotteita">
                ei huonevarauksia tallennettu
            </p>
 
        <?php else: ?>
 
            <table>
 
                <thead>
                    <tr>
                        <th>Nimi</th>
                        <th>Maa</th>
                        <th>Huone Nro</th>
                        
                        <th>Päivämäärä</th>
                        
                    </tr>
                </thead>
 
                <tbody>
 
                <?php foreach ($tuotteet as $tuote): ?>
 
                    <tr>
                        
 
                        <td>
                            <?= htmlspecialchars($tuote["nimi"]) ?>
                        </td>
 
                        <td>
                            <?= htmlspecialchars($tuote["maa"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($tuote["nimi2"]) ?>
                        </td>
                        
                        <td>
                            <?= 
                                $tuote["paivamaara"],
                                
                                
                                " "

                        

                             ?> 
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