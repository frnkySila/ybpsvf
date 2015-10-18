<?php

include_once "config.inc.php";

if(!isset($_GET['secret']) || $_GET['secret'] != $CONFIG['SECRET']) {
  echo "<p>Неправильный секрет";
  die();
}

require 'vendor/autoload.php';

ini_set('display_errors', '1');


class Ybpsvf_update
{
  function __construct($config)
  {
    $this->client = new GuzzleHttp\Client(['defaults' => ['headers' => ['Accept-Encoding' => 'gzip, deflate, compress', 'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.71 (KHTML, like Gecko) Version/6.1 Safari/537.71']] ]);

    $this->database = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']);
    $this->database->set_charset("utf8");

    $this->config = $config;
  }

  private function get_movies_today()
  {
    $movie_urls = Array();

    foreach($this->config['TARGET_CINEMAS'] as $cinema_url) {
      $res = $this->client->get($cinema_url, [
        'cookies' =>  true
      ]);

      $dom_document = new DOMDocument();

      $html = $res->getBody();

      @$dom_document->loadHTML($html);

      $dom_xpath = new DOMXpath($dom_document);

      $elements = $dom_xpath->query("//div[contains(concat(' ',normalize-space(@class),' '),' colName ')]//div[contains(concat(' ',normalize-space(@class),' '),' name ')]//a");

      if ($elements->length == 0) break;
      else {
        foreach ($elements as $element) {
          $movie_url = $element->getAttribute('href');

          if(!in_array($movie_url, $movie_urls)) {
            array_push($movie_urls, $movie_url);
          }
        }
      }
    }

    return $movie_urls;
  }

  private function get_movie_scores()
  {
    $res = $this->client->get($this->config['KP_USER_URL'] . 'votes/list/ord/date/perpage/50/');

    $dom_document = new DOMDocument();

    $html = $res->getBody();

    @$dom_document->loadHTML($html);

    $dom_xpath = new DOMXpath($dom_document);

    $elements = $dom_xpath->query("descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' profileFilmsList ')]/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' item ')]");
    /* div.profileFilmsList div.item */

    $movie_scores = Array();

    foreach ($elements as $element) {

      $name_elem = $dom_xpath->query("descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' nameRus ')]/descendant::a", $element)->item(0);
      /* div.nameRus a */
      $url = $name_elem->getAttribute('href');

      $score_elem = $dom_xpath->query("descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' vote ')]", $element)->item(0);
      /* div.vote */
      $score = is_numeric($score_elem->nodeValue) ? (int)$score_elem->nodeValue : -1; // -1 для просмотренных без оценки

      $date_elem = $dom_xpath->query("descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' date ')]", $element)->item(0);
      /* div.date */
      $date = DateTime::createFromFormat("d.m.Y, H:i", $date_elem->nodeValue)->format("Y-m-d H:i:00");

      $new_item = Array($url, $score, $date);

      array_push($movie_scores, $new_item);
    }

    return $movie_scores;
  }

