-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Isäntä: db
-- Luontiaika: 12.11.2026 klo 12:41
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
-- Tietokanta: `testdb`
--

-- --------------------------------------------------------

--
-- Rakenne taululle `Kirjautumiset`
--

CREATE TABLE `Kirjautumiset` (
  `KirjautuminenID` int NOT NULL,
  `KayttajaID` int NOT NULL,
  `Kirjautumisaika` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Uloskirjautumisaika` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `Kirjautumiset`
--

INSERT INTO `Kirjautumiset` (`KirjautuminenID`, `KayttajaID`, `Kirjautumisaika`, `Uloskirjautumisaika`) VALUES
(1, 3, '2026-11-12 06:53:54', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeksit taulukolle `Kirjautumiset`
--
ALTER TABLE `Kirjautumiset`
  ADD PRIMARY KEY (`KirjautuminenID`),
  ADD KEY `fk_kirjautuminen_kayttaja` (`KayttajaID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Kirjautumiset`
--
ALTER TABLE `Kirjautumiset`
  MODIFY `KirjautuminenID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Rajoitteet vedostauluille
--

--
-- Rajoitteet taululle `Kirjautumiset`
--
ALTER TABLE `Kirjautumiset`
  ADD CONSTRAINT `fk_kirjautuminen_kayttaja` FOREIGN KEY (`KayttajaID`) REFERENCES `Kayttajat` (`KayttajaID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
