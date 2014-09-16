<?php
$sql = QX_QUERY("CREATE TABLE IF NOT EXISTS `{$config['db_name']}`.`qx_gallery` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `desc` varchar(255) NOT NULL,
  `image` varchar(255) CHARACTER SET latin1 NOT NULL,
  `username` varchar(60) CHARACTER SET latin1 NOT NULL,
  `date` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image` (`image`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
?>