  private function get_movie_info($movie_url)
  {
    $res = $this->client->get("http://www.kinopoisk.ru" . $movie_url);

    $dom_document = new DOMDocument();

    $html = $res->getBody();

    @$dom_document->loadHTML($html);

    $dom_xpath = new DOMXpath($dom_document);

    $movie_name = $dom_xpath->query("descendant-or-self::h1[contains(concat(' ', normalize-space(@class), ' '), ' moviename-big ')]")->item(0)->nodeValue;
    /* h1.moviename-big */

    $img_elem = $dom_xpath->query("descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' film-img-box ')]/descendant::a[contains(concat(' ', normalize-space(@class), ' '), ' popupBigImage ')]");
    /* div.film-img-box a.popupBigImage */

    $kinopoisk_score_elem = $dom_xpath->query("descendant-or-self::span[contains(concat(' ', normalize-space(@class), ' '), ' rating_ball ')]");
    /* span.rating_ball */

    if($kinopoisk_score_elem->length > 0) {
      $kinopoisk_score = (float)$kinopoisk_score_elem->item(0)->nodeValue;
    }
    else {
      $kinopoisk_score = (float)(-1);
    }

    $runtime_elem = $dom_xpath->query("descendant-or-self::td[@id = 'runtime']");
    /* td#runtime */

    $runtime = $runtime_elem->item(0)->nodeValue;

    $runtime = (int)substr($runtime, 0, 3);

    $kinopoisk_tagline_elem = $dom_xpath->query("//td[text() = \"слоган\"]");
    /* тут у меня потерялся "css to xpath converter", и пришлось сразу в xpath начать хуярить */

    $tagline = $kinopoisk_tagline_elem->item(0)->nextSibling->nodeValue;
      
    $tagline = mb_substr($tagline, 1, -1, "utf-8");

    // /film/%s -> http://st.kinopoisk.ru/images/film_big/%s.jpg  
    if($img_elem->length > 0) {
      $img_url = $img_elem->item(0)->getAttribute('onclick');
      if($img_url) {
        $img_url = str_replace("openImgPopup('", "", $img_url);
        $img_url = str_replace("'); return false", "", $img_url);
        $img_url = 'http://kinopoisk.ru' . $img_url;
      }
      else {
        $img_url = "http://placehold.it/472x700.jpg&text=No+poster"; # К сожалению, постер отсутствует
      }
    }

    $img_thumbnail = $this->save_img_thumbnail($img_url);

    return Array($movie_name, $img_url, $img_thumbnail, $kinopoisk_score, $runtime, $tagline);
  }

  private function save_img_thumbnail($img_url, $dimensions = 400)
  {
    $path_image = "./temp_" . basename($img_url);
    $path_thumb = "./poster_thumbs/thumb" . $dimensions . "_" . basename($img_url);

    copy($img_url, $path_image);

    $image = new Imagick($path_image);

    $image->resizeImage($dimensions, $dimensions, imagick::FILTER_LANCZOS, 1, true);

    $image->writeImage($path_thumb);

    unlink($path_image);

    return $path_thumb;
  }

  private function add_new_movies_to_database()
  {
    $movies_today = $this->get_movies_today();

    echo "<p>Фильмов сегодня идет " . count($movies_today) . ".";
    flush();

    $comma_separated_movie_url_string = "'" . implode("', '", array_map(Array($this->database, 'real_escape_string'), $movies_today)) . "'"; // 'a', 'b', 'c'

    $res = $this->database->query("SELECT `kinopoisk_url` FROM `movies` WHERE `kinopoisk_url` IN(" . $comma_separated_movie_url_string . ")");

    $movies_already_in_db = Array();

    while($next_item = $res->fetch_row()) {
      $next_item = $next_item[0];

      array_push($movies_already_in_db, $next_item);
    }

    $stmt = $this->database->prepare("INSERT IGNORE INTO `movies` (`kinopoisk_url`, `name`, `runtime`, `tagline`, `img_remote`, `img_local`, `score`, `date_added`, `date_checked`, `date_rated`, `kinopoisk_score`) VALUES (?, ?, ?, ?, ?, ?, '0', NOW(), '1970-01-01 00:00:00', '1970-01-01 00:00:00', ?)");

    $stmt->bind_param("ssisssd", $movie_url, $movie_name, $movie_runtime, $movie_tagline, $movie_img_remote, $movie_img_local, $movie_kinopoisk_score);

    $number_added_movies = 0;

    echo "<p><ul>";

    foreach ($movies_today as $movie_url) {
      if(in_array($movie_url, $movies_already_in_db)) {
        continue;
      }

      $movie_info = get_movie_info($movie_url);

      $movie_name = $movie_info[0];
      $movie_img_remote = $movie_info[1];
      $movie_img_local = $movie_info[2];
      $movie_kinopoisk_score = $movie_info[3];
      $movie_runtime = $movie_info[4];
      $movie_tagline = $movie_info[5];

      $stmt->execute();

      echo "<li>Добавлен новый фильм «" . $movie_name . "»<br />\n";

      flush();

      $number_added_movies++;
    }

    echo "</ul>";

    $res1 = $this->database->query("UPDATE `movies` SET `is_on` = 0 WHERE `is_on` = 1");

    $res2 = $this->database->query("UPDATE `movies` SET `is_on` = 1 WHERE `kinopoisk_url` IN(" . $comma_separated_movie_url_string . ")");

    return $number_added_movies;
  }

