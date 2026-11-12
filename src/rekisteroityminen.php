<?php
$viesti = "";
$viestiluokka = "viesti";
$etunimi = "";
$sukunimi = "";
$sahkoposti = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $etunimi = trim($_POST["etunimi"] ?? "");
    $sukunimi = trim($_POST["sukunimi"] ?? "");
    $sahkoposti = trim($_POST["sahkoposti"] ?? "");
    $salasana = $_POST["salasana"] ?? "";
    $salasanaUudelleen = $_POST["salasana_uudelleen"] ?? "";

    if ($etunimi === "" || $sukunimi === "" || $sahkoposti === "" || $salasana === "" || $salasanaUudelleen === "") {
        $viesti = "Täytä kaikki kentät.";
        $viestiluokka = "virhe";
    } elseif (mb_strlen($etunimi) > 100 || mb_strlen($sukunimi) > 100 || mb_strlen($sahkoposti) > 100) {
        $viesti = "Nimi tai sähköpostiosoite on liian pitkä.";
        $viestiluokka = "virhe";
    } elseif (!filter_var($sahkoposti, FILTER_VALIDATE_EMAIL)) {
        $viesti = "Anna kelvollinen sähköpostiosoite.";
        $viestiluokka = "virhe";
    } elseif (strlen($salasana) < 8) {
        $viesti = "Salasanan pitää olla vähintään 8 merkkiä.";
        $viestiluokka = "virhe";
    } elseif ($salasana !== $salasanaUudelleen) {
        $viesti = "Salasanat eivät täsmää.";
        $viestiluokka = "virhe";
    } else {
        try {
            require_once __DIR__ . "/back/db.php";

            $kysely = $pdo->prepare(
                "INSERT INTO Kayttajat (Etunimi, Sukunimi, Sahkoposti, Salasana_hash, Rooli)
                 VALUES (:etunimi, :sukunimi, :sahkoposti, :salasana_hash, :rooli)"
            );
            $kysely->execute([
                "etunimi" => $etunimi,
                "sukunimi" => $sukunimi,
                "sahkoposti" => $sahkoposti,
                "salasana_hash" => password_hash($salasana, PASSWORD_DEFAULT),
                "rooli" => "kayttaja",
            ]);

            $viesti = "Rekisteröityminen onnistui. Voit nyt kirjautua sisään.";
            $etunimi = "";
            $sukunimi = "";
            $sahkoposti = "";
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $viesti = "Tällä sähköpostiosoitteella on jo rekisteröity käyttäjä.";
            } else {
                error_log($e->getMessage());
                $viesti = "Rekisteröityminen ei onnistunut tietokantavirheen vuoksi.";
            }
            $viestiluokka = "virhe";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
 
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
 
    <title>Rekisteröityminen</title>
 
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
 
    <h1>Luokkien varaus</h1>
 
    <?php if ($viesti !== ""): ?>
 
        <div class="<?= $viestiluokka ?>">
            <?= htmlspecialchars($viesti) ?>
        </div>
 
    <?php endif; ?>
 
    <div class="laatikko">
 
        <h2>Rekisteröidy</h2>
 
        <form method="post" action="rekisteroityminen.php">
 
            <label for="etunimi">
                Etunimi
            </label>
 
            <input
                type="text"
                id="etunimi"
                name="etunimi"
                maxlength="100"
                value="<?= htmlspecialchars($etunimi, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="sukunimi">
                Sukunimi
            </label>

            <input
                type="text"
                id="sukunimi"
                name="sukunimi"
                maxlength="100"
                value="<?= htmlspecialchars($sukunimi, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="sahkoposti">
                Sähköposti
            </label>

            <input
                type="email"
                id="sahkoposti"
                name="sahkoposti"
                maxlength="100"
                value="<?= htmlspecialchars($sahkoposti, ENT_QUOTES, "UTF-8") ?>"
                required
            >
            
            <label for="salasana">
                Salasana
            </label>

            <input
                type="password"
                id="salasana"
                name="salasana"
                minlength="8"
                required
            >
            
            
            <label for="salasana_uudelleen">
                Varmista salasana
            </label>

            <input
                type="password"
                id="salasana_uudelleen"
                name="salasana_uudelleen"
                minlength="8"
                required
            >

            

            
 
            <button type="submit">
                Rekisteröidy
            </button>
 
        </form>


        <form method="post" action="kirjautuminen.php">
 
            <button type="submit">
                Takaisin
            </button>
 </form>
    </div>
 
    
 
    
 
</div>
 
</body>
</html>