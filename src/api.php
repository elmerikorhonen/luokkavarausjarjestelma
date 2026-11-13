<?php
header('Content-Type: application/json; charset=utf-8');
$metodi = $_SERVER['REQUEST_METHOD'];
session_start();

try {
    require __DIR__ . '/back/db.php';
if (($_GET['taulu'] ?? '') === 'kayttajat') {
    if (!isset($_SESSION['kayttaja_id'])) {
        http_response_code(401);
        echo json_encode(['onnistui' => false, 'viesti' => 'Kirjaudu sisään.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $roolikysely = $pdo->prepare('SELECT Rooli, Aktiivinen FROM Kayttajat WHERE KayttajaID = :id');
    $roolikysely->execute(['id' => $_SESSION['kayttaja_id']]);
    $istunnonKayttaja = $roolikysely->fetch(PDO::FETCH_ASSOC);
    if (!$istunnonKayttaja || !(int) $istunnonKayttaja['Aktiivinen']) {
        http_response_code(401);
        echo json_encode(['onnistui' => false, 'viesti' => 'Käyttäjätili ei ole aktiivinen.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $_SESSION['kayttajan_rooli'] = $istunnonKayttaja['Rooli'];
    if ($_SESSION['kayttajan_rooli'] !== 'Opettaja') {
        http_response_code(403);
        echo json_encode(['onnistui' => false, 'viesti' => 'Käyttäjähallinta on vain opettajille.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($metodi === 'GET') {
        $lause = $pdo->query(
            'SELECT KayttajaID, Etunimi, Sukunimi, Puhelin, Sahkoposti, Rooli, Aktiivinen
             FROM Kayttajat ORDER BY Sukunimi, Etunimi'
        );
        echo json_encode($lause->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array($metodi, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        http_response_code(405);
        echo json_encode(['onnistui' => false, 'viesti' => 'Pyyntömenetelmää ei tueta.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        http_response_code(403);
        echo json_encode(['onnistui' => false, 'viesti' => 'Pyyntö vanheni. Päivitä sivu ja yritä uudelleen.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($metodi === 'DELETE' || $metodi === 'PATCH') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $aktiivinen = $metodi === 'PATCH' ? filter_var($_GET['aktiivinen'] ?? null, FILTER_VALIDATE_INT) : 0;
        if (!$id || !in_array($aktiivinen, [0, 1], true)
            || ($aktiivinen === 0 && $id === (int) $_SESSION['kayttaja_id'])) {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Käyttäjän tilaa ei voi muuttaa.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $kohdeKysely = $pdo->prepare('SELECT Rooli, Aktiivinen FROM Kayttajat WHERE KayttajaID = :id');
        $kohdeKysely->execute(['id' => $id]);
        $kohdeKayttaja = $kohdeKysely->fetch(PDO::FETCH_ASSOC);
        if (!$kohdeKayttaja) {
            http_response_code(404);
            echo json_encode(['onnistui' => false, 'viesti' => 'Käyttäjää ei löytynyt.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!$aktiivinen && $kohdeKayttaja['Rooli'] === 'Opettaja' && (int) $kohdeKayttaja['Aktiivinen']) {
            $opettajienMaara = (int) $pdo->query(
                "SELECT COUNT(*) FROM Kayttajat WHERE Rooli = 'Opettaja' AND Aktiivinen = 1"
            )->fetchColumn();
            if ($opettajienMaara <= 1) {
                http_response_code(409);
                echo json_encode(['onnistui' => false, 'viesti' => 'Viimeistä aktiivista opettajaa ei voi passivoida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $tilapaivitys = $pdo->prepare('UPDATE Kayttajat SET Aktiivinen = :aktiivinen WHERE KayttajaID = :id');
        $tilapaivitys->execute(['aktiivinen' => $metodi === 'DELETE' ? 0 : $aktiivinen, 'id' => $id]);
        echo json_encode([
            'onnistui' => true,
            'viesti' => ($metodi === 'DELETE' || !$aktiivinen) ? 'Käyttäjä passivoitiin.' : 'Käyttäjä aktivoitiin.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tiedot = json_decode(file_get_contents('php://input'), true);
    if (!is_array($tiedot)) {
        http_response_code(400);
        echo json_encode(['onnistui' => false, 'viesti' => 'Pyynnön JSON-tiedot ovat virheelliset.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $etunimi = trim($tiedot['etunimi'] ?? '');
    $sukunimi = trim($tiedot['sukunimi'] ?? '');
    $puhelin = trim($tiedot['puhelin'] ?? '');
    $sahkoposti = trim($tiedot['sahkoposti'] ?? '');
    $salasana = $tiedot['salasana'] ?? '';
    $rooli = $tiedot['rooli'] ?? ($metodi === 'POST' ? 'kayttaja' : null);

    if ($etunimi === '' || $sukunimi === '' || $puhelin === '' || $sahkoposti === '') {
        http_response_code(400);
        echo json_encode(['onnistui' => false, 'viesti' => 'Täytä nimi, puhelin ja sähköposti.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array($rooli, ['Opettaja', 'kayttaja'], true)
        || mb_strlen($etunimi) > 100 || mb_strlen($sukunimi) > 100 || mb_strlen($puhelin) > 30
        || mb_strlen($sahkoposti) > 100 || !filter_var($sahkoposti, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['onnistui' => false, 'viesti' => 'Tarkista kenttien pituudet ja sähköpostiosoite.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if ($metodi === 'POST') {
            if (!is_string($salasana) || strlen($salasana) < 8) {
                http_response_code(400);
                echo json_encode(['onnistui' => false, 'viesti' => 'Salasanan pitää olla vähintään 8 merkkiä.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $lause = $pdo->prepare(
                'INSERT INTO Kayttajat (Etunimi, Sukunimi, Puhelin, Sahkoposti, Salasana_hash, Rooli)
                 VALUES (:etunimi, :sukunimi, :puhelin, :sahkoposti, :salasana_hash, :rooli)'
            );
            $lause->execute([
                'etunimi' => $etunimi,
                'sukunimi' => $sukunimi,
                'puhelin' => $puhelin,
                'sahkoposti' => $sahkoposti,
                'salasana_hash' => password_hash($salasana, PASSWORD_DEFAULT),
                'rooli' => $rooli,
            ]);
            http_response_code(201);
            echo json_encode(['onnistui' => true, 'viesti' => 'Käyttäjä lisättiin.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = filter_var($tiedot['kayttaja_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Käyttäjän tunniste puuttuu.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($rooli !== 'Opettaja' && $rooli !== 'kayttaja') {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Valitse opettaja- tai käyttäjärooli.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($salasana !== '' && (!is_string($salasana) || strlen($salasana) < 8)) {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Salasanan pitää olla vähintään 8 merkkiä.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $kohdeRooli = $pdo->prepare('SELECT Rooli FROM Kayttajat WHERE KayttajaID = :id');
        $kohdeRooli->execute(['id' => $id]);
        $vanhaRooli = $kohdeRooli->fetchColumn();
        if ($vanhaRooli === false) {
            http_response_code(404);
            echo json_encode(['onnistui' => false, 'viesti' => 'Käyttäjää ei löytynyt.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($vanhaRooli === 'Opettaja' && $rooli !== 'Opettaja') {
            $opettajienMaara = (int) $pdo->query("SELECT COUNT(*) FROM Kayttajat WHERE Rooli = 'Opettaja'")->fetchColumn();
            if ($opettajienMaara <= 1) {
                http_response_code(409);
                echo json_encode(['onnistui' => false, 'viesti' => 'Viimeisen opettajan roolia ei voi vaihtaa.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $sql = 'UPDATE Kayttajat
                SET Etunimi = :etunimi, Sukunimi = :sukunimi, Puhelin = :puhelin,
                    Sahkoposti = :sahkoposti, Rooli = :rooli';
        $parametrit = [
            'etunimi' => $etunimi,
            'sukunimi' => $sukunimi,
            'puhelin' => $puhelin,
            'sahkoposti' => $sahkoposti,
            'rooli' => $rooli,
            'id' => $id,
        ];
        if ($salasana !== '') {
            $sql .= ', Salasana_hash = :salasana_hash';
            $parametrit['salasana_hash'] = password_hash($salasana, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE KayttajaID = :id';
        $lause = $pdo->prepare($sql);
        $lause->execute($parametrit);
        echo json_encode(['onnistui' => true, 'viesti' => 'Käyttäjän tiedot päivitettiin.'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(409);
            echo json_encode(['onnistui' => false, 'viesti' => 'Sähköpostiosoite on jo käytössä.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        throw $e;
    }
}

if(isset($_GET['taulu'])) {
    if($_GET['taulu'] == 'varaukset'){
    if ($metodi === 'GET') {
        if(isset($_GET['id'])) {
            $id = $_GET['id'];
            $lause = $pdo->prepare('SELECT VarausID, KayttajaID, LuokkaID, Paivamaara, Alkamisaika, Loppumisaika, Aihe FROM Varaukset WHERE VarausID = :id');
            $lause->execute([':id' => $id]);
        echo json_encode($lause->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    }
         else {
        $lause = $pdo->query('SELECT VarausID, KayttajaID, LuokkaID, Paivamaara, Alkamisaika, Loppumisaika, Aihe FROM Varaukset');
        echo json_encode($lause->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    }
    }

    if ($metodi === 'POST') {
        $tiedot = json_decode(file_get_contents('php://input'), true);
        if (!is_array($tiedot)) {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Pyynnön JSON-tiedot ovat virheelliset.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
$luokkaid = $tiedot["luokkaid"];
$kayttajaid = $tiedot["kayttajaid"];
        $Paivamaara = trim($tiedot['paivamaara'] ?? '');
        $Alkamisaika = trim($tiedot['alkamisaika'] ?? '');
        $Loppumisaika = $tiedot['loppumisaika'] ?? '';
        $Aihe = $tiedot['aihe'] ?? '';

        if ($Paivamaara === '' || $Alkamisaika === '' || $Loppumisaika === '' || $Aihe === '') {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Anna tapahtuman nimi, kirjailija, julkaisuvuosi ja hyllypaikka.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lause = $pdo->prepare('INSERT INTO Varaukset ( LuokkaID, KayttajaID, Paivamaara, Alkamisaika, Loppumisaika, Aihe)
            VALUES (:luokkaid, :kayttajaid, :paivamaara, :alkamisaika, :loppumisaika, :aihe)');
        $lause->execute([
            ':luokkaid' => $luokkaid,
            ':kayttajaid' => $kayttajaid,
            ':paivamaara' => $Paivamaara,
            ':alkamisaika' => $Alkamisaika,
            ':loppumisaika' => $Loppumisaika,
            ':aihe' =>  $Aihe
        ]);

        http_response_code(201);
        echo json_encode(['onnistui' => true, 'viesti' => 'Lisääminen onnistui'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($metodi === 'PUT') {
        $tiedot = json_decode(file_get_contents('php://input'), true);
        if (!is_array($tiedot)) {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Pyynnön JSON-tiedot ovat virheelliset.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $Paivamaara = trim($tiedot['paivamaara'] ?? '');
        $Alkamisaika = trim($tiedot['alkamisaika'] ?? '');
        $Loppumisaika = $tiedot['loppumisaika'] ?? '';
        $Aihe = $tiedot['aihe'] ?? '';

        if ($Paivamaara === '' || $Alkamisaika === '' || $Loppumisaika === '' || $Aihe === '') {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Anna tapahtuman nimi, kirjailija, julkaisuvuosi ja hyllypaikka.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lause = $conn->prepare('UPDATE Varaukset SET (paivamaara, alkamisaika, loppumisaika, aihe');
        $lause->execute([
            ':paivamaara' => $Paivamaara,
            ':alkamisaika' => $Alkamisaika,
            ':loppumisaika' => $Loppumisaika,
            ':aihe' => $Aihe
        ]);

        http_response_code(201);
        echo json_encode(['onnistui' => true, 'viesti' => 'Lisääminen onnistui'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['onnistui' => false, 'viesti' => 'Pyyntömenetelmää ei tueta.'], JSON_UNESCAPED_UNICODE);
}
if($_GET['taulu'] == 'luokat'){
    if ($metodi === 'GET') {
        if(isset($_GET['id'])) {
            $id = $_GET['id'];
            $lause = $pdo->prepare('SELECT LuokkaID, Nimi, Sijainti, Kapasiteetti, Luontiaika, Paivitetty FROM Luokat WHERE LuokkaID = :id');
            $lause->execute([':id' => $id]);
        echo json_encode($lause->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    }
         else {
        $lause = $pdo->query('SELECT LuokkaID, Nimi, Sijainti, Kapasiteetti, Luontiaika, Paivitetty FROM Luokat');
        echo json_encode($lause->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    }
    }

    if ($metodi === 'POST') {
        $tiedot = json_decode(file_get_contents('php://input'), true);
        if (!is_array($tiedot)) {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Pyynnön JSON-tiedot ovat virheelliset.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
$luokkaid = $tiedot["luokkaid"];
        $Nimi = $tiedot['nimi'];
        $Sijainti = $tiedot['sijainti'];
        $Kapasiteetti = $tiedot['kapasiteetti'] ?? '';
        $Luontiaika = $tiedot['luontiaika'] ?? '';
        $Paivitetty = $tiedot['paivitetty'] ?? '';

        if ($Nimi === '' || $Sijainti === '' || $Kapasiteetti === '' || $Luontiaika === '' || $Paivitetty === '') {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Anna tapahtuman nimi, kirjailija, julkaisuvuosi ja hyllypaikka.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lause = $pdo->prepare('INSERT INTO Luokat (LuokkaID, Nimi, Sijainti, Kapasiteetti, Luontiaika, Paivitetty)
            VALUES (:luokkaid, :nimi, :sijainti, :kapasiteetti, :luontiaika, :paivitetty)');
        $lause->execute([
            ':luokkaid' => $luokkaid,
            ':nimi' => $Nimi,
            ':sijainti' => $Sijainti,
            ':kapasiteetti' => $Kapasiteetti,
            ':luontiaika' => $Luontiaika,
            ':paivitetty' =>  $Paivitetty
        ]);

        http_response_code(201);
        echo json_encode(['onnistui' => true, 'viesti' => 'Lisääminen onnistui'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($metodi === 'PUT') {
        $tiedot = json_decode(file_get_contents('php://input'), true);
        if (!is_array($tiedot)) {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Pyynnön JSON-tiedot ovat virheelliset.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $Nimi = $tiedot['nimi'];
        $Sijainti = $tiedot['sijainti'];
        $Kapasiteetti = $tiedot['kapasiteetti'];
        $Luontiaika = $tiedot['luontiaika'];
        $Paivitetty = $tiedot['paivitetty'];


        if ($Nimi === '' || $Sijainti === '' || $Kapasiteetti === '' || $Luontiaika === '' || $Paivitetty === '') {
            http_response_code(400);
            echo json_encode(['onnistui' => false, 'viesti' => 'Anna tapahtuman nimi, kirjailija, julkaisuvuosi ja hyllypaikka.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lause = $conn->prepare('UPDATE Varaukset SET (paivamaara, alkamisaika, loppumisaika, aihe');
        $lause->execute([
            ':nimi' => $Nimi,
            ':sijainti' => $Sijainti,
            ':kapasiteetti' => $Kapasiteetti,
            ':luontiaika' => $Aihe,
            ':paivitetty' => $Paivitetty
        ]);

        http_response_code(201);
        echo json_encode(['onnistui' => true, 'viesti' => 'Lisääminen onnistui'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['onnistui' => false, 'viesti' => 'Pyyntömenetelmää ei tueta.'], JSON_UNESCAPED_UNICODE);
}}} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['onnistui' => false, 'viesti' => 'Tietokantavirhe: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>