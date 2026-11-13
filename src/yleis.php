<?php
session_start();

if (!isset($_SESSION["kayttaja_id"])) {
    header("Location: kirjautuminen.php");
    exit;
}

require_once __DIR__ . "/back/db.php";
$kayttajaId = (int) $_SESSION["kayttaja_id"];
$kayttajaKysely = $pdo->prepare(
    "SELECT Etunimi, Sukunimi, Rooli, Aktiivinen FROM Kayttajat WHERE KayttajaID = :id"
);
$kayttajaKysely->execute(["id" => $kayttajaId]);
$kayttaja = $kayttajaKysely->fetch(PDO::FETCH_ASSOC);

if (!$kayttaja || !(int) $kayttaja["Aktiivinen"]) {
    session_unset();
    session_destroy();
    header("Location: kirjautuminen.php");
    exit;
}

$_SESSION["kayttajan_rooli"] = $kayttaja["Rooli"];
if ($kayttaja["Rooli"] === "Opettaja") {
    header("Location: admin.php");
    exit;
}
if ($kayttaja["Rooli"] !== "kayttaja") {
    http_response_code(403);
    exit("Sinulla ei ole oikeutta nähdä tätä sivua.");
}

$varauskysely = $pdo->prepare(
    "SELECT DISTINCT COALESCE(l.Nimi, CONCAT('Luokka ', v.LuokkaID)) AS LuokanNimi,
            v.Aihe, v.Paivamaara, v.Alkamisaika, v.Loppumisaika
     FROM Varaukset v
     LEFT JOIN Luokat l ON l.LuokkaID = v.LuokkaID
     WHERE v.KayttajaID = :kayttaja_id
     ORDER BY v.Paivamaara, v.Alkamisaika"
);
$varauskysely->execute(["kayttaja_id" => $kayttajaId]);
$varaukset = $varauskysely->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omat varaukset | Luokkien varaus</title>
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

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--blue-soft);
            color: var(--ink);
            font-family: Arial, sans-serif;
        }

        header {
            background: var(--blue);
            color: var(--paper);
            box-shadow: 0 3px 12px rgb(23 45 67 / 18%);
        }

        .ylapalkin-sisalto,
        main {
            width: min(100% - 48px, 1060px);
            margin: 0 auto;
        }

        .ylapalkin-sisalto {
            display: flex;
            min-height: 76px;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        h1 { margin: 0; font-size: 24px; }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        header a { color: var(--paper); }
        main { padding: 30px 0 40px; }

        .varaukset {
            padding: 22px;
            border: 1px solid var(--line);
            background: var(--paper);
        }

        h2 { margin: 0 0 18px; font-size: 20px; }
        .taulukko-kuori { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--paper);
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

        .tyhja { color: var(--muted); }

        @media (max-width: 620px) {
            .ylapalkin-sisalto,
            main { width: min(100% - 32px, 1060px); }
            .ylapalkin-sisalto { min-height: 0; align-items: flex-start; flex-direction: column; gap: 12px; padding: 16px 0; }
            nav { gap: 14px; }
            .varaukset { padding: 18px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="ylapalkin-sisalto">
            <h1>Tervetuloa, <?= htmlspecialchars($kayttaja["Etunimi"], ENT_QUOTES, "UTF-8") ?></h1>
            <nav aria-label="Päänavigaatio">
                <a href="varaa.php">Varaa huone</a>
                <a href="profiili.php">Profiili</a>
                <a href="uloskirjautuminen.php">Kirjaudu ulos</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="varaukset" aria-labelledby="varaukset-otsikko">
            <h2 id="varaukset-otsikko">Omat varaukset</h2>
            <?php if ($varaukset === []): ?>
                <p class="tyhja">Sinulla ei ole vielä varauksia.</p>
            <?php else: ?>
                <div class="taulukko-kuori">
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
                                    <td><?= htmlspecialchars($varaus["LuokanNimi"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($varaus["Aihe"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($varaus["Paivamaara"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars(substr($varaus["Alkamisaika"], 0, 5) . " - " . substr($varaus["Loppumisaika"], 0, 5), ENT_QUOTES, "UTF-8") ?></td>
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
