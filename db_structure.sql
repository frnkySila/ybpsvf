SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";

CREATE TABLE `chart_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chart_id` tinytext NOT NULL,
  `json_data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `chart_data` (`id`, `chart_id`, `json_data`) VALUES
(1, 'pie_chart_of_movie_scores', ''),
(2, 'line_chart_daily_avg', ''),
(3, 'bar_chart_views_dayofweek', ''),
(4, 'largenumber_wasted_academic_hours', '');

CREATE TABLE `movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kinopoisk_url` varchar(32) NOT NULL,
  `name` text NOT NULL,
  `runtime` smallint(6) NOT NULL,
  `tagline` text NOT NULL,
  `img_remote` varchar(256) NOT NULL,
  `img_local` varchar(256) NOT NULL,
  `score` tinyint(4) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_checked` datetime NOT NULL,
  `date_rated` datetime NOT NULL,
  `is_on` tinyint(1) NOT NULL,
  `kinopoisk_score` decimal(5,3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kinopoisk_url` (`kinopoisk_url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
