-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- 主機： localhost:3306
-- 產生時間： 2021 年 01 月 10 日 17:35
-- 伺服器版本： 10.0.38-MariaDB-0+deb8u1
-- PHP 版本： 7.3.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `hadname_longer`
--

-- --------------------------------------------------------

--
-- 資料表結構 `article`
--

CREATE TABLE `article` (
  `USER` text NOT NULL,
  `SERIAL` text NOT NULL,
  `PUBLISH` bigint(20) NOT NULL,
  `LAST_MODIFY` bigint(20) NOT NULL,
  `TITLE` text NOT NULL,
  `CLASSIFY` text NOT NULL,
  `TOP` bit(1) NOT NULL,
  `CONTENT` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `article_interactive`
--

CREATE TABLE `article_interactive` (
  `USER` text NOT NULL,
  `SERIAL` text NOT NULL,
  `TYPE` tinyint(1) NOT NULL COMMENT 'LIKE: 0 DISLIKE: 1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `article_star`
--

CREATE TABLE `article_star` (
  `USER` text NOT NULL,
  `SERIAL` text NOT NULL,
  `TIME` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `chatbox`
--

CREATE TABLE `chatbox` (
  `ID` varchar(32) NOT NULL,
  `chat` varchar(256) NOT NULL,
  `date` varchar(32) NOT NULL,
  `target` varchar(32) DEFAULT NULL,
  `seen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `classify`
--

CREATE TABLE `classify` (
  `ID` text NOT NULL,
  `NAME_TW` text NOT NULL,
  `NAME_CN` text NOT NULL,
  `NAME_EN` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `file`
--

CREATE TABLE `file` (
  `SERVER_NAME` text NOT NULL,
  `CLIENT_NAME` text NOT NULL,
  `FILE_TYPE` text NOT NULL,
  `OWNER` text NOT NULL,
  `LINK` text NOT NULL,
  `UPLOAD_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `moderator`
--

CREATE TABLE `moderator` (
  `USER` text NOT NULL,
  `CLASSIFY` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `notice`
--

CREATE TABLE `notice` (
  `NOTICE_SERIAL` bigint(20) UNSIGNED NOT NULL,
  `ID_FROM` text NOT NULL,
  `ID_TO` text NOT NULL,
  `TYPE` tinyint(4) NOT NULL,
  `LINK` text NOT NULL,
  `TIME` bigint(20) NOT NULL,
  `ALREADY_READ` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `punishment`
--

CREATE TABLE `punishment` (
  `SERIAL` bigint(20) UNSIGNED NOT NULL,
  `ID` text NOT NULL,
  `CLASSIFY_ID` text NOT NULL,
  `DEADLINE` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `reply`
--

CREATE TABLE `reply` (
  `ARTICLE_SERIAL` text NOT NULL,
  `USER` text NOT NULL,
  `SERIAL` bigint(20) UNSIGNED NOT NULL,
  `FLOOR` int(10) UNSIGNED NOT NULL,
  `TAG` text NOT NULL,
  `CONTENT` text NOT NULL,
  `TIME` bigint(20) NOT NULL,
  `LAST_MODIFY` bigint(20) NOT NULL,
  `LIKE_LIST` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `reply_interactive`
--

CREATE TABLE `reply_interactive` (
  `USER` text NOT NULL,
  `SERIAL` bigint(20) NOT NULL,
  `TYPE` tinyint(4) NOT NULL COMMENT 'LIKE:0 DISLIKE:1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `ID` text NOT NULL,
  `SALT` text NOT NULL,
  `PASSWORD` text NOT NULL,
  `NAME` text NOT NULL,
  `PROFILE` text NOT NULL,
  `EMAIL` text NOT NULL,
  `PERMISSION` bit(1) NOT NULL,
  `ONLINE` bigint(20) NOT NULL,
  `DIVING` tinyint(1) NOT NULL,
  `READTIME` bigint(20) NOT NULL COMMENT '打開inbox的時間',
  `LANGUAGE` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0: 繁中 1: 簡中'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `user_change_email`
--

CREATE TABLE `user_change_email` (
  `TOKEN` text NOT NULL,
  `ID` text NOT NULL,
  `NEW_EMAIL` text NOT NULL,
  `EXPIRE` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `user_forget_pwd`
--

CREATE TABLE `user_forget_pwd` (
  `TOKEN` text NOT NULL,
  `ID` text NOT NULL,
  `EXPIRE` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `user_more_info`
--

CREATE TABLE `user_more_info` (
  `ID` text NOT NULL,
  `BIRTHDAY` text NOT NULL,
  `HOBBY` text NOT NULL,
  `COME_FROM` text NOT NULL,
  `LINK` text NOT NULL,
  `BIO` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `notice`
--
ALTER TABLE `notice`
  ADD UNIQUE KEY `NOTICE_SERIAL` (`NOTICE_SERIAL`);

--
-- 資料表索引 `punishment`
--
ALTER TABLE `punishment`
  ADD UNIQUE KEY `SERIAL` (`SERIAL`);

--
-- 資料表索引 `reply`
--
ALTER TABLE `reply`
  ADD UNIQUE KEY `SERIAL` (`SERIAL`),
  ADD UNIQUE KEY `COMMENT_ID` (`SERIAL`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `notice`
--
ALTER TABLE `notice`
  MODIFY `NOTICE_SERIAL` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `punishment`
--
ALTER TABLE `punishment`
  MODIFY `SERIAL` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `reply`
--
ALTER TABLE `reply`
  MODIFY `SERIAL` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
