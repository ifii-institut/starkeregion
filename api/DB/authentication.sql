-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 04. Feb 2021 um 13:03
-- Server-Version: 10.4.14-MariaDB
-- PHP-Version: 7.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `zentral-db-testing`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `authentication`
--

CREATE TABLE `authentication` (
  `uid` bigint(10) NOT NULL,
  `customer_id` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `apikey` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `authentication`
--

INSERT INTO `authentication` (`uid`, `customer_id`, `customer_email`, `apikey`) VALUES
(1, 'Sebastian Enger, Entwickler, StarkeRegion-Plattform', 's.enger@company.de', '56d9320489ffa79124bb8991fdbca1c7aa8722a0d14e94425229bd63543743b6157f3253c33fac8b18765a37464ce3492978a92e50865089999fd1b051b0a833');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `authentication`
--
ALTER TABLE `authentication`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `customer_email` (`customer_email`),
  ADD UNIQUE KEY `apikey_unique` (`apikey`) USING HASH;

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `authentication`
--
ALTER TABLE `authentication`
  MODIFY `uid` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
