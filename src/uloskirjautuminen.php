<?php
session_start();

if (isset($_SESSION["kirjautuminen_id"], $_SESSION["kayttaja_id"])) {
    try {
        require_once __DIR__ . "/back/db.php";
        $kysely = $pdo->prepare(
            "UPDATE Kirjautumiset
             SET Uloskirjautumisaika = CURRENT_TIMESTAMP
             WHERE KirjautuminenID = :kirjautuminen_id
               AND KayttajaID = :kayttaja_id
               AND Uloskirjautumisaika IS NULL"
        );
        $kysely->execute([
            "kirjautuminen_id" => $_SESSION["kirjautuminen_id"],
            "kayttaja_id" => $_SESSION["kayttaja_id"],
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

$_SESSION = [];
session_destroy();

header("Location: kirjautuminen.php?ulos=1");
exit;
