-- Development or production?
SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE gazelle_development CHARACTER SET utf8mb4;
USE gazelle_development;


-- 2020-10-11
CREATE TABLE `api_user_tokens`(
    `ID` int NOT NULL AUTO_INCREMENT,
    `UserID` int NOT NULL,
    `AppID` int DEFAULT NULL,
    `Name` VARCHAR(50) NOT NULL,
    `Token` CHAR(255) NOT NULL,
    `Scope` TEXT,
    `Created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Revoked` ENUM('0', '1', '2') NOT NULL DEFAULT '0',
    PRIMARY KEY(`ID`, `Token`),
    UNIQUE KEY `Name`(`Name`),
    KEY `UserID`(`UserID`)
) ENGINE = InnoDB CHARSET = utf8mb4;


-- 2020-10-11
CREATE TABLE `api_applications`(
    `ID` int unsigned NOT NULL AUTO_INCREMENT,
    `UserID` int unsigned NOT NULL,
    `Name` VARCHAR(50) NOT NULL,
    `Token` CHAR(255) NOT NULL,
    `Description` TEXT,
    `CategoryID` int unsigned NOT NULL DEFAULT '0',
    `TagList` VARCHAR(500) NOT NULL DEFAULT '',
    `Created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(`ID`),
    UNIQUE KEY `Name`(`Name`),
    KEY `UserID`(`UserID`),
    KEY `CategoryID`(`CategoryID`)
) ENGINE = InnoDB CHARSET = utf8mb4;


-- https://github.com/OPSnet/Gazelle/blob/master/db/data/gazelle.sql
-- 2020-12-12
CREATE TABLE `login_attempts`(
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int unsigned NOT NULL DEFAULT 0,
  `IP` varchar(15) NOT NULL,
  `LastAttempt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Attempts` int unsigned NOT NULL DEFAULT 1,
  `BannedUntil` datetime DEFAULT NULL,
  `Bans` int unsigned NOT NULL,
  `Capture` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `IP` (`IP`),
  KEY `attempts_idx` (`Attempts`)
) ENGINE = InnoDB CHARSET = utf8mb4;


-- https://github.com/OPSnet/Gazelle/blob/master/db/data/gazelle.sql
-- 2020-12-12
CREATE TABLE `ip_bans` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `FromIP` int unsigned NOT NULL,
  `ToIP` int unsigned NOT NULL,
  `Reason` varchar(255) DEFAULT NULL,
  `UserID` int unsigned NOT NULL DEFAULT 0,
  `Created` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `FromIP_2` (`FromIP`,`ToIP`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `artists_group` (
  `ArtistID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL DEFAULT '',
  `ORCiD` varchar(20) NOT NULL DEFAULT '', -- todo
  `RevisionID` int DEFAULT NULL,
  `LastCommentID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ArtistID`,`Name`),
  KEY `RevisionID` (`RevisionID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `artists_tags` (
  `TagID` int NOT NULL DEFAULT '0',
  `ArtistID` int NOT NULL DEFAULT '0',
  `PositiveVotes` int NOT NULL DEFAULT '1',
  `NegativeVotes` int NOT NULL DEFAULT '1',
  `UserID` int DEFAULT NULL,
  PRIMARY KEY (`TagID`,`ArtistID`),
  KEY `TagID` (`TagID`),
  KEY `ArtistID` (`ArtistID`),
  KEY `PositiveVotes` (`PositiveVotes`),
  KEY `NegativeVotes` (`NegativeVotes`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `badges` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Icon` varchar(255) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2021-07-28
CREATE TABLE `bioinformatics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `torrent_id` int NOT NULL,
  `user_id` int NOT NULL,
  `timestamp` datetime NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `seqhash` varchar(100) DEFAULT NULL,
  `gc_content` tinyint DEFAULT NULL,
  `monoisotopic_mass` double DEFAULT NULL,
  PRIMARY KEY (`id`,`torrent_id`,`user_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `blog` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int unsigned NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Body` text,
  `Time` datetime,
  `ThreadID` int unsigned DEFAULT NULL,
  `Important` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `bookmarks_artists` (
  `UserID` int NOT NULL,
  `ArtistID` int NOT NULL,
  `Time` datetime,
  KEY `UserID` (`UserID`),
  KEY `ArtistID` (`ArtistID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `bookmarks_collages` (
  `UserID` int NOT NULL,
  `CollageID` int NOT NULL,
  `Time` datetime,
  KEY `UserID` (`UserID`),
  KEY `CollageID` (`CollageID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `bookmarks_requests` (
  `UserID` int NOT NULL,
  `RequestID` int NOT NULL,
  `Time` datetime,
  KEY `UserID` (`UserID`),
  KEY `RequestID` (`RequestID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `bookmarks_torrents` (
  `UserID` int NOT NULL,
  `GroupID` int NOT NULL,
  `Time` datetime,
  `Sort` int NOT NULL DEFAULT '0',
  UNIQUE KEY `groups_users` (`GroupID`,`UserID`),
  KEY `UserID` (`UserID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `collages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL DEFAULT '', -- todo: 100 vs. 255?
  `Description` text,
  `UserID` int NOT NULL DEFAULT '0',
  `NumTorrents` int NOT NULL DEFAULT '0',
  `Deleted` enum('0','1') DEFAULT '0',
  `Locked` enum('0','1') NOT NULL DEFAULT '0',
  `CategoryID` int NOT NULL DEFAULT '1',
  `TagList` varchar(500) NOT NULL DEFAULT '',
  `MaxGroups` int NOT NULL DEFAULT '0',
  `MaxGroupsPerUser` int NOT NULL DEFAULT '0',
  `Featured` tinyint NOT NULL DEFAULT '0',
  `Subscribers` int DEFAULT '0',
  `updated` datetime,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `UserID` (`UserID`),
  KEY `CategoryID` (`CategoryID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `collages_artists` (
  `CollageID` int NOT NULL,
  `ArtistID` int NOT NULL,
  `UserID` int NOT NULL,
  `Sort` int NOT NULL DEFAULT '0',
  `AddedOn` datetime,
  PRIMARY KEY (`CollageID`,`ArtistID`),
  KEY `UserID` (`UserID`),
  KEY `Sort` (`Sort`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `collages_torrents` (
  `CollageID` int NOT NULL,
  `GroupID` int NOT NULL,
  `UserID` int NOT NULL,
  `Sort` int NOT NULL DEFAULT '0',
  `AddedOn` datetime,
  PRIMARY KEY (`CollageID`,`GroupID`),
  KEY `UserID` (`UserID`),
  KEY `Sort` (`Sort`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `comments` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Page` enum('artist','collages','requests','torrents') NOT NULL,
  `PageID` int NOT NULL,
  `AuthorID` int NOT NULL,
  `AddedTime` datetime,
  `Body` mediumtext,
  `EditedUserID` int DEFAULT NULL,
  `EditedTime` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Page` (`Page`,`PageID`),
  KEY `AuthorID` (`AuthorID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `cover_art` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GroupID` int NOT NULL,
  `Image` varchar(255) NOT NULL DEFAULT '',
  `Summary` varchar(100) DEFAULT NULL, -- todo: 100 vs. 255?
  `UserID` int NOT NULL DEFAULT '0',
  `Time` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `GroupID` (`GroupID`,`Image`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `dupe_groups` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `Comments` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `email_blacklist` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Time` datetime,
  `Comment` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `featured_albums` (
  `GroupID` int NOT NULL DEFAULT '0',
  `ThreadID` int NOT NULL DEFAULT '0',
  `Title` varchar(35) NOT NULL DEFAULT '', -- todo: 35 vs. 50 vs. 255?
  `Started` datetime,
  `Ended` datetime
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `CategoryID` tinyint NOT NULL DEFAULT '0',
  `Sort` int unsigned NOT NULL,
  `Name` varchar(40) NOT NULL DEFAULT '', -- todo: 40 vs. 50 vs. 255?
  `Description` varchar(255) DEFAULT '',
  `MinClassRead` int NOT NULL DEFAULT '0',
  `MinClassWrite` int NOT NULL DEFAULT '0',
  `MinClassCreate` int NOT NULL DEFAULT '0',
  `NumTopics` int NOT NULL DEFAULT '0',
  `NumPosts` int NOT NULL DEFAULT '0',
  `LastPostID` int NOT NULL DEFAULT '0',
  `LastPostAuthorID` int NOT NULL DEFAULT '0',
  `LastPostTopicID` int NOT NULL DEFAULT '0',
  `LastPostTime` datetime,
  PRIMARY KEY (`ID`),
  KEY `Sort` (`Sort`),
  KEY `MinClassRead` (`MinClassRead`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_categories` (
  `ID` tinyint NOT NULL AUTO_INCREMENT,
  `Name` varchar(40) NOT NULL DEFAULT '', -- todo: 40 vs. 50 vs. 255?
  `Sort` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `Sort` (`Sort`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_last_read_topics` (
  `UserID` int NOT NULL,
  `TopicID` int NOT NULL,
  `PostID` int NOT NULL,
  PRIMARY KEY (`UserID`,`TopicID`),
  KEY `TopicID` (`TopicID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_polls` (
  `TopicID` int unsigned NOT NULL,
  `Question` varchar(255) NOT NULL,
  `Answers` text,
  `Featured` datetime,
  `Closed` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`TopicID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_polls_votes` (
  `TopicID` int unsigned NOT NULL,
  `UserID` int unsigned NOT NULL,
  `Vote` tinyint unsigned NOT NULL,
  PRIMARY KEY (`TopicID`,`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_posts` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TopicID` int NOT NULL,
  `AuthorID` int NOT NULL,
  `AddedTime` datetime,
  `Body` mediumtext,
  `EditedUserID` int DEFAULT NULL,
  `EditedTime` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TopicID` (`TopicID`),
  KEY `AuthorID` (`AuthorID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_specific_rules` (
  `ForumID` int unsigned DEFAULT NULL,
  `ThreadID` int DEFAULT NULL
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_topics` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(150) NOT NULL, -- todo: 150 vs. 255?
  `AuthorID` int NOT NULL,
  `IsLocked` enum('0','1') NOT NULL DEFAULT '0',
  `IsSticky` enum('0','1') NOT NULL DEFAULT '0',
  `ForumID` int NOT NULL,
  `NumPosts` int NOT NULL DEFAULT '0',
  `LastPostID` int NOT NULL DEFAULT '0',
  `LastPostTime` datetime,
  `LastPostAuthorID` int NOT NULL,
  `StickyPostID` int NOT NULL DEFAULT '0',
  `Ranking` tinyint DEFAULT '0',
  `CreatedTime` datetime,
  PRIMARY KEY (`ID`),
  KEY `AuthorID` (`AuthorID`),
  KEY `ForumID` (`ForumID`),
  KEY `IsSticky` (`IsSticky`),
  KEY `LastPostID` (`LastPostID`),
  KEY `Title` (`Title`),
  KEY `CreatedTime` (`CreatedTime`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `forums_topic_notes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TopicID` int NOT NULL,
  `AuthorID` int NOT NULL,
  `AddedTime` datetime,
  `Body` mediumtext,
  PRIMARY KEY (`ID`),
  KEY `TopicID` (`TopicID`),
  KEY `AuthorID` (`AuthorID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `group_log` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GroupID` int NOT NULL,
  `TorrentID` int NOT NULL,
  `UserID` int NOT NULL DEFAULT '0',
  `Info` mediumtext,
  `Time` datetime,
  `Hidden` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `GroupID` (`GroupID`),
  KEY `TorrentID` (`TorrentID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `invites` (
  `InviterID` int NOT NULL DEFAULT '0',
  `InviteKey` char(32) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Expires` datetime,
  `Reason` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`InviteKey`),
  KEY `Expires` (`Expires`),
  KEY `InviterID` (`InviterID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `invite_tree` (
  `UserID` int NOT NULL DEFAULT '0',
  `InviterID` int NOT NULL DEFAULT '0',
  `TreePosition` int NOT NULL DEFAULT '1',
  `TreeID` int NOT NULL DEFAULT '1',
  `TreeLevel` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`),
  KEY `InviterID` (`InviterID`),
  KEY `TreePosition` (`TreePosition`),
  KEY `TreeID` (`TreeID`),
  KEY `TreeLevel` (`TreeLevel`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `last_sent_email` (
  `UserID` int NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2021-07-29
CREATE TABLE `literature` (
  `id` int NOT NULL,
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `timestamp` datetime DEFAULT NULL,
  `doi` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `year` smallint DEFAULT NULL,
  PRIMARY KEY (`id`,`group_id`,`doi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `locked_accounts` (
  `UserID` int unsigned NOT NULL,
  `Type` tinyint NOT NULL,
  PRIMARY KEY (`UserID`),
  CONSTRAINT `fk_user_id` FOREIGN KEY (`UserID`) REFERENCES `users_main` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `log` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `Message` varchar(400) NOT NULL, -- todo: 400 vs. 500?
  `Time` datetime,
  PRIMARY KEY (`ID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `misc` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(64) NOT NULL,
  `First` text,
  `Second` text,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `name_index` (`Name`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `news` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int unsigned NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Body` text,
  `Time` datetime,
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `new_info_hashes` (
  `TorrentID` int NOT NULL,
  `InfoHash` binary(20) DEFAULT NULL,
  PRIMARY KEY (`TorrentID`),
  KEY `InfoHash` (`InfoHash`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `ocelot_query_times` (
  `buffer` enum('users','torrents','snatches','peers') NOT NULL,
  `starttime` datetime,
  `ocelotinstance` datetime,
  `querylength` int NOT NULL,
  `timespent` int NOT NULL,
  UNIQUE KEY `starttime` (`starttime`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `permissions` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `Level` int unsigned NOT NULL,
  `Name` varchar(25) NOT NULL,
  `Values` text,
  `DisplayStaff` enum('0','1') NOT NULL DEFAULT '0',
  `PermittedForums` varchar(150) NOT NULL DEFAULT '', -- todo: 150 vs. 255?
  `Secondary` tinyint NOT NULL DEFAULT '0',
  `Abbreviation` varchar(5) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Level` (`Level`),
  KEY `DisplayStaff` (`DisplayStaff`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `pm_conversations` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Subject` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `pm_conversations_users` (
  `UserID` int NOT NULL DEFAULT '0',
  `ConvID` int NOT NULL DEFAULT '0',
  `InInbox` enum('1','0') NOT NULL,
  `InSentbox` enum('1','0') NOT NULL,
  `SentDate` datetime,
  `ReceivedDate` datetime,
  `UnRead` enum('1','0') NOT NULL DEFAULT '1',
  `Sticky` enum('1','0') NOT NULL DEFAULT '0',
  `ForwardedTo` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`,`ConvID`),
  KEY `InInbox` (`InInbox`),
  KEY `InSentbox` (`InSentbox`),
  KEY `ConvID` (`ConvID`),
  KEY `UserID` (`UserID`),
  KEY `SentDate` (`SentDate`),
  KEY `ReceivedDate` (`ReceivedDate`),
  KEY `Sticky` (`Sticky`),
  KEY `ForwardedTo` (`ForwardedTo`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `pm_messages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConvID` int NOT NULL DEFAULT '0',
  `SentDate` datetime,
  `SenderID` int NOT NULL DEFAULT '0',
  `Body` text,
  PRIMARY KEY (`ID`),
  KEY `ConvID` (`ConvID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- Added back 2020-12-05
CREATE TABLE `reports` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int unsigned NOT NULL DEFAULT '0',
  `ThingID` int unsigned NOT NULL DEFAULT '0',
  `Type` varchar(30) DEFAULT NULL,
  `Comment` text,
  `ResolverID` int unsigned NOT NULL DEFAULT '0',
  `Status` enum('New','InProgress','Resolved') DEFAULT 'New',
  `ResolvedTime` datetime,
  `ReportedTime` datetime,
  `Reason` text,
  `ClaimerID` int unsigned NOT NULL DEFAULT '0',
  `Notes` text,
  PRIMARY KEY (`ID`),
  KEY `Status` (`Status`),
  KEY `Type` (`Type`),
  KEY `ResolvedTime` (`ResolvedTime`),
  KEY `ResolverID` (`ResolverID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `reportsv2` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `ReporterID` int unsigned NOT NULL DEFAULT '0',
  `TorrentID` int unsigned NOT NULL DEFAULT '0',
  `Type` varchar(25) DEFAULT '', -- todo: 25 vs. 50 vs. 255?
  `UserComment` text,
  `ResolverID` int unsigned NOT NULL DEFAULT '0',
  `Status` enum('New','InProgress','Resolved') DEFAULT 'New',
  `ReportedTime` datetime,
  `LastChangeTime` datetime,
  `ModComment` text,
  `Track` text,
  `Image` text,
  `ExtraID` text,
  `Link` text,
  `LogMessage` text,
  PRIMARY KEY (`ID`),
  KEY `Status` (`Status`),
  KEY `Type` (`Type`(1)),
  KEY `LastChangeTime` (`LastChangeTime`),
  KEY `TorrentID` (`TorrentID`),
  KEY `ResolverID` (`ResolverID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `requests` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int unsigned NOT NULL DEFAULT '0',
  `TimeAdded` datetime,
  `LastVote` datetime DEFAULT NULL,
  `CategoryID` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Title2` varchar(255) DEFAULT NULL,
  `TitleJP` varchar(255) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Description` text,
  `CatalogueNumber` varchar(50) NOT NULL,
  `FillerID` int unsigned NOT NULL DEFAULT '0',
  `TorrentID` int unsigned NOT NULL DEFAULT '0',
  `TimeFilled` datetime,
  `Visible` binary(1) NOT NULL DEFAULT '1',
  `GroupID` int DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `Userid` (`UserID`),
  KEY `Name` (`Title`),
  KEY `Filled` (`TorrentID`),
  KEY `FillerID` (`FillerID`),
  KEY `TimeAdded` (`TimeAdded`),
  KEY `TimeFilled` (`TimeFilled`),
  KEY `LastVote` (`LastVote`),
  KEY `GroupID` (`GroupID`),
  KEY `NameJP` (`TitleJP`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `requests_artists` (
  `RequestID` int unsigned NOT NULL,
  `ArtistID` int NOT NULL,
  PRIMARY KEY (`RequestID`, `ArtistID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `requests_tags` (
  `TagID` int NOT NULL DEFAULT '0',
  `RequestID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`TagID`,`RequestID`),
  KEY `TagID` (`TagID`),
  KEY `RequestID` (`RequestID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `requests_votes` (
  `RequestID` int NOT NULL DEFAULT '0',
  `UserID` int NOT NULL DEFAULT '0',
  `Bounty` bigint unsigned NOT NULL,
  PRIMARY KEY (`RequestID`,`UserID`),
  KEY `RequestID` (`RequestID`),
  KEY `UserID` (`UserID`),
  KEY `Bounty` (`Bounty`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `schedule` (
  `NextHour` int NOT NULL DEFAULT '0',
  `NextDay` int NOT NULL DEFAULT '0',
  `NextBiWeekly` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `shop_freeleeches` (
  `TorrentID` int NOT NULL,
  `ExpiryTime` datetime,
  PRIMARY KEY (`TorrentID`),
  KEY `ExpiryTime` (`ExpiryTime`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `staff_pm_conversations` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Subject` text,
  `UserID` int DEFAULT NULL,
  `Status` enum('Open','Unanswered','Resolved') DEFAULT NULL,
  `Level` int DEFAULT NULL,
  `AssignedToUser` int DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Unread` tinyint DEFAULT NULL,
  `ResolverID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `StatusAssigned` (`Status`,`AssignedToUser`),
  KEY `StatusLevel` (`Status`,`Level`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `staff_pm_messages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `UserID` int DEFAULT NULL,
  `SentDate` datetime DEFAULT NULL,
  `Message` text,
  `ConvID` int DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `staff_pm_responses` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Message` text,
  `Name` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2020-03-09
CREATE TABLE `stylesheets` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `Default` enum('0','1') NOT NULL DEFAULT '0',
  `Additions` text,
  `Color` varchar(7), -- #deadbe
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- todo: Start again here
CREATE TABLE `tag_aliases` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BadTag` varchar(255) DEFAULT NULL,
  `AliasTag` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `BadTag` (`BadTag`),
  KEY `AliasTag` (`AliasTag`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `tags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) DEFAULT NULL,
  `TagType` enum('genre','other') NOT NULL DEFAULT 'other',
  `Uses` int NOT NULL DEFAULT '1',
  `UserID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name_2` (`Name`),
  KEY `TagType` (`TagType`),
  KEY `Uses` (`Uses`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `top10_history` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Date` datetime,
  `Type` enum('Daily','Weekly') DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `top10_history_torrents` (
  `HistoryID` int NOT NULL DEFAULT '0',
  `Rank` tinyint NOT NULL DEFAULT '0',
  `TorrentID` int NOT NULL DEFAULT '0',
  `TitleString` varchar(150) NOT NULL DEFAULT '',
  `TagString` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `top_snatchers` (
  `UserID` int unsigned NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `torrents` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GroupID` int NOT NULL DEFAULT '0',
  `UserID` int DEFAULT NULL,
  `Media` varchar(32) DEFAULT NULL,
  `Container` varchar(32) DEFAULT NULL,
  `Codec` varchar(32) DEFAULT NULL,
  `Resolution` varchar(32) DEFAULT NULL,
  `Version` varchar(32) DEFAULT NULL,
  `Censored` tinyint NOT NULL DEFAULT '1',
  `Anonymous` tinyint NOT NULL DEFAULT '0',
  `info_hash` blob NOT NULL,
  `FileCount` int NOT NULL DEFAULT '0',
  `FileList` mediumtext,
  `FilePath` varchar(255) NOT NULL DEFAULT '',
  `Size` bigint NOT NULL DEFAULT '0',
  `Leechers` int NOT NULL DEFAULT '0',
  `Seeders` int NOT NULL DEFAULT '0',
  `last_action` datetime,
  `FreeTorrent` enum('0','1','2') NOT NULL DEFAULT '0',
  `FreeLeechType` enum('0','1','2','3','4') NOT NULL DEFAULT '0',
  `Time` datetime,
  `Description` text,
  `Snatched` int unsigned NOT NULL DEFAULT '0',
  `balance` bigint NOT NULL DEFAULT '0',
  `LastReseedRequest` datetime,
  `Archive` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `InfoHash` (`info_hash`(40)),
  KEY `GroupID` (`GroupID`),
  KEY `UserID` (`UserID`),
  KEY `Media` (`Media`),
  KEY `Container` (`Container`),
  KEY `Codec` (`Codec`),
  KEY `Resolution` (`Resolution`),
  KEY `Version` (`Version`),
  KEY `FileCount` (`FileCount`),
  KEY `Size` (`Size`),
  KEY `Seeders` (`Seeders`),
  KEY `Leechers` (`Leechers`),
  KEY `last_action` (`last_action`),
  KEY `Time` (`Time`),
  KEY `FreeTorrent` (`FreeTorrent`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `torrents_artists` (
  `GroupID` int NOT NULL,
  `ArtistID` int NOT NULL,
  `UserID` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`GroupID`,`ArtistID`),
  KEY `ArtistID` (`ArtistID`),
  KEY `GroupID` (`GroupID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `torrents_bad_files` (
  `TorrentID` int NOT NULL DEFAULT '0',
  `UserID` int NOT NULL DEFAULT '0',
  `TimeAdded` datetime
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `torrents_bad_folders` (
  `TorrentID` int NOT NULL,
  `UserID` int NOT NULL,
  `TimeAdded` datetime,
  PRIMARY KEY (`TorrentID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `torrents_bad_tags` (
  `TorrentID` int NOT NULL DEFAULT '0',
  `UserID` int NOT NULL DEFAULT '0',
  `TimeAdded` datetime,
  KEY `TimeAdded` (`TimeAdded`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2021-07-08
CREATE TABLE `torrents_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` tinyint DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `object` varchar(255) DEFAULT NULL,
  `year` smallint DEFAULT NULL,
  `workgroup` varchar(128) DEFAULT NULL,
  `location` varchar(128) DEFAULT NULL,
  `identifier` varchar(64) DEFAULT NULL,
  `tag_list` varchar(512) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `revision_id` int DEFAULT NULL,
  `description` text DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `title` (`title`),
  KEY `year` (`year`),
  KEY `timestamp` (`timestamp`),
  KEY `revision_id` (`revision_id`);
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `torrents_logs_new` (
  `LogID` int NOT NULL AUTO_INCREMENT,
  `TorrentID` int NOT NULL DEFAULT '0',
  `Log` mediumtext,
  `Details` mediumtext,
  `Score` int NOT NULL,
  `Revision` int NOT NULL,
  `Adjusted` enum('1','0') NOT NULL DEFAULT '0',
  `AdjustedBy` int NOT NULL DEFAULT '0',
  `NotEnglish` enum('1','0') NOT NULL DEFAULT '0',
  `AdjustmentReason` text,
  PRIMARY KEY (`LogID`),
  KEY `TorrentID` (`TorrentID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `torrents_peerlists` (
  `TorrentID` int NOT NULL,
  `GroupID` int DEFAULT NULL,
  `Seeders` int DEFAULT NULL,
  `Leechers` int DEFAULT NULL,
  `Snatches` int DEFAULT NULL,
  PRIMARY KEY (`TorrentID`),
  KEY `GroupID` (`GroupID`),
  KEY `Stats` (`TorrentID`,`Seeders`,`Leechers`,`Snatches`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `torrents_peerlists_compare` (
  `TorrentID` int NOT NULL,
  `GroupID` int DEFAULT NULL,
  `Seeders` int DEFAULT NULL,
  `Leechers` int DEFAULT NULL,
  `Snatches` int DEFAULT NULL,
  PRIMARY KEY (`TorrentID`),
  KEY `GroupID` (`GroupID`),
  KEY `Stats` (`TorrentID`,`Seeders`,`Leechers`,`Snatches`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `torrents_recommended` (
  `GroupID` int NOT NULL,
  `UserID` int NOT NULL,
  `Time` datetime,
  PRIMARY KEY (`GroupID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET=utf8mb4;


-- 2021-07-28
CREATE TABLE `torrents_mirrors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `torrent_id` int NOT NULL,
  `user_id` int NOT NULL,
  `timestamp` datetime,
  `uri` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`torrent_id`,`uri`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `torrents_tags` (
  `TagID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  `UserID` int DEFAULT NULL,
  PRIMARY KEY (`TagID`,`GroupID`),
  KEY `TagID` (`TagID`),
  KEY `GroupID` (`GroupID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `u2f` (
  `UserID` int NOT NULL,
  `KeyHandle` varchar(255) NOT NULL,
  `PublicKey` varchar(255) NOT NULL,
  `Certificate` text,
  `Counter` int NOT NULL DEFAULT '-1',
  `Valid` enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`KeyHandle`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_badges` (
  `UserID` int NOT NULL,
  `BadgeID` int NOT NULL,
  `Displayed` tinyint DEFAULT '0',
  PRIMARY KEY (`UserID`,`BadgeID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_collage_subs` (
  `UserID` int NOT NULL,
  `CollageID` int NOT NULL,
  `LastVisit` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`,`CollageID`),
  KEY `CollageID` (`CollageID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_comments_last_read` (
  `UserID` int NOT NULL,
  `Page` enum('artist','collages','requests','torrents') NOT NULL,
  `PageID` int NOT NULL,
  `PostID` int NOT NULL,
  PRIMARY KEY (`UserID`,`Page`,`PageID`),
  KEY `Page` (`Page`,`PageID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_donor_ranks` (
  `UserID` int NOT NULL DEFAULT '0',
  `Rank` tinyint NOT NULL DEFAULT '0',
  `DonationTime` datetime DEFAULT NULL,
  `Hidden` tinyint NOT NULL DEFAULT '0',
  `TotalRank` int NOT NULL DEFAULT '0',
  `SpecialRank` tinyint DEFAULT '0',
  `InvitesRecievedRank` tinyint DEFAULT '0',
  `RankExpirationTime` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  KEY `DonationTime` (`DonationTime`),
  KEY `SpecialRank` (`SpecialRank`),
  KEY `Rank` (`Rank`),
  KEY `TotalRank` (`TotalRank`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_downloads` (
  `UserID` int NOT NULL,
  `TorrentID` int NOT NULL,
  `Time` datetime,
  PRIMARY KEY (`UserID`,`TorrentID`,`Time`),
  KEY `TorrentID` (`TorrentID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_dupes` (
  `GroupID` int unsigned NOT NULL,
  `UserID` int unsigned NOT NULL,
  UNIQUE KEY `UserID` (`UserID`),
  KEY `GroupID` (`GroupID`),
  CONSTRAINT `users_dupes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users_main` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `users_dupes_ibfk_2` FOREIGN KEY (`GroupID`) REFERENCES `dupe_groups` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_enable_recommendations` (
  `ID` int NOT NULL,
  `Enable` tinyint DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Enable` (`Enable`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_enable_requests` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `UserID` int unsigned NOT NULL,
  `Email` varchar(255) NOT NULL,
  `IP` varchar(255) NOT NULL DEFAULT 'mIbUEUEmV93bF6C5i6cITAlcw3H7TKcaPzZZIMIZQNQ=',
  `UserAgent` text,
  `Timestamp` datetime,
  `HandledTimestamp` datetime DEFAULT NULL,
  `Token` char(32) DEFAULT NULL,
  `CheckedBy` int unsigned DEFAULT NULL,
  `Outcome` tinyint DEFAULT NULL COMMENT '1 for approved, 2 for denied, 3 for discarded',
  PRIMARY KEY (`ID`),
  KEY `UserId` (`UserID`),
  KEY `CheckedBy` (`CheckedBy`),
  CONSTRAINT `users_enable_requests_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users_main` (`ID`),
  CONSTRAINT `users_enable_requests_ibfk_2` FOREIGN KEY (`CheckedBy`) REFERENCES `users_main` (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_freeleeches` (
  `UserID` int NOT NULL,
  `TorrentID` int NOT NULL,
  `Time` datetime,
  `Expired` tinyint NOT NULL DEFAULT '0',
  `Downloaded` bigint NOT NULL DEFAULT '0',
  `Uses` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`TorrentID`),
  KEY `Time` (`Time`),
  KEY `Expired_Time` (`Expired`,`Time`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_friends` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `userId` INT NOT NULL,
  `friendId` INT NOT NULL,
  `comment` VARCHAR(255),
  `created` DATETIME DEFAULT NOW(),
  `updated` DATETIME ON UPDATE NOW(),
  PRIMARY KEY (`id`, `userId`,`friendId`)
);


CREATE TABLE `users_info` (
  `UserID` int unsigned NOT NULL,
  `StyleID` int unsigned NOT NULL,
  `StyleURL` varchar(255) DEFAULT NULL,
  `Info` text,
  `Avatar` varchar(255),
  `AdminComment` text,
  `SiteOptions` text,
  `ViewAvatars` enum('0','1') NOT NULL DEFAULT '1',
  `Donor` enum('0','1') NOT NULL DEFAULT '0',
  `Artist` enum('0','1') NOT NULL DEFAULT '0',
  `Warned` datetime,
  `SupportFor` varchar(255),
  `TorrentGrouping` enum('0','1','2') NOT NULL COMMENT '0=Open,1=Closed,2=Off',
  `ShowTags` enum('0','1') NOT NULL DEFAULT '1',
  `NotifyOnQuote` enum('0','1','2') NOT NULL DEFAULT '0',
  `AuthKey` varchar(32) NOT NULL DEFAULT '',
  `ResetKey` varchar(32) NOT NULL DEFAULT '',
  `ResetExpires` datetime,
  `JoinDate` datetime,
  `Inviter` int DEFAULT NULL,
  `WarnedTimes` int NOT NULL DEFAULT '0',
  `RatioWatchEnds` datetime,
  `RatioWatchDownload` bigint unsigned NOT NULL DEFAULT '0',
  `RatioWatchTimes` tinyint unsigned NOT NULL DEFAULT '0',
  `BanDate` datetime,
  `BanReason` enum('0','1','2','3','4') NOT NULL DEFAULT '0',
  `CatchupTime` datetime DEFAULT NULL,
  `LastReadNews` int NOT NULL DEFAULT '0',
  `HideCountryChanges` enum('0','1') NOT NULL DEFAULT '0',
  `RestrictedForums` varchar(150) NOT NULL DEFAULT '',
  `PermittedForums` varchar(150) NOT NULL DEFAULT '',
  `UnseededAlerts` enum('0','1') NOT NULL DEFAULT '0',
  `LastReadBlog` int NOT NULL DEFAULT '0',
  `InfoTitle` varchar(255) NOT NULL DEFAULT '',
  UNIQUE KEY `UserID` (`UserID`),
  KEY `SupportFor` (`SupportFor`),
  KEY `Donor` (`Donor`),
  KEY `Warned` (`Warned`),
  KEY `JoinDate` (`JoinDate`),
  KEY `Inviter` (`Inviter`),
  KEY `RatioWatchEnds` (`RatioWatchEnds`),
  KEY `RatioWatchDownload` (`RatioWatchDownload`),
  KEY `AuthKey` (`AuthKey`),
  KEY `ResetKey` (`ResetKey`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_levels` (
  `UserID` int unsigned NOT NULL,
  `PermissionID` int unsigned NOT NULL,
  PRIMARY KEY (`UserID`,`PermissionID`),
  KEY `PermissionID` (`PermissionID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_main` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `Username` varchar(32) NOT NULL DEFAULT '',
  `Email` varchar(255) NOT NULL DEFAULT '',
  `PassHash` varchar(60) NOT NULL DEFAULT '',
  `TwoFactor` varchar(255) DEFAULT NULL,
  `PublicKey` text,
  `IRCKey` char(32) DEFAULT NULL,
  `LastLogin` datetime,
  `LastAccess` datetime,
  `IP` varchar(90) NOT NULL DEFAULT '0.0.0.0',
  `Class` tinyint NOT NULL DEFAULT '5',
  `Uploaded` bigint unsigned NOT NULL DEFAULT '0',
  `Downloaded` bigint unsigned NOT NULL DEFAULT '0',
  `Title` text,
  `Enabled` enum('0','1','2') NOT NULL DEFAULT '0',
  `Paranoia` text,
  `Visible` enum('1','0') NOT NULL DEFAULT '1',
  `Invites` int unsigned NOT NULL DEFAULT '0',
  `PermissionID` int unsigned NOT NULL DEFAULT '0',
  `CustomPermissions` text,
  `can_leech` tinyint NOT NULL DEFAULT '1',
  `torrent_pass` char(32) NOT NULL DEFAULT '',
  `RequiredRatio` double(10,8) NOT NULL DEFAULT '0.00000000',
  `RequiredRatioWork` double(10,8) NOT NULL DEFAULT '0.00000000',
  `FLTokens` int NOT NULL DEFAULT '0',
  `BonusPoints` int unsigned NOT NULL DEFAULT '0',
  `IRCLines` int unsigned NOT NULL DEFAULT '0',
  `HnR` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Username` (`Username`),
  KEY `Email` (`Email`),
  KEY `PassHash` (`PassHash`),
  KEY `LastAccess` (`LastAccess`),
  KEY `IP` (`IP`),
  KEY `Class` (`Class`),
  KEY `Uploaded` (`Uploaded`),
  KEY `Downloaded` (`Downloaded`),
  KEY `Enabled` (`Enabled`),
  KEY `Invites` (`Invites`),
  KEY `torrent_pass` (`torrent_pass`),
  KEY `RequiredRatio` (`RequiredRatio`),
  KEY `PermissionID` (`PermissionID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_notifications_settings` (
  `UserID` int NOT NULL DEFAULT '0',
  `Inbox` tinyint DEFAULT '1',
  `StaffPM` tinyint DEFAULT '1',
  `News` tinyint DEFAULT '1',
  `Blog` tinyint DEFAULT '1',
  `Torrents` tinyint DEFAULT '1',
  `Collages` tinyint DEFAULT '1',
  `Quotes` tinyint DEFAULT '1',
  `Subscriptions` tinyint DEFAULT '1',
  `SiteAlerts` tinyint DEFAULT '1',
  `RequestAlerts` tinyint DEFAULT '1',
  `CollageAlerts` tinyint DEFAULT '1',
  `TorrentAlerts` tinyint DEFAULT '1',
  `ForumAlerts` tinyint DEFAULT '1',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_notify_filters` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `Label` varchar(128) NOT NULL DEFAULT '',
  `Artists` mediumtext,
  `RecordLabels` mediumtext,
  `Users` mediumtext,
  `Tags` varchar(500) NOT NULL DEFAULT '',
  `NotTags` varchar(500) NOT NULL DEFAULT '',
  `Categories` varchar(500) NOT NULL DEFAULT '',
  `Formats` varchar(500) NOT NULL DEFAULT '',
  `Encodings` varchar(500) NOT NULL DEFAULT '',
  `Media` varchar(500) NOT NULL DEFAULT '',
  `FromYear` int NOT NULL DEFAULT '0',
  `ToYear` int NOT NULL DEFAULT '0',
  `NewGroupsOnly` enum('1','0') NOT NULL DEFAULT '0',
  `ReleaseTypes` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `FromYear` (`FromYear`),
  KEY `ToYear` (`ToYear`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_notify_quoted` (
  `UserID` int NOT NULL,
  `QuoterID` int NOT NULL,
  `Page` enum('forums','artist','collages','requests','torrents') NOT NULL,
  `PageID` int NOT NULL,
  `PostID` int NOT NULL,
  `UnRead` tinyint NOT NULL DEFAULT '1',
  `Date` datetime,
  PRIMARY KEY (`UserID`,`Page`,`PostID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_notify_torrents` (
  `UserID` int NOT NULL,
  `FilterID` int NOT NULL,
  `GroupID` int NOT NULL,
  `TorrentID` int NOT NULL,
  `UnRead` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`TorrentID`),
  KEY `TorrentID` (`TorrentID`),
  KEY `UserID_Unread` (`UserID`,`UnRead`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_points` (
  `UserID` int NOT NULL,
  `GroupID` int NOT NULL,
  `Points` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`GroupID`),
  KEY `UserID` (`UserID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_points_requests` (
  `UserID` int NOT NULL,
  `RequestID` int NOT NULL,
  `Points` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`RequestID`),
  KEY `UserID` (`UserID`),
  KEY `RequestID` (`RequestID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_seedtime` (
  `UserID` int unsigned NOT NULL,
  `TorrentID` int unsigned NOT NULL,
  `SeedTime` int unsigned NOT NULL DEFAULT '0',
  `Uploaded` bigint NOT NULL DEFAULT '0',
  `LastUpdate` datetime NOT NULL,
  `Downloaded` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`,`TorrentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `users_sessions` (
	`userId` INT NOT NULL,
	`sessionId` VARCHAR(128) NOT NULL,
	`expires` DATETIME NOT NULL,
	`ipAddress` VARCHAR(128),
	`userAgent` TEXT,
	PRIMARY KEY (`userId`,`sessionId`)
);


CREATE TABLE `users_subscriptions` (
  `UserID` int NOT NULL,
  `TopicID` int NOT NULL,
  PRIMARY KEY (`UserID`,`TopicID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_subscriptions_comments` (
  `UserID` int NOT NULL,
  `Page` enum('artist','collages','requests','torrents') NOT NULL,
  `PageID` int NOT NULL,
  PRIMARY KEY (`UserID`,`Page`,`PageID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_torrent_history` (
  `UserID` int unsigned NOT NULL,
  `NumTorrents` int unsigned NOT NULL,
  `Date` int unsigned NOT NULL,
  `Time` int unsigned NOT NULL DEFAULT '0',
  `LastTime` int unsigned NOT NULL DEFAULT '0',
  `Finished` enum('1','0') NOT NULL DEFAULT '1',
  `Weight` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`,`NumTorrents`,`Date`),
  KEY `Finished` (`Finished`),
  KEY `Date` (`Date`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_torrent_history_snatch` (
  `UserID` int unsigned NOT NULL,
  `NumSnatches` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`),
  KEY `NumSnatches` (`NumSnatches`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `users_torrent_history_temp` (
  `UserID` int unsigned NOT NULL,
  `NumTorrents` int unsigned NOT NULL DEFAULT '0',
  `SumTime` bigint unsigned NOT NULL DEFAULT '0',
  `SeedingAvg` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `wiki_aliases` (
  `Alias` varchar(50) NOT NULL,
  `UserID` int NOT NULL,
  `ArticleID` int DEFAULT NULL,
  PRIMARY KEY (`Alias`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `wiki_articles` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Revision` int NOT NULL DEFAULT '1',
  `Title` varchar(100) DEFAULT NULL,
  `Body` mediumtext,
  `MinClassRead` int DEFAULT NULL,
  `MinClassEdit` int DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Author` int DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `wiki_artists` (
  `RevisionID` int NOT NULL AUTO_INCREMENT,
  `PageID` int NOT NULL DEFAULT '0',
  `Body` text,
  `UserID` int NOT NULL DEFAULT '0',
  `Summary` varchar(100) DEFAULT NULL,
  `Time` datetime,
  `Image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`RevisionID`),
  KEY `PageID` (`PageID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `wiki_revisions` (
  `ID` int NOT NULL,
  `Revision` int NOT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `Body` mediumtext,
  `Date` datetime DEFAULT NULL,
  `Author` int DEFAULT NULL,
  KEY `ID_Revision` (`ID`,`Revision`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `wiki_torrents` (
  `RevisionID` int NOT NULL AUTO_INCREMENT,
  `PageID` int NOT NULL DEFAULT '0',
  `Body` text,
  `UserID` int NOT NULL DEFAULT '0',
  `Summary` varchar(100) DEFAULT NULL,
  `Time` datetime,
  `Image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`RevisionID`),
  KEY `PageID` (`PageID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `xbt_client_whitelist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `peer_id` varchar(25) DEFAULT NULL,
  `vstring` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `peer_id` (`peer_id`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `xbt_files_users` (
  `uid` int NOT NULL,
  `active` tinyint NOT NULL DEFAULT '0',
  `announced` int NOT NULL,
  `completed` tinyint NOT NULL DEFAULT '0',
  `downloaded` bigint NOT NULL DEFAULT '0',
  `remaining` bigint NOT NULL DEFAULT '0',
  `uploaded` bigint NOT NULL DEFAULT '0',
  `upspeed` int unsigned NOT NULL DEFAULT '0',
  `downspeed` int unsigned NOT NULL DEFAULT '0',
  `corrupt` bigint NOT NULL DEFAULT '0',
  `timespent` int unsigned NOT NULL,
  `useragent` varchar(51) NOT NULL DEFAULT '',
  `connectable` tinyint NOT NULL DEFAULT '1',
  `peer_id` binary(20) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fid` int NOT NULL,
  `mtime` int NOT NULL,
  `ip` varchar(15) NOT NULL DEFAULT '', -- Max IPv4 address length
  `seeder` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`peer_id`,`fid`,`uid`),
  KEY `remaining_idx` (`remaining`),
  KEY `fid_idx` (`fid`),
  KEY `mtime_idx` (`mtime`),
  KEY `uid_active` (`uid`,`active`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `xbt_snatched` (
  `uid` int NOT NULL DEFAULT '0',
  `tstamp` int NOT NULL,
  `fid` int NOT NULL,
  `IP` varchar(15) NOT NULL, -- Max IPv4 address length
  `seedtime` int NOT NULL DEFAULT '0',
  KEY `fid` (`fid`),
  KEY `tstamp` (`tstamp`),
  KEY `uid_tstamp` (`uid`,`tstamp`)
) ENGINE=InnoDB CHARSET=utf8mb4;


CREATE TABLE `openai` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `jobId` VARCHAR(128) NOT NULL,
    `groupId` INT NOT NULL,
    `object` VARCHAR(32),
    `created` DATETIME DEFAULT NOW(),
    `updated` DATETIME DEFAULT NOW() ON UPDATE CURRENT_TIMESTAMP,
    `model` VARCHAR(32),
    `text` TEXT,
    `index` TINYINT,
    `logprobs` TINYINT,
    `finishReason` VARCHAR(16),
    `promptTokens` SMALLINT,
    `completionTokens` SMALLINT,
    `totalTokens` SMALLINT,
    `failCount` TINYINT DEFAULT 0,
    `json` JSON,
    `type` VARCHAR(16),
    PRIMARY KEY (`id`,`jobId`,`groupId`)
);

-- Okay, that's all for the schema structure
-- Now we have the default values to initialize the DB with
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `permissions` (`ID`, `Level`, `Name`, `Values`, `DisplayStaff`) VALUES
  (15, 1000, 'Sysop', 'a:100:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:19:\"site_advanced_top10\";i:1;s:16:\"site_album_votes\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:20:\"site_collages_delete\";i:1;s:23:\"site_collages_subscribe\";i:1;s:22:\"site_collages_personal\";i:1;s:28:\"site_collages_renamepersonal\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:22:\"site_can_invite_always\";i:1;s:27:\"site_send_unlimited_invites\";i:1;s:22:\"site_moderate_requests\";i:1;s:18:\"site_delete_artist\";i:1;s:20:\"site_moderate_forums\";i:1;s:17:\"site_admin_forums\";i:1;s:23:\"site_forums_double_post\";i:1;s:14:\"site_view_flow\";i:1;s:18:\"site_view_full_log\";i:1;s:28:\"site_view_torrent_snatchlist\";i:1;s:18:\"site_recommend_own\";i:1;s:27:\"site_manage_recommendations\";i:1;s:15:\"site_delete_tag\";i:1;s:23:\"site_disable_ip_history\";i:1;s:14:\"zip_downloader\";i:1;s:10:\"site_debug\";i:1;s:17:\"site_proxy_images\";i:1;s:16:\"site_search_many\";i:1;s:20:\"users_edit_usernames\";i:1;s:16:\"users_edit_ratio\";i:1;s:20:\"users_edit_own_ratio\";i:1;s:17:\"users_edit_titles\";i:1;s:18:\"users_edit_avatars\";i:1;s:18:\"users_edit_invites\";i:1;s:22:\"users_edit_watch_hours\";i:1;s:21:\"users_edit_reset_keys\";i:1;s:19:\"users_edit_profiles\";i:1;s:18:\"users_view_friends\";i:1;s:20:\"users_reset_own_keys\";i:1;s:19:\"users_edit_password\";i:1;s:19:\"users_promote_below\";i:1;s:16:\"users_promote_to\";i:1;s:16:\"users_give_donor\";i:1;s:10:\"users_warn\";i:1;s:19:\"users_disable_users\";i:1;s:19:\"users_disable_posts\";i:1;s:17:\"users_disable_any\";i:1;s:18:\"users_delete_users\";i:1;s:18:\"users_view_invites\";i:1;s:20:\"users_view_seedleech\";i:1;s:19:\"users_view_uploaded\";i:1;s:15:\"users_view_keys\";i:1;s:14:\"users_view_ips\";i:1;s:16:\"users_view_email\";i:1;s:18:\"users_invite_notes\";i:1;s:23:\"users_override_paranoia\";i:1;s:12:\"users_logout\";i:1;s:20:\"users_make_invisible\";i:1;s:9:\"users_mod\";i:1;s:13:\"torrents_edit\";i:1;s:15:\"torrents_delete\";i:1;s:20:\"torrents_delete_fast\";i:1;s:18:\"torrents_freeleech\";i:1;s:20:\"torrents_search_fast\";i:1;i:1;s:19:\"torrents_fix_ghosts\";i:1;s:17:\"admin_manage_news\";i:1;s:17:\"admin_manage_blog\";i:1;s:18:\"admin_manage_polls\";i:1;s:19:\"admin_manage_forums\";i:1;s:16:\"admin_manage_fls\";i:1;s:13:\"admin_reports\";i:1;s:26:\"admin_advanced_user_search\";i:1;i:1;s:15:\"admin_donor_log\";i:1;s:19:\"admin_manage_ipbans\";i:1;i:1;s:17:\"admin_clear_cache\";i:1;s:15:\"admin_whitelist\";i:1;s:24:\"admin_manage_permissions\";i:1;s:14:\"admin_schedule\";i:1;s:17:\"admin_login_watch\";i:1;s:17:\"admin_manage_wiki\";i:1;i:1;s:21:\"site_collages_recover\";i:1;s:19:\"torrents_add_artist\";i:1;s:13:\"edit_unknowns\";i:1;s:19:\"forums_polls_create\";i:1;s:21:\"forums_polls_moderate\";i:1;s:12:\"project_team\";i:1;s:25:\"torrents_edit_vanityhouse\";i:1;s:23:\"artist_edit_vanityhouse\";i:1;s:21:\"site_tag_aliases_read\";i:1;}', '1'),
  (11, 800, 'Moderator', 'a:89:{s:26:\"admin_advanced_user_search\";i:1;s:17:\"admin_clear_cache\";i:1;i:1;i:1;s:15:\"admin_donor_log\";i:1;s:17:\"admin_login_watch\";i:1;s:17:\"admin_manage_blog\";i:1;s:19:\"admin_manage_ipbans\";i:1;s:17:\"admin_manage_news\";i:1;s:18:\"admin_manage_polls\";i:1;s:17:\"admin_manage_wiki\";i:1;s:13:\"admin_reports\";i:1;s:23:\"artist_edit_vanityhouse\";i:1;s:13:\"edit_unknowns\";i:1;s:19:\"forums_polls_create\";i:1;s:21:\"forums_polls_moderate\";i:1;s:12:\"project_team\";i:1;s:17:\"site_admin_forums\";i:1;s:20:\"site_advanced_search\";i:1;s:19:\"site_advanced_top10\";i:1;s:16:\"site_album_votes\";i:1;s:22:\"site_can_invite_always\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_delete\";i:1;s:20:\"site_collages_manage\";i:1;s:22:\"site_collages_personal\";i:1;s:21:\"site_collages_recover\";i:1;s:28:\"site_collages_renamepersonal\";i:1;s:23:\"site_collages_subscribe\";i:1;s:18:\"site_delete_artist\";i:1;s:15:\"site_delete_tag\";i:1;s:23:\"site_disable_ip_history\";i:1;s:14:\"site_edit_wiki\";i:1;s:23:\"site_forums_double_post\";i:1;s:10:\"site_leech\";i:1;s:19:\"site_make_bookmarks\";i:1;s:27:\"site_manage_recommendations\";i:1;s:20:\"site_moderate_forums\";i:1;s:22:\"site_moderate_requests\";i:1;s:17:\"site_proxy_images\";i:1;s:18:\"site_recommend_own\";i:1;s:16:\"site_search_many\";i:1;s:27:\"site_send_unlimited_invites\";i:1;s:20:\"site_submit_requests\";i:1;s:21:\"site_tag_aliases_read\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:11:\"site_upload\";i:1;s:14:\"site_view_flow\";i:1;s:18:\"site_view_full_log\";i:1;s:28:\"site_view_torrent_snatchlist\";i:1;s:9:\"site_vote\";i:1;s:19:\"torrents_add_artist\";i:1;s:15:\"torrents_delete\";i:1;s:20:\"torrents_delete_fast\";i:1;s:13:\"torrents_edit\";i:1;s:25:\"torrents_edit_vanityhouse\";i:1;s:19:\"torrents_fix_ghosts\";i:1;s:18:\"torrents_freeleech\";i:1;i:1;s:20:\"torrents_search_fast\";i:1;s:18:\"users_delete_users\";i:1;s:17:\"users_disable_any\";i:1;s:19:\"users_disable_posts\";i:1;s:19:\"users_disable_users\";i:1;s:18:\"users_edit_avatars\";i:1;s:18:\"users_edit_invites\";i:1;s:20:\"users_edit_own_ratio\";i:1;s:19:\"users_edit_password\";i:1;s:19:\"users_edit_profiles\";i:1;s:16:\"users_edit_ratio\";i:1;s:21:\"users_edit_reset_keys\";i:1;s:17:\"users_edit_titles\";i:1;s:16:\"users_give_donor\";i:1;s:12:\"users_logout\";i:1;s:20:\"users_make_invisible\";i:1;s:9:\"users_mod\";i:1;s:23:\"users_override_paranoia\";i:1;s:19:\"users_promote_below\";i:1;s:20:\"users_reset_own_keys\";i:1;s:10:\"users_warn\";i:1;s:16:\"users_view_email\";i:1;s:18:\"users_view_friends\";i:1;s:18:\"users_view_invites\";i:1;s:14:\"users_view_ips\";i:1;s:15:\"users_view_keys\";i:1;s:20:\"users_view_seedleech\";i:1;s:19:\"users_view_uploaded\";i:1;s:14:\"zip_downloader\";i:1;}', '1'),
  (2, 100, 'User', 'a:7:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:14:\"site_edit_wiki\";i:1;s:19:\"torrents_add_artist\";i:1;}', '0'),
  (3, 150, 'Member', 'a:10:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_collages_manage\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:19:\"torrents_add_artist\";i:1;}', '0'),
  (4, 200, 'Power User', 'a:14:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:14:\"zip_downloader\";i:1;s:19:\"forums_polls_create\";i:1;s:19:\"torrents_add_artist\";i:1;} ', '0'),
  (5, 250, 'Elite', 'a:18:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:19:\"site_advanced_top10\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:15:\"site_delete_tag\";i:1;s:14:\"zip_downloader\";i:1;s:19:\"forums_polls_create\";i:1;s:13:\"torrents_edit\";i:1;s:19:\"torrents_add_artist\";i:1;s:17:\"admin_clear_cache\";i:1;}', '0'),
  (20, 202, 'Donor', 'a:9:{s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:14:\"zip_downloader\";i:1;s:19:\"forums_polls_create\";i:1;}', '0'),
  (19, 201, 'Artist', 'a:9:{s:10:\"site_leech\";s:1:\"1\";s:11:\"site_upload\";s:1:\"1\";s:9:\"site_vote\";s:1:\"1\";s:20:\"site_submit_requests\";s:1:\"1\";s:20:\"site_advanced_search\";s:1:\"1\";s:10:\"site_top10\";s:1:\"1\";s:19:\"site_make_bookmarks\";s:1:\"1\";s:14:\"site_edit_wiki\";s:1:\"1\";s:18:\"site_recommend_own\";s:1:\"1\";}', '0');


INSERT INTO `stylesheets` (`ID`, `Name`, `Description`, `Default`, `Additions`, `Color`) VALUES
  (1, 'bookish', 'BioTorrents.de Stylesheet', '1', 'select=noto_sans;select=luxi_sans;select=noto_serif;select=luxi_serif;select=opendyslexic;select=comic_neue;checkbox=matcha', '#000000'),
  (2, 'postmod', 'What.cd Stylesheet', '0', 'select=noto_sans;select=luxi_sans;select=noto_serif;select=luxi_serif;select=opendyslexic;select=comic_neue;', '#000000'),


INSERT INTO `wiki_articles` (`ID`, `Revision`, `Title`, `Body`, `MinClassRead`, `MinClassEdit`, `Date`, `Author`) VALUES
  (1, 1, 'Wiki', 'Welcome to your new wiki! Hope this works.', 100, 475, NOW(), 1);


INSERT INTO `wiki_aliases` (`Alias`, `UserID`, `ArticleID`) VALUES ('wiki', 1, 1);


INSERT INTO `wiki_revisions` (`ID`, `Revision`, `Title`, `Body`, `Date`, `Author`) VALUES
  (1, 1, 'Wiki', 'Welcome to your new wiki! Hope this works.', NOW(), 1);


INSERT INTO `forums` (`ID`, `CategoryID`, `Sort`, `Name`, `Description`, `MinClassRead`, `MinClassWrite`, `MinClassCreate`, `NumTopics`, `NumPosts`, `LastPostID`, `LastPostAuthorID`, `LastPostTopicID`, `LastPostTime`) VALUES
  (1, 1, 20, 'Your Site', 'Totally rad forum', 100, 100, 100, 0, 0, 0, 0, 0, NULL),
  (2, 5, 30, 'Chat', 'Expect this to fill up with spam', 100, 100, 100, 0, 0, 0, 0, 0, NULL),
  (3, 10, 40, 'Help!', 'I fell down and I cant get up', 100, 100, 100, 0, 0, 0, 0, 0, NULL),
  (4, 20, 100, 'Trash', 'Every thread ends up here eventually', 100, 500, 500, 0, 0, 0, 0, 0, NULL);


INSERT INTO `tags` (`ID`, `Name`, `TagType`, `Uses`, `UserID`) VALUES
  (1, 'one', 'genre', 0, 1),
  (2, 'two', 'genre', 0, 1),
  (3, 'three', 'genre', 0, 1),
  (4, 'four', 'genre', 0, 1),
  (5, 'five', 'genre', 0, 1);


INSERT INTO `schedule` (`NextHour`, `NextDay`, `NextBiWeekly`) VALUES (0,0,0);


INSERT INTO `forums_categories` (`ID`, `Sort`, `Name`) VALUES (1,1,'Site');


INSERT INTO `forums_categories` (`ID`, `Sort`, `Name`) VALUES (5,5,'Community');


INSERT INTO `forums_categories` (`ID`, `Sort`, `Name`) VALUES (10,10,'Help');


INSERT INTO `forums_categories` (`ID`, `Sort`, `Name`) VALUES (8,8,'Music');


INSERT INTO `forums_categories` (`ID`, `Sort`, `Name`) VALUES (20,20,'Trash');


INSERT INTO `misc` (`ID`, `Name`, `First`, `Second`) VALUES (1, 'FreeleechPool', '100', '200');


-- One last thing: a trigger to update seeding stats
DELIMITER ;;
CREATE TRIGGER update_seedtime
  AFTER UPDATE ON `xbt_files_users`
  FOR EACH ROW BEGIN
    IF ( (OLD.timespent < NEW.timespent) AND (OLD.active = 1) AND (NEW.active = 1) ) THEN
      INSERT INTO `users_seedtime`
        (`UserID`, `TorrentID`, `SeedTime`, `Uploaded`, `Downloaded`, `LastUpdate`)
        VALUES
        (NEW.uid, NEW.fid, NEW.timespent, NEW.uploaded, NEW.downloaded, NOW())
        ON DUPLICATE KEY UPDATE
          `SeedTime` = `SeedTime` + (NEW.timespent - OLD.timespent),
          `Uploaded` = `Uploaded` + (NEW.uploaded - OLD.uploaded),
          `Downloaded` = `Downloaded` + (NEW.downloaded - OLD.downloaded),
          `LastUpdate` = NOW();
    END IF;
  END;;
DELIMITER ;
