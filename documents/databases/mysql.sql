CREATE TABLE `Accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(140) NOT NULL,
  `password` varchar(32) NOT NULL,
  `username` varchar(30) NOT NULL,
  `activation_key` varchar(32) NOT NULL,
  `activated` smallint(1) NOT NULL DEFAULT '0',
  `date_registered` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `last_session_id` varchar(32) NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `Files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `title` varchar(250) NOT NULL,
  `uploaded` datetime NOT NULL,
  `url` text NOT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `FilesKeywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `keyword_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `Keywords` (
  `keyword_id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) NOT NULL,
  PRIMARY KEY (`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;