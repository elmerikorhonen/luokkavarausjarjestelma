<?php
session_start();

if (isset($_SESSION["kayttaja_id"])) {
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
    $_SESSION["kayttajan_rooli"] = $istunnonKayttaja["Rooli"];
    header("Location: " . (($_SESSION["kayttajan_rooli"] ?? "") === "Opettaja" ? "admin.php" : "yleis.php"));
    exit;
}

$viesti = "";
if (isset($_GET["rekisteroityminen"]) && $_GET["rekisteroityminen"] === "onnistui") {
    $viesti = "Rekisteröityminen onnistui. Kirjaudu sisään.";
} elseif (isset($_GET["ulos"])) {
    $viesti = "Kirjauduit ulos.";
} elseif (isset($_GET["passiivinen"])) {
    $viesti = "Käyttäjätili ei ole aktiivinen. Ota yhteyttä ylläpitäjään.";
}
$viestiluokka = "viesti";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sahkoposti = $_POST["sahkoposti"] ?? "";
    $salasana = $_POST["salasana"] ?? "";

    if (!is_string($sahkoposti) || !is_string($salasana)) {
        $viesti = "Anna sähköposti ja salasana.";
        $viestiluokka = "virhe";
    } else {
        $sahkoposti = trim($sahkoposti);

        if ($sahkoposti === "" || $salasana === "" || !filter_var($sahkoposti, FILTER_VALIDATE_EMAIL)) {
            $viesti = "Sähköposti tai salasana on väärä.";
            $viestiluokka = "virhe";
        } else {
            try {
                require_once __DIR__ . "/back/db.php";

                $kysely = $pdo->prepare(
                    "SELECT KayttajaID, Etunimi, Sukunimi, Sahkoposti, Salasana_hash, Rooli, Aktiivinen
                     FROM Kayttajat
                     WHERE Sahkoposti = :sahkoposti
                     LIMIT 1"
                );
                $kysely->execute(["sahkoposti" => $sahkoposti]);
                $kayttaja = $kysely->fetch(PDO::FETCH_ASSOC);

                if (!$kayttaja || !(int) $kayttaja["Aktiivinen"]
                    || !password_verify($salasana, $kayttaja["Salasana_hash"] ?? "")) {
                    $viesti = "Sähköposti tai salasana on väärä.";
                    $viestiluokka = "virhe";
                } else {
                    $kirjautumisKysely = $pdo->prepare(
                        "INSERT INTO Kirjautumiset (KayttajaID) VALUES (:kayttaja_id)"
                    );
                    $kirjautumisKysely->execute([
                        "kayttaja_id" => $kayttaja["KayttajaID"],
                    ]);

                    session_regenerate_id(true);
                    $_SESSION["kayttaja_id"] = (int) $kayttaja["KayttajaID"];
                    $_SESSION["kirjautuminen_id"] = (int) $pdo->lastInsertId();
                    $_SESSION["kayttajan_nimi"] = trim($kayttaja["Etunimi"] . " " . $kayttaja["Sukunimi"]);
                    $_SESSION["kayttajan_rooli"] = $kayttaja["Rooli"];

                    header("Location: " . ($kayttaja["Rooli"] === "Opettaja" ? "admin.php" : "yleis.php"));
                    exit;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $viesti = "Kirjautuminen ei onnistunut tietokantavirheen vuoksi.";
                $viestiluokka = "virhe";
            }
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
 
    <title>Huoneiden varaus</title>
 
    <style>
        body {
            margin: 0;
            padding: 30px;
            background-color: #5d7fb3;
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
  color: green;
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
 
    <h1>Luokkien varaus</h1>
 
    <?php if ($viesti !== ""): ?>
 
        <div class="<?= $viestiluokka ?>">
            <?= htmlspecialchars($viesti) ?>
        </div>
 
    <?php endif; ?>
 
    <div class="laatikko">
 
        <h2>Kirjaudu sisään
        </h2>
 
        <form method="post" action="kirjautuminen.php">
 
            <label for="sahkoposti">
                Sähköposti
            </label>
 
            <input
                type="email"
                id="sahkoposti"
                name="sahkoposti"
                required
            >

            
            
            <label for="salasana">
                Salasana
            </label>

            <input
                type="password"
                id="salasana"
                name="salasana"
                required
            >
            <p></p>
            <a href="salasana.php">Unohditko salasanan?</a>
            
            <p></p>

            <p></p>
            <a href="rekisteroityminen.php">Rekisteröidy</a>
            
            <p></p>


            
 
            <button type="submit">
                Kirjaudu
            </button>
 
        </form>
 
    </div>
 
    
 
    
 
</div>
 
</body>
</html>