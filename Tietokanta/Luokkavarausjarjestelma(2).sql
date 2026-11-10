-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Isäntä: db
-- Luontiaika: 09.11.2026 klo 07:50
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
  `Etunimi` varchar(100) NOT NULL,
  `Sukunimi` varchar(100) NOT NULL,
  `Puhelin` varchar(30) NOT NULL,
  `Sahkoposti` varchar(100) NOT NULL,
  `Salasana_hash` varchar(255) DEFAULT NULL,
  `Rooli` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `Kayttajat`
--

INSERT INTO `Kayttajat` (`KayttajaID`, `Etunimi`, `Sukunimi`, `Puhelin`, `Sahkoposti`, `Salasana_hash`, `Rooli`) VALUES
(2, 'Pekka', 'Mäkelä', '0445973815', 'pekkamakela@gmail.com', NULL, 'Opettaja');

-- --------------------------------------------------------

--
-- Rakenne taululle `Luokat`
--

CREATE TABLE `Luokat` (
  `LuokkaID` int NOT NULL,
  `Nimi` varchar(100) NOT NULL,
  `Sijainti` varchar(100) NOT NULL,
  `Kapasiteetti` int NOT NULL,
  `Luontiaika` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Paivitetty` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `Luokat`
--

INSERT INTO `Luokat` (`LuokkaID`, `Nimi`, `Sijainti`, `Kapasiteetti`, `Luontiaika`, `Paivitetty`) VALUES
(1, 'Auditoria A, Kokoushuone kivi, Luokka 2B, Ullakkosali', '1. kerros, itäsiipi, Pääaula', 12, NULL, NULL);

-- --------------------------------------------------------

--
-- Rakenne taululle `Varaukset`
--

CREATE TABLE `Varaukset` (
  `VarausID` int NOT NULL,
  `KayttajaID` int NOT NULL,
  `LuokkaID` int NOT NULL,
  `Paivamaara` date NOT NULL,
  `Alkamisaika` time NOT NULL,
  `Loppumisaika` time NOT NULL,
  `Aihe` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `Varaukset`
--

INSERT INTO `Varaukset` (`VarausID`, `KayttajaID`, `LuokkaID`, `Paivamaara`, `Alkamisaika`, `Loppumisaika`, `Aihe`) VALUES
(1, 2, 1, '2027-02-10', '11:30:00', '13:15:00', 'Historia');

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
  ADD KEY `fk_kayttaja` (`KayttajaID`),
  ADD KEY `fk_luokka` (`LuokkaID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Kayttajat`
--
ALTER TABLE `Kayttajat`
  MODIFY `KayttajaID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `Luokat`
--
ALTER TABLE `Luokat`
  MODIFY `LuokkaID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Varaukset`
--
ALTER TABLE `Varaukset`
  MODIFY `VarausID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Rajoitteet vedostauluille
--

--
-- Rajoitteet taululle `Varaukset`
--
ALTER TABLE `Varaukset`
  ADD CONSTRAINT `fk_kayttaja` FOREIGN KEY (`KayttajaID`) REFERENCES `Kayttajat` (`KayttajaID`),
  ADD CONSTRAINT `fk_luokka` FOREIGN KEY (`LuokkaID`) REFERENCES `Luokat` (`LuokkaID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
