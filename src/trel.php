
<?php
 
$apiOsoite = $apiOsoite = "http://localhost/trel.php";
$viesti = "";
 
 
// Kun lomake lähetetään
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $nimi = trim($_POST["nimi"] ?? "");
    $maa = $_POST["maa"] ?? "";
    $nimi2 = $_POST["nimi2"] ?? "";
    $paivamaara = trim($_POST["paivamaara"] ?? "");
 
    if ($nimi === "" || $maa === "" || $nimi2 === "" || $paivamaara === "") {
 
        $viesti = "Täytä tapahtuman tiedot";
 
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

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #F4F4F4
        }
 
        header {
            background-color: #365d81;
            color: #fff;
            padding: 10px 20px;
            text-align: center;
            display: block;
            justify-content: space-between;
        }
 
        h1 {
            margin: 0;
        }

        

        .sidenav {
  height: 100%; /* Full-height: remove this if you want "auto" height */
  width: 160px; /* Set the width of the sidebar */
  position: fixed; /* Fixed Sidebar (stay in place on scroll) */
  z-index: 1; /* Stay on top */
  top: 0; /* Stay at the top */
  left: 0;
  background-color: #365d81; /* Black */
  overflow-x: hidden; 
  padding-top: 50px;
}


.sidenav a {
  padding: 6px 8px 6px 8px;
  text-decoration: none;
  font-size: 25px;
  color: #818181;
  display: block;
}


.sidenav a:hover {
  color: #f1f1f1;
}


.main {
  margin-left: 160px; 
  padding: 0px 10px;
}


@media screen and (max-height: 450px) {
  .sidenav {padding-top: 10px;}
  .sidenav a {font-size: 18px;}
}
table {
  border-collapse: collapse;
  width: 100%;
  marhgin-top: 20px;
  background-color: #f2f4f7;
  margin: 100px auto;
}

th, td {
  padding: 8px;
  text-align: left;
  border-bottom: 1px solid #DDD;
}


table {
  border-collapse: collapse;
  width: 100%;
  marhgin-top: 20px;
  background-color: #f2f4f7;
  margin: 100px auto;
}


tr:hover {background-color: #D6EEEE;}
</style>
</head>
<body>






        </style>
    </head>
 
<body>

<header>
  <h1>Admin</h1>
  
</header>



<div class="sidenav">
  <a href="Huoneet.php">Huoneet</a>
  <a href="Varaukset.php">Varaukset</a>
  <a href="kayttajat.php">Käyttäjät</a>
  <a href="Raportit.php">Raportit</a>
</div>

<!-- Page content -->
<div class="main">
  ...
</div>




<table style="width:50%;">
    
  </table>
<table style="width:50%;">
    
  <tr>
     
    <th>Varaukset</th>
    
</table>
<table style="width:50%;">
    
  <tr>
     
    
    <th>Nimi</th>
    <th>Maa</th>
    <th>Huoneet</th>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td></td>
    </tr>
</table>

    </body>
</html>
    