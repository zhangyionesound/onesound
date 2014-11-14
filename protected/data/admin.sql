DROP TABLE IF EXISTS `v1_admin`;
CREATE TABLE IF NOT EXISTS `v1_admin` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(30) NOT NULL,
`password` varchar(50) NOT NULL,
`rememberMe` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;