-- phpMyAdmin SQL Dump
-- version 5.0.4deb2ubuntu5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 03. Apr 2022 um 21:44
-- Server-Version: 8.0.28-0ubuntu0.21.10.3
-- PHP-Version: 8.0.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `lithernet`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `qrcodes`
--

CREATE TABLE `qrcodes` (
  `code` varchar(10) NOT NULL,
  `code_type` int DEFAULT '0',
  `lithernet_id` int DEFAULT '255',
  `target_type` int DEFAULT '0',
  `target_id` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Daten für Tabelle `qrcodes`
--

INSERT INTO `qrcodes` (`code`, `code_type`, `lithernet_id`, `target_type`, `target_id`) VALUES
('9115e1d7fd', 0, 255, 0, 0),
('ab12', 2, 0, 0, 0),
('abcd', 1, 255, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `qrcode_level`
--

CREATE TABLE `qrcode_level` (
  `code` varchar(10) NOT NULL,
  `level` int NOT NULL DEFAULT '0',
  `red` int NOT NULL DEFAULT '0',
  `green` int NOT NULL DEFAULT '0',
  `blue` int NOT NULL DEFAULT '0',
  `white` int NOT NULL DEFAULT '0',
  `tc` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Daten für Tabelle `qrcode_level`
--

INSERT INTO `qrcode_level` (`code`, `level`, `red`, `green`, `blue`, `white`, `tc`) VALUES
('9115e1d7fd', 0, 0, 0, 0, 0, 0),
('ab12', 0, 0, 0, 0, 0, 0),
('abcd', 51, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user` (
  `username` varchar(20) NOT NULL,
  `password` varchar(20) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Daten für Tabelle `user`
--

INSERT INTO `user` (`username`, `password`, `last_login`) VALUES
('admin', 'password', '2022-04-03 13:18:19');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `qrcodes`
--
ALTER TABLE `qrcodes`
  ADD PRIMARY KEY (`code`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indizes für die Tabelle `qrcode_level`
--
ALTER TABLE `qrcode_level`
  ADD PRIMARY KEY (`code`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indizes für die Tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `username` (`username`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
