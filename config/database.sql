-- unfortunately contao doesnt support KEY or multiple KEY constraints

-- 
-- Table `tl_metafile`
-- 

CREATE TABLE `tl_metafile` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `published` char(1) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `folder` varchar(255) NOT NULL default '',
  `language` varchar(2) NOT NULL default '',
  `rte` varchar(255) NOT NULL default '',
  `metatype` char(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
--  UNIQUE KEY `uk_folder_language` (`folder`,`language`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Table `tl_metaitem`
-- 

CREATE TABLE `tl_metaitem` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `sorting` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `filename` varchar(255) NOT NULL default '',
  `title` varchar(255) NULL default '',
  `link` varchar(255) NULL default '',
  `description` text NULL,
  PRIMARY KEY  (`id`)
--  KEY `k_pid` (`pid`),
--  UNIQUE KEY `uk_filename_pid` (`filename`,`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