  private function update_kp_scores_for_all_movies($portion)
  {
    if($portion == -1) {
      $res = $this->database->query("SELECT `kinopoisk_url`, `kinopoisk_score` FROM `movies`");
    }
    else {
      $res = $this->database->query("SELECT `kinopoisk_url`, `kinopoisk_score` FROM `movies` LIMIT ". $portion * 5 . ", " . 5 . "");
    }

    $stmt = $this->database->prepare("UPDATE `movies` SET `kinopoisk_score` = ? WHERE `kinopoisk_url` = ?");

    $stmt->bind_param("ds", $movie_new_kinopoisk_score, $movie_url);

    echo "<p><ul>";

    $count = 0;

    while($next_row = $res->fetch_row()) {
      $movie_url = $next_row[0];
      $movie_old_kinopoisk_score = $next_row[1];

      $page = $this->client->get("http://www.kinopoisk.ru" . $movie_url);

      $dom_document = new DOMDocument();

      $html = $page->getBody();

      @$dom_document->loadHTML($html);

      $dom_xpath = new DOMXpath($dom_document);

      $kinopoisk_score_elem = $dom_xpath->query("descendant-or-self::span[contains(concat(' ', normalize-space(@class), ' '), ' rating_ball ')]");
      /* span.rating_ball */

      if($kinopoisk_score_elem->length > 0) {
        $movie_new_kinopoisk_score = (float)$kinopoisk_score_elem->item(0)->nodeValue;
      }
      else {
        $movie_new_kinopoisk_score = (float)(-1);
      }

      $stmt->execute();

      echo "<li><a href=\"http://kinopoisk.ru" . $movie_url . "\">" . $movie_url . "</a>: " . $movie_old_kinopoisk_score . " => " . $movie_new_kinopoisk_score;
      flush();

      $count++;
    }

    echo "</ul>";

    return $count;
  }

  private function update_new_movie_scores_in_the_database()
  {
    $movie_scores = $this->get_movie_scores();

    $read_stmt = $this->database->prepare("SELECT `score` FROM `movies` WHERE `kinopoisk_url` = ? AND `score` != ?");
    $read_stmt->bind_param("ss", $movie_url, $new_score);
    $read_stmt->bind_result($current_score);

    $update1_stmt = $this->database->prepare("UPDATE `movies` SET `score` = ?, `date_rated` = ? WHERE `kinopoisk_url` = ?");
    $update1_stmt->bind_param("iss", $new_score, $movie_date_rated, $movie_url);

    $update2_stmt = $this->database->prepare("UPDATE `movies` SET `date_checked` = ? WHERE `kinopoisk_url` = ?");
    $update2_stmt->bind_param("ss", $movie_date_rated, $movie_url);

    echo "<p><ul>";

    $num_movies_with_new_scores = 0;

    foreach ($movie_scores as $movie_score) {
      $movie_url = $movie_score[0];
      $new_score = $movie_score[1];
      $movie_date_rated = $movie_score[2];

      $read_stmt->execute();
      $read_stmt->store_result();

      if(!$read_stmt->num_rows) {
        continue;
      }

      $read_stmt->fetch();

      $update1_stmt->execute();

      if($current_score == 0) {
        $update2_stmt->execute();
      }

      if($current_score == 0) {
        echo "<li>«". $movie_url ."» — оценка поставлена: ". $new_score ."<br />";
      }
      else {
        echo "<li>«". $movie_url ."» — оценка обновлена: ". $current_score ." => ". $new_score ."<br />";
      }

      flush();

      $num_movies_with_new_scores++;
    }

    echo "</ul>";

    return $num_movies_with_new_scores;
  }

  private function create_chartdata_pie_scores()
  {
    $res1 = $this->database->query("SELECT `score` FROM `movies` WHERE `score` >= 1");

    $new_data = Array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0);

    while($next_row = $res1->fetch_row()) {
      $new_data[$next_row[0]]++;
    }

    $new_data = json_encode($new_data);

