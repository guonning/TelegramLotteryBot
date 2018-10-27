-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- 主机： 52.193.196.168:3306
-- 生成日期： 2018-10-27 13:40:47
-- 服务器版本： 5.6.41-log
-- PHP 版本： 7.2.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `LotteryBot`
--

-- --------------------------------------------------------

--
-- 表的结构 `lottery_list`
--

CREATE TABLE `lottery_list` (
  `id` int(11) NOT NULL COMMENT 'id',
  `number` bigint(11) NOT NULL COMMENT '抽奖编号',
  `token` text NOT NULL COMMENT 'Join 抽奖令牌',
  `title` text NOT NULL COMMENT '抽奖标题',
  `details` mediumtext NOT NULL COMMENT '抽奖详情',
  `prize` int(11) NOT NULL COMMENT '奖品数',
  `req_uid` int(11) NOT NULL COMMENT '创建者 Telegram ID',
  `req_username` text NOT NULL COMMENT '创建者tg用户名',
  `req_firstname` text NOT NULL COMMENT '创建者 first name',
  `timestamp` int(11) NOT NULL COMMENT '创建时间',
  `extracted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已开奖',
  `closed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否关闭，开奖同时为0才为进行中'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `lottery_tpl`
--

CREATE TABLE `lottery_tpl` (
  `id` int(11) NOT NULL COMMENT '自增id',
  `user_id` int(11) NOT NULL COMMENT 'Telegram User ID',
  `username` text NOT NULL,
  `first_name` text NOT NULL,
  `last_name` text NOT NULL,
  `join_time` int(11) NOT NULL COMMENT '参与时间戳',
  `lang_code` text NOT NULL COMMENT 'language_code',
  `win` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否中奖'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转储表的索引
--

--
-- 表的索引 `lottery_list`
--
ALTER TABLE `lottery_list`
  ADD PRIMARY KEY (`id`,`number`);

--
-- 表的索引 `lottery_tpl`
--
ALTER TABLE `lottery_tpl`
  ADD PRIMARY KEY (`id`,`user_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `lottery_list`
--
ALTER TABLE `lottery_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- 使用表AUTO_INCREMENT `lottery_tpl`
--
ALTER TABLE `lottery_tpl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
