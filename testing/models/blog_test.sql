-- phpMyAdmin SQL Dump
-- version 2.11.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 08, 2009 at 02:56 AM
-- Server version: 5.0.51
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `blog_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `blog_comments`
--

CREATE TABLE `blog_comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `blog_post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `blog_comments`
--

INSERT INTO `blog_comments` (`id`, `blog_post_id`, `user_id`, `text`) VALUES
(1, 1, 3, 'Just leaving a comment ya here!'),
(2, 1, 4, 'SAME!!!!'),
(3, 3, 1, 'I love this post. A LOT!');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `title` varchar(64) default NULL,
  `body` text NOT NULL,
  `edited_by` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=110 ;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `user_id`, `section_id`, `title`, `body`, `edited_by`, `parent_id`) VALUES
(1, 1, 1, 'First Post', 'Updated Body Yo = 7528', 2, 2),
(2, 1, 2, 'Second Post Thing', 'Hey Yo, Second Post HERE!', 2, 1),
(3, 2, 3, 'Wait, What? Thing', 'Yeah, not much here.', 2, 1),
(4, 3, 2, 'Nothing but a G Thing', 'How you doing?', 3, 0),
(5, 3, 1, 'I Don''t like you', 'Yeah TRUTH!', 3, 0),
(55, 1, 1, 'test title', 'test body', 1, 1),
(56, 1, 1, 'test save title', 'test save body', 1, 1),
(57, 1, 1, 'test title', 'test body', 1, 1),
(58, 1, 1, 'test save title', 'test save body', 1, 1),
(59, 1, 1, 'test title', 'test body', 1, 1),
(60, 1, 1, 'test save title', 'test save body', 1, 1),
(61, 1, 1, 'test title', 'test body', 1, 1),
(62, 1, 1, 'test save title', 'test save body', 1, 1),
(63, 1, 1, 'test title', 'test body', 1, 1),
(64, 1, 1, 'test save title', 'test save body', 1, 1),
(65, 1, 1, 'test title', 'test body', 1, 1),
(66, 1, 1, 'test save title', 'test save body', 1, 1),
(67, 1, 1, 'test title', 'test body', 1, 1),
(68, 1, 1, 'test save title', 'test save body', 1, 1),
(69, 1, 1, 'test title', 'test body', 1, 1),
(70, 1, 1, 'test save title', 'test save body', 1, 1),
(71, 1, 1, 'test title', 'test body', 1, 1),
(72, 1, 1, 'test save title', 'test save body', 1, 1),
(73, 1, 1, 'test title', 'test body', 1, 1),
(74, 1, 1, 'test save title', 'test save body', 1, 1),
(75, 1, 1, 'test title', 'test body', 1, 1),
(76, 1, 1, 'test save title', 'test save body', 1, 1),
(77, 1, 1, 'test title', 'test body', 1, 1),
(78, 1, 1, 'test save title', 'test save body', 1, 1),
(79, 1, 1, 'test title', 'test body', 1, 1),
(80, 1, 1, 'test save title', 'test save body', 1, 1),
(81, 1, 1, 'test title', 'test body', 1, 1),
(82, 1, 1, 'test save title', 'test save body', 1, 1),
(83, 1, 1, 'test title', 'test body', 1, 1),
(84, 1, 1, 'test save title', 'test save body', 1, 1),
(85, 1, 1, 'test title', 'test body', 1, 1),
(86, 1, 1, 'test save title', 'test save body', 1, 1),
(87, 1, 1, 'test title', 'test body', 1, 1),
(88, 1, 1, 'test save title', 'test save body', 1, 1),
(89, 1, 1, 'test title', 'test body', 1, 1),
(90, 1, 1, 'test save title', 'test save body', 1, 1),
(91, 1, 1, 'test title', 'test body', 1, 1),
(92, 1, 1, 'test save title', 'test save body', 1, 1),
(93, 1, 1, 'test title', 'test body', 1, 1),
(94, 1, 1, 'test save title', 'test save body', 1, 1),
(95, 1, 1, 'test title', 'test body', 1, 1),
(96, 1, 1, 'test save title', 'test save body', 1, 1),
(97, 1, 1, 'test title', 'test body', 1, 1),
(98, 1, 1, 'test save title', 'test save body', 1, 1),
(99, 1, 1, 'test title', 'test body', 1, 1),
(100, 1, 1, 'test save title', 'test save body', 1, 1),
(101, 1, 1, 'test title', 'test body', 1, 1),
(102, 1, 1, 'test title', 'test body', 1, 1),
(103, 1, 1, 'test save title', 'test save body', 1, 1),
(104, 1, 1, 'test title', 'test body', 1, 1),
(105, 1, 1, 'test save title', 'test save body', 1, 1),
(106, 1, 1, 'test title', 'test body', 1, 1),
(107, 1, 1, 'test save title', 'test save body', 1, 1),
(108, 1, 1, 'test title', 'test body', 1, 1),
(109, 1, 1, 'test save title', 'test save body', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `blog_post_tags`
--

CREATE TABLE `blog_post_tags` (
  `post_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`post_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `blog_post_tags`
--

INSERT INTO `blog_post_tags` (`post_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `blog_sections`
--

CREATE TABLE `blog_sections` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `blog_sections`
--

INSERT INTO `blog_sections` (`id`, `name`) VALUES
(1, 'programming'),
(2, 'politics'),
(3, 'funny'),
(4, 'news');

-- --------------------------------------------------------

--
-- Table structure for table `blog_tags`
--

CREATE TABLE `blog_tags` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `blog_tags`
--

INSERT INTO `blog_tags` (`id`, `name`) VALUES
(1, 'awesome'),
(2, 'crazy'),
(3, 'football'),
(4, 'soccer');

-- --------------------------------------------------------

--
-- Table structure for table `blog_users`
--

CREATE TABLE `blog_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL,
  `class_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `blog_users`
--

INSERT INTO `blog_users` (`id`, `name`, `password`, `class_id`) VALUES
(1, 'User One', '25985', 1),
(2, 'User Two', 'hello', 2),
(3, 'User Three', 'test', 1),
(4, 'User Four', 'wat', 2);

-- --------------------------------------------------------

--
-- Table structure for table `blog_user_classes`
--

CREATE TABLE `blog_user_classes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `blog_user_classes`
--

INSERT INTO `blog_user_classes` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'guest');
