-- --
-- manticore search engine schema
--


-- manticore_counter
create table `manticore_counter` (
  `type` varchar(16) not null,
  `lastTouch` int,

  primary key (`type`)
) engine=InnoDB charset=utf8mb4;


-- manticore_torrents_group
create table `manticore_torrents_group` (
  `id` int not null,
  `categoryId` tinyint,
  `title` varchar(255),
  `subject` varchar(255),
  `object` varchar(255),
  `year` smallint,
  `workgroup` varchar(128),
  `location` varchar(128),
  `identifier` varchar(64),
  `tagList` text,
  `groupDescription` text,
  `picture` varchar(255),

  -- torrents_artists
  `creatorList` text,

  primary key (`id`)
) engine=InnoDB charset=utf8mb4;


-- manticore_torrents
create table `manticore_torrents` (
  `id` int not null,
  `groupId` int not null,
  `userId` int,
  `platform` varchar(32),
  `format` varchar(32),
  `license` varchar(32),
  `scope` varchar(32),
  `version` varchar(16),
  `aligned` tinyint,
  `infoHash` blob,
  `fileList` text,
  `size` bigint,
  `leechers` int not null,
  `seeders` int not null,
  `leechStatus` tinyint not null,
  `timeAdded` int,
  `torrentDescription` text,
  `snatches` int,
  `archive` varchar(16),

  primary key (`id`),
  key `groupId` (`groupId`)
) engine=InnoDB charset=utf8mb4;


-- manticore_torrents_delta
create table `manticore_torrents_delta` (
  -- torrents_group
  `id` int not null,
  `categoryId` tinyint,
  `title` varchar(255),
  `subject` varchar(255),
  `object` varchar(255),
  `year` smallint,
  `workgroup` varchar(128),
  `location` varchar(128),
  `identifier` varchar(64),
  `tagList` text,
  `groupDescription` text,
  `picture` varchar(255),

  -- torrents
  `groupId` int not null,
  `userId` int,
  `platform` varchar(32),
  `format` varchar(32),
  `license` varchar(32),
  `scope` varchar(32),
  `version` varchar(16),
  `aligned` tinyint,
  `infoHash` blob,
  `fileList` text,
  `size` bigint not null,
  `leechers` int,
  `seeders` int,
  `leechStatus` tinyint,
  `timeAdded` int,
  `torrentDescription` text,
  `snatches` int,
  `archive` varchar(16),

  -- torrents_artists
  `creatorList` text,

  primary key (`id`),
  key `groupId` (`groupId`)
) engine=InnoDB charset=utf8mb4;


-- manticore_requests
create table `manticore_requests` (
  -- requests
  `id` int not null,
  `userId` int,
  `timeAdded` int,
  `lastVote` int,
  `categoryId` smallint,
  `title` varchar(255),
  `subject` varchar(255),
  `object` varchar(255),
  `picture` varchar(255),
  `description` text,
  `identifier` varchar(64),
  `fillerId` int,
  `torrentId` int,
  `timeFilled` int,
  `visible` binary(1),

  -- requests_votes
  `bounty` bigint,
  `voteCount` bigint,

  -- requests_artists
  `creatorList` text,

  -- requests_tags
  `tagList` text,

  key (`id`)
) engine=InnoDB charset=utf8mb4;


-- manticore_requests_delta
create table `manticore_requests_delta` (
  -- requests
  `id` int not null,
  `userId` int,
  `timeAdded` int,
  `lastVote` int,
  `categoryId` smallint,
  `title` varchar(255),
  `subject` varchar(255),
  `object` varchar(255),
  `picture` varchar(255),
  `description` text,
  `identifier` varchar(64),
  `fillerId` int,
  `torrentId` int,
  `timeFilled` int,
  `visible` binary(1),

  -- requests_votes
  `bounty` bigint,
  `voteCount` bigint,

  -- requests_artists
  `creatorList` text,

  -- requests_tags
  `tagList` text,

  key (`id`)
) engine=InnoDB charset=utf8mb4;


-- --
-- to drop the tables
--

--drop table manticore_counter;
--drop table manticore_torrents_group;
--drop table manticore_torrents;
--drop table manticore_torrents_delta;
--drop table manticore_creators;
--drop table manticore_requests;
--drop table manticore_requests_delta;