<?php
session_start();

if (!isset($_SESSION["yleis_tekstit"]) || !is_array($_SESSION["yleis_tekstit"])) {
    $_SESSION["yleis_tekstit"] = [];
}

$viesti = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nimi = $_POST["nimi"] ?? "";

    if (!is_string($nimi) || trim($nimi) === "") {
        $viesti = "Kirjoita teksti ennen lähettämistä.";
    } else {
        $_SESSION["yleis_tekstit"][] = trim($nimi);
        header("Location: yleis.php");
        exit;
    }
}

$tuotteet = $_SESSION["yleis_tekstit"];
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luokkavaraus</title>
    <style>
        body {
            margin: 0;
            background-color: #f4f4f4;
            color: #222;
            font-family: Arial, sans-serif;
        }

        header {
            padding: 10px 20px;
            background-color: #365d81;
            color: #fff;
            text-align: center;
        }

        h1 {
            margin: 0;
        }

        .sidenav {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1;
            width: 160px;
            height: 100%;
            padding-top: 50px;
            overflow-x: hidden;
            background-color: #365d81;
        }

        .sidenav a {
            display: block;
            padding: 6px 8px;
            color: #ccc7c7;
            font-size: 20px;
            text-decoration: none;
        }

        .sidenav a:hover {
            color: #fff;
        }

        main {
            max-width: 800px;
            margin: 0 auto 0 160px;
            padding: 20px;
        }

        .laatikko {
            margin-bottom: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #b8c0cc;
            border-radius: 4px;
        }

        button {
            margin-top: 12px;
            padding: 10px 20px;
            border: 0;
            border-radius: 4px;
            background: #1769aa;
            color: #fff;
            cursor: pointer;
        }

        .viesti {
            margin-bottom: 16px;
            color: #8b0000;
        }

        .ei-tuotteita {
            color: #5f5e5e;
        }

        @media screen and (max-width: 600px) {
            .sidenav {
                position: static;
                width: auto;
                height: auto;
                padding: 0;
            }

            main {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Tervetuloa</h1>
    </header>

    <nav class="sidenav" aria-label="Päänavigaatio">
        <a href="varaa.php">Varaa huone</a>
        <a href="profiili.php">Profiili</a>
    </nav>

    <main>
        <section class="laatikko">
            <h2>Kirjoita teksti</h2>

            <?php if ($viesti !== ""): ?>
                <p class="viesti"><?= htmlspecialchars($viesti, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <form method="post" action="yleis.php">
                <label for="nimi">Kirjoita lomakkeeseen</label>
                <input type="text" id="nimi" name="nimi" required>
                <button type="submit">Lähetä</button>
            </form>
        </section>

        <section class="laatikko">
            <h2>Tallennetut tekstit</h2>

            <?php if ($tuotteet === []): ?>
                <p class="ei-tuotteita">Ei kirjoitettua tekstiä.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($tuotteet as $tuote): ?>
                        <li><?= htmlspecialchars($tuote, ENT_QUOTES, "UTF-8") ?></li>
                    <?php endforeach; ?>
                </ul> 
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
