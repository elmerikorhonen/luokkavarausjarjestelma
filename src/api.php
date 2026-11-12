<?php
header('Content-Type: application/json; charset=utf-8');
$metodi = $_SERVER['REQUEST_METHOD'];

try {
    require __DIR__ . '/back/db.php';
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