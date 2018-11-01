-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2018-11-01 21:41:36
-- 服务器版本： 5.5.61-log
-- PHP Version: 7.2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `LotteryBot`
--

-- --------------------------------------------------------

--
-- 表的结构 `lottery_list`
--

CREATE TABLE IF NOT EXISTS `lottery_list` (
  `id` int(11) NOT NULL COMMENT 'id',
  `number` bigint(11) NOT NULL COMMENT '抽奖编号',
  `token` text NOT NULL COMMENT 'Join 抽奖令牌',
  `title` text NOT NULL COMMENT '抽奖标题',
  `details` mediumtext NOT NULL COMMENT '抽奖详情',
  `prize` int(11) NOT NULL COMMENT '奖品数',
  `smart` tinyint(1) NOT NULL DEFAULT '0' COMMENT '智能概率控制',
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

CREATE TABLE IF NOT EXISTS `lottery_tpl` (
  `id` int(11) NOT NULL COMMENT '自增id',
  `user_id` int(11) NOT NULL COMMENT 'Telegram User ID',
  `username` text NOT NULL,
  `first_name` text NOT NULL,
  `last_name` text NOT NULL,
  `probability` decimal(10,8) NOT NULL DEFAULT '1.00000000' COMMENT '中奖率',
  `join_time` int(11) NOT NULL COMMENT '参与时间戳',
  `lang_code` text NOT NULL COMMENT 'language_code',
  `win` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否中奖'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lottery_list`
--
ALTER TABLE `lottery_list`
  ADD PRIMARY KEY (`id`,`number`);

--
-- Indexes for table `lottery_tpl`
--
ALTER TABLE `lottery_tpl`
  ADD PRIMARY KEY (`id`,`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lottery_list`
--
ALTER TABLE `lottery_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id';
--
-- AUTO_INCREMENT for table `lottery_tpl`
--
ALTER TABLE `lottery_tpl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id';
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
