<?php
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
if ($istunnonKayttaja["Rooli"] !== "Opettaja") {
    header("Location: huoneet.php");
    exit;
}

$apiOsoite = $apiOsoite = "http://localhost/admin.php";
$viesti = "";
 
 
// Kun lomake lähetetään
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $nimi = trim($_POST["nimi"] ?? "");
    $maa = $_POST["maa"] ?? "";
    $nimi2 = $_POST["nimi2"] ?? "";
    
    $kalenteri = $_POST["kalenteri"] ?? "";
 
    if ($nimi === "" || $maa === "" && "0" || $nimi2 === "" || $kalenteri === "") {
 
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
                "kalenteri" => $kalenteri

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
     <link rel="stylesheet" href="style.css">

    <meta charset="UTF-8">
 
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
 
    <title>Huoneiden varaus</title>
 
    <style>
        

    </style>
</head>
 
<body>
 
<div class="sisalto">
 
    <h1></h1>
 
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
 
        <h2></h2>
 
        
 
            <table style="width:50%;">
    
  </table>
<table style="width:50%;">
    

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
            
            

   
            <form method="post" action="varaukset.php">
 
            <button type="submit">
                Tallenna tiedot
            </button>
            </form>
    
</form>

<p></P>
         
 

 </form>
 <p></P>
 
    </div>

    
 <div class="laatikko">
 
        <h2>Tallennetut tiedot  </h2>
 
        <?php if (count($tuotteet) === 0): ?>
 
            <p class="ei-tuotteita">
                ei tietoja tallennettu
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
                            <?= htmlspecialchars($tuote["kalenteri"]) ?>
                        </td>

                    </tr>
                   
                <?php endforeach; ?>
 
                </tbody>
 
            </table>
 
        <?php endif; ?>
 
    </div>


  <tr>
     
    
    
</table>
<table style="width:50%;">
    
  

    </body>
</html>
            


            
 
            
 
        </form>
 </form>


        <form method="post" action="admin.php">
 
            <button type="submit">
                Takaisin
            </button>
 </form>
    </div>
 
    
 
    
 
</div>
 
<p></p>



         

  <form method="post" action="admin.php">
 
            <button type="submit">
                Takaisin
            </button>
</body>
</html>