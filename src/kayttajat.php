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
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Käyttäjät | Luokkien varaus</title>
    <style>
        :root {
            --blue: #365d81;
            --blue-dark: #243f59;
            --blue-soft: #e7eef5;
            --paper: #ffffff;
            --ink: #263746;
            --muted: #627384;
            --line: #d3dee8;
            --danger: #a52b35;
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
        h2 { margin: 0 0 18px; font-size: 20px; }

        header a { color: var(--paper); }
        main { padding: 28px 0 40px; }

        .palsta {
            display: grid;
            grid-template-columns: minmax(260px, 340px) minmax(0, 1fr);
            align-items: start;
            gap: 18px;
        }

        .osio {
            padding: 22px;
            border: 1px solid var(--line);
            background: var(--paper);
        }

        label {
            display: block;
            margin: 13px 0 5px;
            font-size: 14px;
            font-weight: 700;
        }

        input,
        select {
            width: 100%;
            min-height: 42px;
            padding: 9px 10px;
            border: 1px solid #b8c5d1;
            border-radius: 3px;
            background: #fff;
            color: var(--ink);
            font: inherit;
        }

        input:focus,
        select:focus {
            border-color: var(--blue);
            outline: 2px solid rgb(54 93 129 / 18%);
        }

        .napit {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        button {
            min-height: 40px;
            padding: 9px 13px;
            border: 0;
            border-radius: 3px;
            background: var(--blue);
            color: var(--paper);
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }

        button:hover { background: var(--blue-dark); }
        button:disabled { cursor: wait; opacity: .65; }

        button.toissijainen {
            border: 1px solid var(--line);
            background: var(--paper);
            color: var(--ink);
        }

        button.poista { background: var(--danger); }

        .viesti {
            display: none;
            margin-bottom: 16px;
            padding: 12px 14px;
            border-left: 4px solid var(--blue);
            background: var(--paper);
        }

        .viesti.nakyvissa { display: block; }
        .viesti.virhe { border-left-color: var(--danger); color: #77232a; }

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

        td.toiminnot { white-space: nowrap; }
        td.toiminnot button { min-height: 34px; padding: 7px 9px; font-size: 13px; }
        .tyhja { color: var(--muted); }

        @media (max-width: 760px) {
            .ylapalkin-sisalto,
            main { width: min(100% - 32px, 1060px); }
            .ylapalkin-sisalto { min-height: 0; align-items: flex-start; flex-direction: column; gap: 8px; padding: 16px 0; }
            .palsta { grid-template-columns: 1fr; }
            .osio { padding: 18px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="ylapalkin-sisalto">
            <h1>Käyttäjien hallinta</h1>
            <a href="admin.php">Takaisin hallintaan</a>
        </div>
    </header>

    <main>
        <div id="viesti" class="viesti" role="status" aria-live="polite"></div>
        <div class="palsta">
            <section class="osio" aria-labelledby="lomake-otsikko">
                <h2 id="lomake-otsikko">Lisää käyttäjä</h2>
                <form id="kayttaja-lomake">
                    <input type="hidden" id="kayttaja_id" name="kayttaja_id">

                    <label for="etunimi">Etunimi</label>
                    <input id="etunimi" name="etunimi" maxlength="100" autocomplete="given-name" required>

                    <label for="sukunimi">Sukunimi</label>
                    <input id="sukunimi" name="sukunimi" maxlength="100" autocomplete="family-name" required>

                    <label for="puhelin">Puhelin</label>
                    <input id="puhelin" name="puhelin" type="tel" maxlength="30" autocomplete="tel" required>

                    <label for="sahkoposti">Sähköposti</label>
                    <input id="sahkoposti" name="sahkoposti" type="email" maxlength="100" autocomplete="email" required>

                    <label for="rooli">Käyttöoikeus</label>
                    <select id="rooli" name="rooli" required>
                        <option value="kayttaja">Käyttäjä, normaali käyttö</option>
                        <option value="Opettaja">Opettaja, ylläpitäjä</option>
                    </select>

                    <label for="salasana">Salasana <span id="salasana-ohje">(vähintään 8 merkkiä)</span></label>
                    <input id="salasana" name="salasana" type="password" minlength="8" autocomplete="new-password">

                    <div class="napit">
                        <button id="tallenna" type="submit">Lisää käyttäjä</button>
                        <button id="peruuta" class="toissijainen" type="button" hidden>Peruuta muokkaus</button>
                    </div>
                </form>
            </section>

            <section class="osio" aria-labelledby="lista-otsikko">
                <h2 id="lista-otsikko">Käyttäjät</h2>
                <div class="taulukko-kuori">
                    <table>
                        <thead>
                            <tr>
                                <th>Nimi</th>
                                <th>Puhelin</th>
                                <th>Sähköposti</th>
                                <th>Rooli</th>
                                <th>Tila</th>
                                <th>Toiminnot</th>
                            </tr>
                        </thead>
                        <tbody id="kayttajat-lista">
                            <tr><td class="tyhja" colspan="6">Ladataan käyttäjiä...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <script>
        const apiUrl = "api.php?taulu=kayttajat";
        const csrfToken = <?= json_encode($_SESSION["csrf_token"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const lomake = document.getElementById("kayttaja-lomake");
        const lista = document.getElementById("kayttajat-lista");
        const viesti = document.getElementById("viesti");
        const tallennaNappi = document.getElementById("tallenna");
        const peruutaNappi = document.getElementById("peruuta");
        let kayttajat = [];

        function naytaViesti(teksti, virhe = false) {
            viesti.textContent = teksti;
            viesti.className = virhe ? "viesti nakyvissa virhe" : "viesti nakyvissa";
        }

        async function apiPyynto(url = apiUrl, asetukset = {}) {
            const vastaus = await fetch(url, {
                ...asetukset,
                headers: {
                    ...(asetukset.body ? { "Content-Type": "application/json" } : {}),
                    ...(asetukset.method && asetukset.method !== "GET" ? { "X-CSRF-Token": csrfToken } : {}),
                    ...asetukset.headers
                }
            });
            const data = await vastaus.json();
            if (!vastaus.ok) {
                throw new Error(data.viesti || "Pyyntö epäonnistui.");
            }
            return data;
        }

        function lisaaSolu(rivi, arvo) {
            const solu = document.createElement("td");
            solu.textContent = arvo ?? "";
            rivi.append(solu);
            return solu;
        }

        function piirraKayttajat() {
            lista.replaceChildren();
            if (kayttajat.length === 0) {
                const rivi = document.createElement("tr");
                const solu = lisaaSolu(rivi, "Käyttäjiä ei löytynyt.");
                solu.colSpan = 6;
                solu.className = "tyhja";
                lista.append(rivi);
                return;
            }

            for (const kayttaja of kayttajat) {
                const rivi = document.createElement("tr");
                lisaaSolu(rivi, `${kayttaja.Etunimi} ${kayttaja.Sukunimi}`);
                lisaaSolu(rivi, kayttaja.Puhelin);
                lisaaSolu(rivi, kayttaja.Sahkoposti);
                lisaaSolu(rivi, kayttaja.Rooli === "Opettaja" ? "Opettaja (ylläpitäjä)" : "Käyttäjä");
                lisaaSolu(rivi, Number(kayttaja.Aktiivinen) ? "Aktiivinen" : "Passiivinen");
                const toiminnot = document.createElement("td");
                toiminnot.className = "toiminnot";

                const muokkaa = document.createElement("button");
                muokkaa.type = "button";
                muokkaa.textContent = "Muokkaa";
                muokkaa.addEventListener("click", () => aloitaMuokkaus(kayttaja));
                toiminnot.append(muokkaa);

                const tilaNappi = document.createElement("button");
                tilaNappi.type = "button";
                tilaNappi.className = Number(kayttaja.Aktiivinen) ? "poista" : "toissijainen";
                tilaNappi.textContent = Number(kayttaja.Aktiivinen) ? "Poista käytöstä" : "Aktivoi";
                tilaNappi.addEventListener("click", () => muutaKayttajanTilaa(kayttaja));
                toiminnot.append(tilaNappi);

                rivi.append(toiminnot);
                lista.append(rivi);
            }
        }

        async function lataaKayttajat() {
            try {
                kayttajat = await apiPyynto();
                piirraKayttajat();
            } catch (virhe) {
                lista.replaceChildren();
                const rivi = document.createElement("tr");
                const solu = lisaaSolu(rivi, virhe.message);
                solu.colSpan = 6;
                solu.className = "tyhja";
                lista.append(rivi);
            }
        }

        function aloitaMuokkaus(kayttaja) {
            document.getElementById("kayttaja_id").value = kayttaja.KayttajaID;
            document.getElementById("etunimi").value = kayttaja.Etunimi;
            document.getElementById("sukunimi").value = kayttaja.Sukunimi;
            document.getElementById("puhelin").value = kayttaja.Puhelin;
            document.getElementById("sahkoposti").value = kayttaja.Sahkoposti;
            document.getElementById("rooli").value = kayttaja.Rooli;
            document.getElementById("salasana").value = "";
            document.getElementById("salasana").required = false;
            document.getElementById("salasana-ohje").textContent = "(jätä tyhjäksi, jos et vaihda salasanaa)";
            document.getElementById("lomake-otsikko").textContent = "Muokkaa käyttäjää";
            tallennaNappi.textContent = "Tallenna muutokset";
            peruutaNappi.hidden = false;
            document.getElementById("etunimi").focus();
        }

        function tyhjennaLomake() {
            lomake.reset();
            document.getElementById("kayttaja_id").value = "";
            document.getElementById("salasana").required = false;
            document.getElementById("salasana-ohje").textContent = "(vähintään 8 merkkiä)";
            document.getElementById("lomake-otsikko").textContent = "Lisää käyttäjä";
            tallennaNappi.textContent = "Lisää käyttäjä";
            peruutaNappi.hidden = true;
        }

        async function muutaKayttajanTilaa(kayttaja) {
            const aktivoidaan = !Number(kayttaja.Aktiivinen);
            const kysymys = aktivoidaan
                ? `Aktivoidaanko käyttäjä ${kayttaja.Etunimi} ${kayttaja.Sukunimi}?`
                : `Poistetaanko käyttäjän ${kayttaja.Etunimi} ${kayttaja.Sukunimi} käyttö käytöstä? Tili ja varaukset säilyvät.`;
            if (!confirm(kysymys)) {
                return;
            }
            try {
                const data = aktivoidaan
                    ? await apiPyynto(`${apiUrl}&id=${encodeURIComponent(kayttaja.KayttajaID)}&aktiivinen=1`, { method: "PATCH" })
                    : await apiPyynto(`${apiUrl}&id=${encodeURIComponent(kayttaja.KayttajaID)}`, { method: "DELETE" });
                naytaViesti(data.viesti);
                await lataaKayttajat();
            } catch (virhe) {
                naytaViesti(virhe.message, true);
            }
        }

        lomake.addEventListener("submit", async (tapahtuma) => {
            tapahtuma.preventDefault();
            const kayttajaId = document.getElementById("kayttaja_id").value;
            const tiedot = Object.fromEntries(new FormData(lomake).entries());
            tiedot.kayttaja_id = kayttajaId;
            tallennaNappi.disabled = true;

            try {
                const data = await apiPyynto(apiUrl, {
                    method: kayttajaId ? "PUT" : "POST",
                    body: JSON.stringify(tiedot)
                });
                naytaViesti(data.viesti);
                tyhjennaLomake();
                await lataaKayttajat();
            } catch (virhe) {
                naytaViesti(virhe.message, true);
            } finally {
                tallennaNappi.disabled = false;
            }
        });

        peruutaNappi.addEventListener("click", tyhjennaLomake);
        lataaKayttajat();
    </script>
</body>
</html>
