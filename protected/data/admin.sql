CREATE TABLE IF NOT EXISTS `v1_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `password` varchar(50) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `realname` varchar(20) NOT NULL DEFAULT '',
  `isBlock` tinyint(4) NOT NULL COMMENT '0: 有效用户, 1: 被封账号',
  `mobile` varchar(11) NOT NULL DEFAULT '',
  `depID` smallint(6) NOT NULL DEFAULT '0' COMMENT '部门ID',
  `jobID` smallint(6) NOT NULL DEFAULT '0' COMMENT '职务ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=27 ;