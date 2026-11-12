-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Isäntä: db
-- Luontiaika: 05.11.2026 klo 10:16
-- Palvelimen versio: 8.0.46
-- PHP-versio 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Tietokanta: `Luokkavarausjarjestelma`
--

-- --------------------------------------------------------

--
-- Rakenne taululle `Kayttajat`
--

CREATE TABLE `Kayttajat` (
  `KayttajaID` int NOT NULL,
  `Etunimi` varchar(100) DEFAULT NULL,
  `Sukunimi` varchar(100) DEFAULT NULL,
  `Puhelin` varchar(30) DEFAULT NULL,
  `Sahkoposti` varchar(100) DEFAULT NULL,
  `Salasana_hash` varchar(255) DEFAULT NULL,
  `Rooli` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Rakenne taululle `Luokat`
--

CREATE TABLE `Luokat` (
  `LuokkaID` int NOT NULL,
  `Nimi` varchar(50) DEFAULT NULL,
  `Sijainti` varchar(50) DEFAULT NULL,
  `Kapasiteetti` int DEFAULT NULL,
  `Luontiaika` timestamp NULL DEFAULT NULL,
  `Paivitetty` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Rakenne taululle `Varaukset`
--

CREATE TABLE `Varaukset` (
  `VarausID` int NOT NULL,
  `KayttajaID` int DEFAULT NULL,
  `Luokka` varchar(50) DEFAULT NULL,
  `Paivamaara` date DEFAULT NULL,
  `Alkamisaika` time DEFAULT NULL,
  `Loppumisaika` time DEFAULT NULL,
  `Aihe` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indeksit taulukolle `Kayttajat`
--
ALTER TABLE `Kayttajat`
  ADD PRIMARY KEY (`KayttajaID`),
  ADD UNIQUE KEY `Sahkoposti` (`Sahkoposti`);

--
-- Indeksit taulukolle `Luokat`
--
ALTER TABLE `Luokat`
  ADD PRIMARY KEY (`LuokkaID`);

--
-- Indeksit taulukolle `Varaukset`
--
ALTER TABLE `Varaukset`
  ADD PRIMARY KEY (`VarausID`),
  ADD KEY `fk_kayttaja` (`KayttajaID`);

--
-- Kirjautumisten loki
--
CREATE TABLE `Kirjautumiset` (
  `KirjautuminenID` int NOT NULL AUTO_INCREMENT,
  `KayttajaID` int NOT NULL,
  `Kirjautumisaika` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Uloskirjautumisaika` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`KirjautuminenID`),
  KEY `fk_kirjautuminen_kayttaja` (`KayttajaID`),
  CONSTRAINT `fk_kirjautuminen_kayttaja`
    FOREIGN KEY (`KayttajaID`) REFERENCES `Kayttajat` (`KayttajaID`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Kayttajat`
--
ALTER TABLE `Kayttajat`
  MODIFY `KayttajaID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Luokat`
--
ALTER TABLE `Luokat`
  MODIFY `LuokkaID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Varaukset`
--
ALTER TABLE `Varaukset`
  MODIFY `VarausID` int NOT NULL AUTO_INCREMENT;

--
-- Rajoitteet vedostauluille
--

--
-- Rajoitteet taululle `Varaukset`
--
ALTER TABLE `Varaukset`
  ADD CONSTRAINT `fk_kayttaja` FOREIGN KEY (`KayttajaID`) REFERENCES `Kayttajat` (`KayttajaID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
