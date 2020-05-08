SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS  `zhx_corp`;
CREATE TABLE `zhx_corp` (
  `corpid` varchar(32) NOT NULL COMMENT '授权方企业微信id',
  `corp_name` varchar(64) NOT NULL COMMENT '授权方企业微信名称',
  `corp_type` varchar(32) NOT NULL DEFAULT 'unverified' COMMENT '授权方企业微信类型，认证号：verified, 注册号：unverified',
  `corp_square_logo_url` varchar(255) NOT NULL DEFAULT '' COMMENT '授权方企业微信方形头像',
  `corp_user_max` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '授权方企业微信用户规模',
  `corp_agent_max` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '授权方企业应用数上限',
  `corp_full_name` varchar(64) NOT NULL DEFAULT '' COMMENT '所绑定的企业微信主体名称',
  `verified_end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '认证到期时间',
  `subject_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '企业类型，1. 企业; 2. 政府以及事业单位; 3. 其他组织, 4.团队号',
  `corp_wxqrcode` varchar(255) NOT NULL DEFAULT '' COMMENT '授权方企业微信二维码',
  `corp_scale` varchar(32) NOT NULL DEFAULT '' COMMENT '企业规模',
  `corp_industry` varchar(32) NOT NULL DEFAULT '' COMMENT '企业所属行业',
  `corp_sub_industry` varchar(128) NOT NULL DEFAULT '' COMMENT '企业所属子行业',
  `location` varchar(128) NOT NULL DEFAULT '' COMMENT '企业所在地信息',
  PRIMARY KEY (`corpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='企业微信帐号记录表';

DROP TABLE IF EXISTS  `zhx_corp_agent`;
CREATE TABLE `zhx_corp_agent` (
  `corpid` varchar(32) NOT NULL COMMENT '授权方企业微信id',
  `agentid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '安装应用ID',
  `name` varchar(64) NOT NULL COMMENT '授权方应用名字',
  `round_logo_url` varchar(255) NOT NULL DEFAULT '' COMMENT '授权方应用圆形头像',
  `square_logo_url` varchar(255) NOT NULL DEFAULT '' COMMENT '授权方应用方形头像',
  `permanent_code` varchar(512) NOT NULL COMMENT '企业微信永久授权码',
  PRIMARY KEY (`corpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='企业授权应用信息';

DROP TABLE IF EXISTS  `zhx_corp_privilege`;
CREATE TABLE `zhx_corp_privilege` (
  `corpid` varchar(32) NOT NULL COMMENT '授权方企业微信id',
  `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT '权限等级(1:通讯录基本信息只读;2:通讯录全部信息只读;3:通讯录全部信息读写;4:单个基本信息只读;5:通讯录全部信息只写)',
  `allow_party` text NOT NULL COMMENT '应用可见范围（部门）',
  `allow_tag` text NOT NULL COMMENT '应用可见范围（标签）',
  `allow_user` text NOT NULL COMMENT '应用可见范围（成员）',
  `extra_party` text NOT NULL COMMENT '额外通讯录（部门）',
  `extra_user` text NOT NULL COMMENT '额外通讯录（成员）',
  `extra_tag` text NOT NULL COMMENT '额外通讯录（标签）',
  PRIMARY KEY (`corpid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='企业授权应用可见范围';

DROP TABLE IF EXISTS  `zhx_corp_history`;
CREATE TABLE `zhx_corp_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `corpid` varchar(32) NOT NULL COMMENT '授权方企业微信id',
  `opuser` varchar(64) NOT NULL DEFAULT '' COMMENT '操作用户userid',
  `optime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
  `optype` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '操作类型',
  `opdata` text NOT NULL COMMENT '操作数据',
  PRIMARY KEY (`id`),
  KEY `INX_CORPID` (`corpid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='企业微信安装历史记录';

CREATE TABLE `zhx_venue_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '原文件名称',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '远程地址',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '保存文件路径',
  `ext` varchar(10) NOT NULL DEFAULT '' COMMENT '文件后缀',
  `mime_type` varchar(100) NOT NULL DEFAULT '' COMMENT '文件mime类型',
  `size` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `width` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片宽度',
  `height` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片高度',
  `md5` varchar(32) NOT NULL DEFAULT '' COMMENT '文件md5',
  `sha1` varchar(40) NOT NULL DEFAULT '' COMMENT '文件 sha1编码',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`), 
  KEY `INX_MD5_SHA` (`md5`, `sha1`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='文件记录表';

DROP TABLE IF EXISTS  `zhx_venue_school`;
CREATE TABLE `zhx_venue_school` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '学校名称',
  `appid` varchar(32) DEFAULT NULL COMMENT '授权公众号appid',
  `orgid` varchar(32) DEFAULT NULL COMMENT '授权智慧校园OrgId',
  `corpid` varchar(32) DEFAULT NULL COMMENT '授权企业微信corpid',
  `province_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '省级id',
  `city_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '市级id',
  `area_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '县级/地区id',
  `town_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '乡镇/街道id',
  `latitude` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '学校地理位置经度',
  `longitude` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '学校地理位置纬度',
  `config` varchar(255) NOT NULL DEFAULT '' COMMENT '配置(学校配置信息，json数据)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0:停用;1:正常;)',
  `expire_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '授权过期时间',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_APPID` (`appid`) USING BTREE,
  UNIQUE KEY `UNQ_ORGID` (`orgid`) USING BTREE,
  UNIQUE KEY `UNQ_CORPID` (`corpid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆学校记录表';

DROP TABLE IF EXISTS  `zhx_venue_user`;
CREATE TABLE `zhx_venue_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(32) NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(128) DEFAULT NULL COMMENT '用户邮箱',
  `mobile` varchar(16) DEFAULT NULL COMMENT '用户手机',
  `openuserid` varchar(32) DEFAULT NULL COMMENT '智慧校园用户唯一id',
  `corpid` varchar(32) DEFAULT NULL COMMENT '企业微信corpid',
  `userid` varchar(32) DEFAULT NULL COMMENT '企业微信userid',
  `openid` varchar(32) NOT NULL DEFAULT '' COMMENT '企业微信openid',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `gender` tinyint(4) NOT NULL DEFAULT '0' COMMENT '性别(0:未设置;1:男;2:女;)',
  `birthday` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生日',
  `areaid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '地区',
  `cityid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '城市',
  `proviceid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '省份',
  `type` tinyint(4) NOT NULL DEFAULT '5' COMMENT '类别(0:系统超级管理员;1:系统客服;5:普通用户;)',
  `password` varchar(60) NOT NULL DEFAULT '' COMMENT '密码',
  `last_login_ip` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录IP',
  `last_login_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `login_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `check_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '错误操作限制次数',
  `ban_expire` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '禁用到期时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态(0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `created_ip` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建IP',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建用户id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新用户id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_OPENUSERID` (`openuserid`) USING BTREE,
  UNIQUE KEY `UNQ_CORPID_USERID` (`corpid`,`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户表';

DROP TABLE IF EXISTS  `zhx_venue_user_log`;
CREATE TABLE `zhx_venue_user_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `opip` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录ip地址',
  `optype` tinyint(4) NOT NULL DEFAULT '0' COMMENT '操作类型(0-登录;)',
  `opdata` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作整型数据',
  `content` varchar(1023) NOT NULL DEFAULT '' COMMENT '操作内容',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建用户id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新用户id',
  PRIMARY KEY (`id`),
  KEY `INX_USERID` (`created_by`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户操作记录表';

DROP TABLE IF EXISTS  `zhx_venue_visitor`;
CREATE TABLE `zhx_venue_visitor` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` char(32) NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` char(18) NOT NULL DEFAULT '' COMMENT '手机号码',
  `id_number` char(24) NOT NULL DEFAULT '' COMMENT '身份证号码',
  `openid` varchar(128) NOT NULL DEFAULT '' COMMENT 'openid',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `gender` tinyint NOT NULL DEFAULT '0' COMMENT '性别(1:男;2:女;)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_OPENID` (`openid`) USING BTREE,
  KEY `INX_MOBILE` (`mobile`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='小程序访客记录表';

DROP TABLE IF EXISTS  `zhx_venue_visitor_ban`;
CREATE TABLE `zhx_venue_visitor_ban` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `visitor_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访客记录ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `INX_SCHOOLID` (`school_id`) USING BTREE,
  KEY `INX_VISITORID` (`visitor_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='小程序访客禁用记录表';

DROP TABLE IF EXISTS  `zhx_venue_member`;
CREATE TABLE `zhx_venue_member` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户记录ID',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '成员姓名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '成员头像',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_SCHOOLID_USERID` (`school_id`,`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆成员表';

DROP TABLE IF EXISTS  `zhx_venue_role`;
CREATE TABLE `zhx_venue_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '名称',
  `desc` varchar(128) NOT NULL DEFAULT '' COMMENT '描述',
  `type` tinyint NOT NULL DEFAULT '6' COMMENT '类型(1:管理员;2:安保人员;6:自定义角色;)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_SCHOOLID_NAME` (`school_id`, `name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆角色表';

DROP TABLE IF EXISTS  `zhx_venue_role_auth`;
CREATE TABLE `zhx_venue_role_auth` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `rid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `module` tinyint NOT NULL DEFAULT '0' COMMENT '模块',
  `auth_data` tinyint NOT NULL DEFAULT '0' COMMENT '权限数据',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `INX_RID` (`rid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆角色权限表';

DROP TABLE IF EXISTS  `zhx_venue_role_member`;
CREATE TABLE `zhx_venue_role_member` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `rid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `mid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '成员ID',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `IDX_MID` (`mid`) USING BTREE, 
  UNIQUE KEY `UNQ_ROLEID_MEMID` (`rid`, `mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆角色成员表';

DROP TABLE IF EXISTS  `zhx_venue_type`;
CREATE TABLE `zhx_venue_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '类型标题',
  `position` tinyint NOT NULL DEFAULT '0' COMMENT '标志位(bit1:系统默认类型;)',
  `sort` tinyint NOT NULL DEFAULT '0' COMMENT '排序值',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_TITLE` (`title`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆类型记录表';
INSERT INTO `zhx_venue_type` (`id`, `title`, `position`, `sort`, `status`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, '篮球场', 1, 0, 1, 1581134529, 0, 1581134529, 0),
(2, '足球场', 1, 0, 1, 1581134529, 0, 1581134529, 0),
(3, '网球场', 1, 0, 1, 1581134529, 0, 1581134529, 0),
(4, '乒乓球场', 1, 0, 1, 1581134529, 0, 1581134529, 0),
(5, '羽毛球场', 1, 0, 1, 1581134529, 0, 1581134529, 0),
(6, '室外篮球场', 0, 0, 1, 1581501898, 4, 1581501898, 4);

DROP TABLE IF EXISTS  `zhx_venue_school_type`;
CREATE TABLE `zhx_venue_school_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `type_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '类型记录ID',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `INX_SCHOOLID_TYPEID` (`school_id`, `type_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='学校场馆类型记录表';

DROP TABLE IF EXISTS  `zhx_venue`;
CREATE TABLE `zhx_venue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '场馆类型',
  `open_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '开放时间(每bit代表半小时，有效位数48)',
  `max_continuous` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '最大连续开放时间(单位半小时)',
  `limit_ordertime` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '单用户单次预约时间限制(单位半小时)',
  `option` tinyint NOT NULL DEFAULT '0' COMMENT '标志位(bit1:是否关闭;bit2:是否室外场地;)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `INX_SCHOOLID` (`school_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆记录表';

DROP TABLE IF EXISTS  `zhx_venue_facility`;
CREATE TABLE `zhx_venue_facility` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `venue_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '场馆记录ID',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '场馆类型',
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '设施名称',
  `open_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '开放时间(每bit代表半小时，有效位数48)',
  `max_continuous` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '最大连续开放时间(单位半小时)',
  `limit_ordertime` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '单用户单次预约时间限制(单位半小时)',
  `option` tinyint NOT NULL DEFAULT '0' COMMENT '标志位(bit1:是否关闭;bit2:是否室外场地;)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `INX_VENUEID` (`venue_id`) USING BTREE,
  KEY `INX_SCHOOLID` (`school_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆设施记录表';

DROP TABLE IF EXISTS  `zhx_venue_image`;
CREATE TABLE `zhx_venue_image` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `venue_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '场馆记录ID',
  `image_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片记录ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `INX_VENUEID` (`venue_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆图片记录表';

DROP TABLE IF EXISTS  `zhx_venue_order`;
CREATE TABLE `zhx_venue_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `school_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学校记录ID',
  `visitor_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访客记录ID',
  `venue_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '场地记录ID',
  `facility_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '设施记录ID',
  `odate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '预约的日期',
  `open_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '预约时间(每bit代表半小时，有效位数48)',
  `people_counts` tinyint NOT NULL DEFAULT '1' COMMENT '到场人数',
  `process` tinyint NOT NULL DEFAULT '0' COMMENT '状态(-1:已取消;0:待审核;1:待签到;2:待签退;3:已退订;4:已签退;5:已拒绝;)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(-1:删除;0:禁用;1:正常;)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `INX_VENUEID` (`venue_id`) USING BTREE,
  KEY `INX_SCHOOLID` (`school_id`) USING BTREE,
  KEY `INX_VISITORID` (`visitor_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆预约记录表';

DROP TABLE IF EXISTS  `zhx_venue_order_history`;
CREATE TABLE `zhx_venue_order_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '预约记录ID',
  `visitor_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访客记录ID',
  `optype` tinyint NOT NULL DEFAULT '0' COMMENT '操作类型',
  `optime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
  `position` tinyint NOT NULL DEFAULT '0' COMMENT '标志位,不同操作类型不同的标志',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人id',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `updated_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  PRIMARY KEY (`id`),
  KEY `INX_ORDERID` (`order_id`) USING BTREE,
  KEY `INX_VISITORID` (`visitor_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='场馆预约操作记录表';

SET FOREIGN_KEY_CHECKS = 1;