    $res2 = $this->database->query("UPDATE `chart_data` SET `json_data` = '" . $new_data . "' WHERE `chart_id` = 'pie_chart_of_movie_scores'");
  }

  private function create_chartdata_line_avgscore()
  {
    $res1 = $this->database->query("SELECT `json_data` FROM `chart_data` WHERE `chart_id` = 'line_chart_daily_avg'");

    $chart_data = json_decode($res1->fetch_row()[0], true);

    $res2 = $this->database->query("SELECT AVG(`score`) FROM `movies` WHERE `score` >= 1");

    $new_avg = $res2->fetch_row()[0];

    $new_date = date("Y-m-d");

    $chart_data[$new_date] = $new_avg;

    $chart_data = json_encode($chart_data);

    $res3 = $this->database->query("UPDATE `chart_data` SET `json_data` = '" . $chart_data . "' WHERE `chart_id` = 'line_chart_daily_avg'");
  }

  private function create_chartdata_bar_viewdays()
  {
    $new_data = Array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0);

    $res1 = $this->database->query("SELECT `date_checked` FROM `movies` WHERE `score` != 0");

    while($next_row = $res1->fetch_row()) {
      $next_dayofweek = date("w", strtotime($next_row[0]));

      $new_data[$next_dayofweek]++;
    }

    $new_data = json_encode($new_data);

    $res2 = $this->database->query("UPDATE `chart_data` SET `json_data` = '" . $new_data . "' WHERE `chart_id` = 'bar_chart_views_dayofweek'");
  }

  private function create_chartdata_largegnumber_wastedhours()
  {
    $new_number = 0;

    $res1 = $this->database->query("SELECT `runtime` FROM `movies` WHERE `score` != 0");

    while($next_row = $res1->fetch_row()) {
      $curr_length = (int)$next_row[0];

      $new_number += (int)($curr_length / 60);

      if($curr_length % 60 <= 50) {
        $new_number += 0.5;
      }
      else {
        $new_number += 1;
      }
    }

    if($new_number % 100 >= 11 && $new_number % 100 <= 19) {
      $plural_suffix = "ов";
    }
    else if($new_number % 10 == 1 && $new_number != 1.5) { // Полтора часа
      $plural_suffix = ""; 
    }
    else if($new_number % 10 >= 1 && $new_number % 10 <= 4) {// Полтора часа
      $plural_suffix = "а";
    }
    else {
      $plural_suffix = "ов";
    }
    

    $new_data = "{\"number\":\"". str_replace(".", ",", (string)$new_number) ." час". $plural_suffix ."\"}";

    $res2 = $this->database->query("UPDATE `chart_data` SET `json_data` = '" . $new_data . "' WHERE `chart_id` = 'largenumber_wasted_academic_hours'");
  }

  private function draw_poster_avg($local_imgs, $save_path)
  {
    $target_image = new Imagick();
    $target_image->newImage(270, 400, new ImagickPixel('black'));
    $target_image->setImageFormat('png');

    $target_it = $target_image->getPixelIterator();

    $source_images = Array();

    foreach($local_imgs as $local_img) {
      $source_image = new Imagick($local_img);
      $source_image->resizeImage(270, 400, imagick::FILTER_LANCZOS, 1, true);
      $source_image->setImageFormat('png');

      array_push($source_images, $source_image);
    }

    echo "<p>Генерируется коллаж «Среднестатистический постер»: ";

    $bufferme = "<!-- Этот комментарий нужен для того, чтобы браузер корректно отображал каждый flush() -->";

    foreach ($target_it as $y => $row) {
      foreach ($row as $x => $pixel) {
        $target_color = Array(0, 0, 0);

        foreach ($source_images as $source_image) {
          $source_color = $source_image->getImagePixelColor($x, $y)->getColor();

          $target_color[0] += $source_color['r'];
          $target_color[1] += $source_color['g'];
          $target_color[2] += $source_color['b'];
        }

        $target_color[0] /= count($source_images);
        $target_color[1] /= count($source_images);
        $target_color[2] /= count($source_images);

        $target_color = "rgba(". $target_color[0] .", ". $target_color[1] .", ". $target_color[2] .", 0)";

        $pixel->setColor($target_color);
      }

      if($y % 20 == 0) {
        echo "$bufferme| ";
        flush();
      }

      $target_it->syncIterator();
    }

    $target_image->writeImage($save_path);  
  }

  private function draw_poster_collage($local_imgs, $save_path, $num_src_images, $xy_callback)
  {
    $target_image = new Imagick();
    $target_image->newImage(270, 400, new ImagickPixel('black'));
    $target_image->setImageFormat('png');

    $target_it = $target_image->getPixelIterator();

    $source_images = Array();

    for($i = 0; $i < $num_src_images; $i++) {
      $source_image = new Imagick($local_imgs[array_rand($local_imgs)]);
      $source_image->resizeImage(270, 400, imagick::FILTER_LANCZOS, 1, true);
      $source_image->setImageFormat('png');

      array_push($source_images, $source_image);
    }

    foreach ($target_it as $y => $row) {
      foreach ($row as $x => $pixel) {
        $color = $source_images[$this->$xy_callback($x, $y, $num_src_images)]->getImagePixelColor($x, $y)->getColorAsString();

        $pixel->setColor($color);
      }

      $target_it->syncIterator();
    }

    $target_image->writeImage($save_path); 
  }

  private function _get_xy_hstripe($x, $y, $num_src_images)
  {
    return $y / $num_src_images;
  }

  private function _get_xy_vstripe($x, $y, $num_src_images)
  {
    return $x / $num_src_images;
  }

  private function _get_xy_dstripe($x, $y, $num_src_images)
  {
    return ($y / $num_src_images) + ($x / $num_src_images);
  }

  private function _get_xy_cstripe($x, $y, $num_src_images)
  {
    $x = $x - 135;
    $y = $y - 200;

    return (int)hypot($y / $num_src_images, $x / $num_src_images);
  }

  private function _get_xy_rstripe($x, $y, $num_src_images)
  {
    $x = $x - 135;
    $y = $y - 200;

    if($x == 0 && $y == 0) return 0;

    return (int)(acos( ($x*135) / (hypot($x, $y) * 135) ) / 3.1416 / 2 * $num_src_images);
  }

  private function _get_xy_sinstripe($x, $y, $num_src_images)
  {
    return ($y + sin($x / 10) * 10) / $num_src_images;
  }

  private function _get_xy_cosstripe($x, $y, $num_src_images)
  {
    return ($x + cos($y / 10) * 10) / $num_src_images;
  }

  private function _get_xy_cell($x, $y, $num_src_images)
  {
    $hw = 40;

    return (($x / $hw) + (($y / $hw) % 10) * 10) % $num_src_images;
  }

  function do_update_regular()
  {
    echo "<p>Собираемся скачать список сегодняшних фильмов и добавить новые фильмы в базу.";
    flush();

    $number_added_movies = $this->add_new_movies_to_database();

    if(!$number_added_movies) {
      echo "<p>Новых фильмов сегодня не показывают.";
    }
    else {
      echo "<p>Новых фильмов показывают аж целых " . $number_added_movies . ".";
    }

    echo "<p>Собираемся скачать список последних оценок.";
    echo "<p><em>Увага! Учитывается только последние 50 оценок пользователя. Если нужно обсчитать больше оценок, придется лезть в код скрипта.</em>";
    flush();

    $number_updated_scores = $this->update_new_movie_scores_in_the_database();

    if(!$number_updated_scores) {
      echo "<p>Новых оценок не обнаружилось.";
    }
    else {
      echo "<p>Обновлены оценки для следующего количества фильмов: " . $number_updated_scores . ".";
    }

    echo "<p>Обновляются данные даграмм...";
    flush();

    $this->create_chartdata_pie_scores();

    $this->create_chartdata_line_avgscore();

    $this->create_chartdata_bar_viewdays();

    $this->create_chartdata_largegnumber_wastedhours();
  }

  function do_update_html()
  {
    echo "<p>Генерируется index.html...";

    $update_html_output_file = time() . "_index.html";

    include "update_html.php";

    copy($update_html_output_file, "index.html");

    unlink($update_html_output_file);
  }

  function do_update_collages($which_collages)
  {
    echo "<p>Щас будут генерироваться коллажи из постеров.";

    $res1 = $this->database->query("SELECT `img_local` FROM `movies` WHERE `is_on` = 1");

    echo "<p>Постеров в наличии: ". $res1->num_rows .".";

    flush();

    $local_imgs = Array();

    while($next_row = $res1->fetch_row()) {
      array_push($local_imgs, $next_row[0]);
    }
    
    if($which_collages[0] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/hstripePoster.png", 30, "_get_xy_hstripe");

      echo "<p>Сгенерирован коллаж из горизонтальных полосок.";
      flush();
    }

    if($which_collages[1] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/vstripePoster.png", 20, "_get_xy_vstripe");

      echo "<p>Сгенерирован коллаж из вертикальных полосок.";
      flush();
    }

    if($which_collages[2] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/dstripePoster.png", 30, "_get_xy_dstripe");

      echo "<p>Сгенерирован коллаж из диагональных полосок.";
      flush();
    }

    if($which_collages[3] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/cstripePoster.png", 30, "_get_xy_cstripe");

      echo "<p>Сгенерирован коллаж из круговых полосок.";
      flush();
    }

    if($which_collages[4] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/rstripePoster.png", 30, "_get_xy_rstripe");

      echo "<p>Сгенерирован коллаж из радиальных полосок.";
      flush();
    }

    if($which_collages[5] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/sinstripePoster.png", 30, "_get_xy_sinstripe");

      echo "<p>Сгенерирован коллаж из синусоидальных полосок.";
      flush();
    }

    if($which_collages[6] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/cosstripePoster.png", 20, "_get_xy_cosstripe");

      echo "<p>Сгенерирован коллаж из косинусоидальных полосок.";
      flush();
    }

    if($which_collages[7] == "1") {
      $this->draw_poster_collage($local_imgs, "poster_collages/cellPoster.png", 100, "_get_xy_cell");

      echo "<p>Сгенерирован коллаж из клеточек.";
      flush();
    }

    if($which_collages[8] == "1") {
      $this->draw_poster_avg($local_imgs, "poster_collages/avgPoster.png");
    }
  }

  function do_update_kp_ratings($portion)
  {
    echo "<p>Обновляются рейтинги Кинопоиска для всех фильмов...";
    flush();

    $number_updated_ratings = $this->update_kp_scores_for_all_movies($portion);

    echo "<p>Рейтинги обновлены для следующего количества фильмов: " . $number_updated_ratings . ".";
  }

  function do_update_a_movie_poster($kinopoisk_url)
  {
    echo "Щас скачается новый постер для фильма ". $kinopoisk_url;
    flush();

    $res1 = $this->database->query("SELECT `id` FROM `movies` WHERE `kinopoisk_url` = '" . $this->database->real_escape_string($kinopoisk_url) . "'");

    if($res1->num_rows) {
      $img_path = $this->get_movie_info($kinopoisk_url)[1];

      $thumb_path = $this->save_img_thumbnail($img_path);

      $update_stmt = $this->database->prepare("UPDATE `movies` SET `img_remote` = ?, `img_local` = ? WHERE `kinopoisk_url` = ?");

      $update_stmt->bind_param("sss", $img_path, $thumb_path, $kinopoisk_url);

      $update_stmt->execute();

      return true;
    }
    else {
      return false;
    }
  }
}

