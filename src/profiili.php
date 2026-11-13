<?php
session_start();

if (!isset($_SESSION["kayttaja_id"])) {
    header("Location: kirjautuminen.php");
    exit;
}

require_once __DIR__ . "/back/db.php";
$kysely = $pdo->prepare(
    "SELECT Etunimi, Sukunimi, Puhelin, Sahkoposti, Rooli, Aktiivinen
     FROM Kayttajat
     WHERE KayttajaID = :id"
);
$kysely->execute(["id" => $_SESSION["kayttaja_id"]]);
$kayttaja = $kysely->fetch(PDO::FETCH_ASSOC);

if (!$kayttaja || !(int) $kayttaja["Aktiivinen"]) {
    session_unset();
    session_destroy();
    header("Location: kirjautuminen.php");
    exit;
}

$rooli = $kayttaja["Rooli"] === "Opettaja" ? "Opettaja (ylläpitäjä)" : "Käyttäjä";
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiili | Luokkien varaus</title>
    <style>
        :root {
            --blue: #365d81;
            --blue-dark: #243f59;
            --blue-soft: #e7eef5;
            --paper: #fff;
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
            width: min(100% - 48px, 760px);
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
        header a { color: var(--paper); }
        main { padding: 30px 0; }

        .profiili {
            border: 1px solid var(--line);
            background: var(--paper);
        }

        .profiili h2 {
            margin: 0;
            padding: 20px 22px;
            border-bottom: 1px solid var(--line);
            font-size: 20px;
        }

        dl { margin: 0; padding: 6px 22px; }

        .rivi {
            display: grid;
            grid-template-columns: minmax(130px, 1fr) 2fr;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid var(--line);
        }

        .rivi:last-child { border-bottom: 0; }
        dt { color: var(--muted); }
        dd { margin: 0; font-weight: 700; overflow-wrap: anywhere; }

        @media (max-width: 560px) {
            .ylapalkin-sisalto,
            main { width: min(100% - 32px, 760px); }
            .rivi { grid-template-columns: 1fr; gap: 5px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="ylapalkin-sisalto">
            <h1>Profiili</h1>
            <nav aria-label="Päänavigaatio">
                <a href="<?= $kayttaja["Rooli"] === "Opettaja" ? "admin.php" : "yleis.php" ?>">Takaisin</a>
                <a href="uloskirjautuminen.php">Kirjaudu ulos</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="profiili" aria-labelledby="profiili-otsikko">
            <h2 id="profiili-otsikko">Kirjautuneen käyttäjän tiedot</h2>
            <dl>
                <div class="rivi">
                    <dt>Etunimi</dt>
                    <dd><?= htmlspecialchars($kayttaja["Etunimi"], ENT_QUOTES, "UTF-8") ?></dd>
                </div>
                <div class="rivi">
                    <dt>Sukunimi</dt>
                    <dd><?= htmlspecialchars($kayttaja["Sukunimi"], ENT_QUOTES, "UTF-8") ?></dd>
                </div>
                <div class="rivi">
                    <dt>Puhelin</dt>
                    <dd><?= htmlspecialchars($kayttaja["Puhelin"], ENT_QUOTES, "UTF-8") ?></dd>
                </div>
                <div class="rivi">
                    <dt>Sähköposti</dt>
                    <dd><?= htmlspecialchars($kayttaja["Sahkoposti"], ENT_QUOTES, "UTF-8") ?></dd>
                </div>
                <div class="rivi">
                    <dt>Käyttöoikeus</dt>
                    <dd><?= htmlspecialchars($rooli, ENT_QUOTES, "UTF-8") ?></dd>
                </div>
            </dl>
        </section>
    </main>
</body>
</html>
