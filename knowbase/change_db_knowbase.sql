-- phpMyAdmin SQL Dump
-- version 2.6.1-rc1
-- http://www.phpmyadmin.net
-- 
-- Serveur: localhost
-- Généré le : Vendredi 31 Décembre 2004 à 03:15
-- Version du serveur: 4.0.23
-- Version de PHP: 4.3.8-5
-- 
-- Base de données: `glpidb`
-- 

-- --------------------------------------------------------

-- 
-- Structure de la table `glpi_kbcategories`
-- 

DROP TABLE IF EXISTS `glpi_kbcategories`;
CREATE TABLE `glpi_kbcategories` (
  `ID` int(11) NOT NULL auto_increment,
  `parentID` int(11) NOT NULL default '0',
  `name` text NOT NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

-- 
-- Contenu de la table `glpi_kbcategories`
-- 

INSERT INTO `glpi_kbcategories` VALUES (1, 0, 'Ordinateur');
INSERT INTO `glpi_kbcategories` VALUES (2, 0, 'Imprimante');
INSERT INTO `glpi_kbcategories` VALUES (3, 2, 'Papier');
INSERT INTO `glpi_kbcategories` VALUES (4, 2, 'Toner');

-- --------------------------------------------------------

-- 
-- Structure de la table `glpi_kbitems`
-- 

DROP TABLE IF EXISTS `glpi_kbitems`;
CREATE TABLE `glpi_kbitems` (
  `ID` int(11) NOT NULL auto_increment,
  `categoryID` int(11) NOT NULL default '0',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `faq` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

-- 
-- Contenu de la table `glpi_kbitems`
-- 

INSERT INTO `glpi_kbitems` VALUES (1, 3, 'Quel type de papier pour l''Epson Stylus Color 460 ?', '\r\nDu papier 90g,100g et 110 g.', 'no');
INSERT INTO `glpi_kbitems` VALUES (2, 2, 'Peut-on  utiliser l'' imprimante EPSON Stylus si la cartouche couleur est vide ?', 'Non. Les imprimantes EPSON Stylus nécessitent que les deux cartouches (noire et couleur) soient installées.', 'yes');
INSERT INTO `glpi_kbitems` VALUES (3, 1, 'Peut on utiliser des codes pour mettre en forme le texte ?', 'Oui : voir dans  l''aide en ligne \r\n\r\nQuelques exemples :\r\n\r\n[b]Texte gras[/b] \r\n[u]Texte souligné[/u] \r\n[i]Texte italique[/i] \r\n[color=#FF0000]Texte rouge[/color] \r\n\r\n\r\nhttp://glpi.indepnet.org\r\n\r\n[email]myname@mydomain.com[/email] \r\n\r\n[email=myname@mydomain.com]Mon adresse e-mail[/email] \r\n\r\n[code]Voici un bout de code.[/code]', 'no');
