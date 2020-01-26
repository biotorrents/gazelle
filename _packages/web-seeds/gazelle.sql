-- Line 1239
CREATE TABLE `torrents_mirrors` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `GroupID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Time` datetime,
  `Resource` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`,`GroupID`,`Resource`)
) ENGINE=InnoDB CHARSET=utf8mb4;
-- Line 1246