$updater = new Ybpsvf_update($CONFIG);

header("Cache-Control: no-cache, must-revalidate");

echo "<meta charset=\"utf-8\">\n<h1>".time()."</h1><br /><br /><br /><br />\n\n";
flush();

$method = isset($_GET['method']) ? $_GET['method'] : '';

switch($method) {
case 'update_regular':
  $updater->do_update_regular();
  $updater->do_update_html();
  break;
case 'update_collages':
  $which_collages = isset($_GET['which_collages']) ? $_GET['which_collages'] : '111111111';

  $updater->do_update_collages($which_collages);
  break;
case 'update_ratings':
  $portion = isset($_GET['portion']) ? $_GET['portion'] : -1;

  $updater->do_update_kp_ratings($portion);
  break;
case 'update_a_poster':
  if(!isset($_GET['kinopoisk_url'])) {
    echo "<p>Для вызова метода <em>update_a_movie_poster</em> необходим параметр <em>which_movie</em>";
  }
  else {
    if($updater->do_update_a_movie_poster($_GET['kinopoisk_url'])) {
      echo "<p>Постер успешно обновлен.";
    }
    else {
      echo "<p>Фильма с таким <em>kinopoisk_url</em> нет в базе.";
    }
  }
  break;
default:
  echo "<p>get-параметр <em>method</em> не установлен или в нем написано че-то не то";
}

echo "<p>Ну, вроде пока всё." ;

?>
