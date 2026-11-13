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
$_SESSION["kayttajan_rooli"] = $istunnonKayttaja["Rooli"];
if ($_SESSION["kayttajan_rooli"] !== "Opettaja") {
    header("Location: huoneet.php");
    exit;
}
$varaukset = $pdo->query(
    "SELECT v.VarausID, v.Aihe, v.Paivamaara, v.Alkamisaika, v.Loppumisaika,
            l.Nimi AS LuokanNimi, k.Etunimi, k.Sukunimi
     FROM Varaukset v
     LEFT JOIN Luokat l ON l.LuokkaID = v.LuokkaID
     LEFT JOIN Kayttajat k ON k.KayttajaID = v.KayttajaID
     ORDER BY v.Paivamaara DESC, v.Alkamisaika DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hallinta | Luokkien varaus</title>
    <style>
        :root {
            --blue: #365d81;
            --blue-dark: #243f59;
            --blue-soft: #e7eef5;
            --paper: #ffffff;
            --ink: #263746;
            --muted: #627384;
            --line: #d3dee8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--blue-soft);
            color: var(--ink);
            font-family: Arial, sans-serif;
        }

        .ylapalkki {
            width: 100%;
            background: var(--blue);
            color: var(--paper);
            box-shadow: 0 3px 12px rgb(23 45 67 / 18%);
        }

        .ylapalkin-sisalto {
            display: flex;
            width: min(100% - 48px, 1060px);
            min-height: 86px;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin: 0 auto;
        }

        h1 {
            margin: 0;
            font-size: 27px;
            font-weight: 700;
        }

        .kayttaja {
            display: flex;
            align-items: center;
            gap: 18px;
            margin: 0;
            color: #e5edf4;
            font-size: 14px;
        }

        .ulos {
            padding: 9px 12px;
            border: 1px solid rgb(255 255 255 / 55%);
            color: var(--paper);
            text-decoration: none;
            transition: background-color 140ms ease;
        }

        .ulos:hover,
        .ulos:focus-visible {
            background: var(--blue-dark);
        }

        main {
            width: min(100% - 48px, 1060px);
            margin: 38px auto;
        }

        .otsikko {
            margin: 0 0 18px;
            color: var(--blue-dark);
            font-size: 21px;
        }

        .osiot {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .osio {
            display: flex;
            min-height: 94px;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px;
            border: 1px solid var(--line);
            border-left: 5px solid var(--blue);
            background: var(--paper);
            color: var(--blue-dark);
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            transition: border-color 140ms ease, background-color 140ms ease, transform 140ms ease;
        }

        .osio::after {
            content: "→";
            color: var(--blue);
            font-size: 22px;
            font-weight: 400;
        }

        .osio:hover,
        .osio:focus-visible {
            transform: translateY(-2px);
            border-color: var(--blue);
            background: #f8fbfd;
            outline: none;
        }

        .varausalue {
            margin-top: 28px;
            padding: 22px;
            border: 1px solid var(--line);
            background: var(--paper);
        }

        .varausalue h2 {
            margin: 0 0 16px;
            color: var(--blue-dark);
            font-size: 20px;
        }

        .taulukko-kuori {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            padding: 11px 10px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: var(--blue-dark);
            color: var(--paper);
            white-space: nowrap;
        }

        td a {
            color: var(--blue-dark);
            font-weight: 700;
            text-underline-offset: 3px;
            white-space: nowrap;
        }

        .tyhja {
            color: var(--muted);
        }

        @media (max-width: 620px) {
            .ylapalkin-sisalto {
                width: min(100% - 32px, 1060px);
                min-height: 0;
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
                padding: 18px 0;
            }

            .kayttaja {
                width: 100%;
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            main {
                width: min(100% - 32px, 1060px);
                margin: 26px auto;
            }

            .osiot {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .osio {
                min-height: 72px;
            }
        }
    </style>
</head>
<body>
    <header class="ylapalkki">
        <div class="ylapalkin-sisalto">
            <h1>Luokkien varaus · Hallinta</h1>
            <p class="kayttaja">
                <span>Kirjautuneena: <?= htmlspecialchars($_SESSION["kayttajan_nimi"] ?? "", ENT_QUOTES, "UTF-8") ?></span>
                <a class="ulos" href="uloskirjautuminen.php">Kirjaudu ulos</a>
            </p>
        </div>
    </header>

    <main>
        <h2 class="otsikko">Hallinnan osiot</h2>
        <nav class="osiot" aria-label="Hallinnan osiot">
            <a class="osio" href="huoneet.php">Huoneet</a>
            <a class="osio" href="kayttajat.php">Käyttäjät</a>
        </nav>

        <section class="varausalue" aria-labelledby="varaukset-otsikko">
            <h2 id="varaukset-otsikko">Tehdyt varaukset</h2>
            <?php if ($varaukset === []): ?>
                <p class="tyhja">Varauksia ei ole vielä tehty.</p>
            <?php else: ?>
                <div class="taulukko-kuori">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Luokka/huone</th>
                                <th>Varaaja</th>
                                <th>Aihe</th>
                                <th>Päivämäärä</th>
                                <th>Aika</th>
                                <th>Toiminto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($varaukset as $varaus): ?>
                                <tr>
                                    <td><?= (int) $varaus["VarausID"] ?></td>
                                    <td><?= htmlspecialchars($varaus["LuokanNimi"] ?? "Luokka", ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars(trim(($varaus["Etunimi"] ?? "") . " " . ($varaus["Sukunimi"] ?? "")), ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($varaus["Aihe"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($varaus["Paivamaara"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars(substr($varaus["Alkamisaika"], 0, 5) . " - " . substr($varaus["Loppumisaika"], 0, 5), ENT_QUOTES, "UTF-8") ?></td>
                                    <td><a href="poista_huone.php?muokkaa=<?= (int) $varaus["VarausID"] ?>">Muokkaa</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
