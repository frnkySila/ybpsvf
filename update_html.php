<?php

include_once "config.inc.php";

if(!isset($_GET['a']) || $_GET['a'] != $SECRET) {
  echo "<p>Самое начало файла:";
  echo '<p><pre>
&lt;?php

if(!isset($_GET[\'a\']) || $_GET[\'a\'] != \' ... \') {
  echo "&lt;p>Самое начало файла:"
  echo \'&lt;p>&lt;pre>
    &amp;lt;?php

    if(!isset($_GET[\\\'a\\\']) || $_GET[\\\'a\\\'] != \\\' ... \\\') {
      echo "&amp;lt;p>Самое начало файла:"
      echo \\\'&amp;lt;p>&amp;lt;pre>
        ...
      &amp;lt;/pre>\\\';
      die();
    }
  &lt;/pre>\';
  die();
}
  </pre>';
  die();
}

require 'vendor/autoload.php';

$client = new GuzzleHttp\Client(['defaults' => ['headers' => ['Accept-Encoding' => 'gzip, deflate, compress', 'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.71 (KHTML, like Gecko) Version/6.1 Safari/537.71']] ]);

$database = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$database->set_charset("utf8");

if(!isset($update_html_output_file)) $update_html_output_file = false;

if($update_html_output_file) {
    ob_start();
}

?><!DOCTYPE html>
<html class="no-js">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Я буду просматривать сейчас все фильмы! — ybpsvf.ru</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width">

        <link rel="stylesheet" href="static/css/normalize.min.css">
        <link rel="stylesheet" href="static/css/main.css">
        <link rel="icon" type="image/png" href="/static/favicon.png" />

       <!-- Картинка, которая появляется при постинге ссылки вконтактике -->
       <link rel="image_src" href="http://st-im.kinopoisk.ru/im/kadr/2/2/4/kinopoisk.ru-Carrie-2240454.jpg" />

        <!--[if lt IE 9]>
            <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
            <script>window.html5 || document.write('<script src="js/vendor/html5shiv.js"><\/script>')</script>
        <![endif]-->

        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
          google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var ratings_data = google.visualization.arrayToDataTable([
                ['Оценка', 'Количество'],
                <?php

                $res1 = $database->query("SELECT `json_data` FROM `chart_data` WHERE `chart_id` = 'pie_chart_of_movie_scores'");

                if($res1->num_rows) {
                    $chart1_data = json_decode($res1->fetch_row()[0]);

                    foreach($chart1_data as $a => $b) {
                        echo "['". $a ."', ". $b ."],\n";
                    }
                }
                else {
                    $chart1_data = Array();
                }

                ?>
            ]);

            (new google.visualization.PieChart(document.getElementById('ratings_chart'))).draw(ratings_data, {reverseCategories: false, is3D: true, legend: {position: 'none'}, chartArea:{top: 0, left: 30, width: "100%",height: "100%"}, colors:['rgb(32, 64, 128)', 'rgb(34, 68, 136)', 'rgb(37, 74, 147)', 'rgb(40, 80, 161)', 'rgb(44, 88, 176)', 'rgb(48, 96, 193)', 'rgb(52, 104, 208)', 'rgb(55, 112, 223)', 'rgb(59, 118, 236)', 'rgb(62, 123, 246)'], });

            var average_data = google.visualization.arrayToDataTable([
            ['День', 'Средняя оценка',],
                <?php

                $res1 = $database->query("SELECT `json_data` FROM `chart_data` WHERE `chart_id` = 'line_chart_daily_avg'");

                if($res1->num_rows) {
                    $chart2_data = json_decode($res1->fetch_row()[0]);

                    $data_points = Array();

                    foreach($chart2_data as $a => $b) {
                        $a = date_create_from_format("Y-m-d", $a);

                        array_push($data_points, Array($a, $b));
                    }

                    $new_data_points = Array();

                    $date = $data_points[0][0];
                    $aday = DateInterval::createFromDateString("1 day");

                    $i = 0;
                    while($date <= date_create_from_format("Y-m-d", date("Y-m-d"))) {
                        if($i < count($data_points) - 1 && $date == $data_points[$i+1][0]) {
                            $i++;
                        }

                        array_push($new_data_points, Array(clone $date, $data_points[$i][1]));

                        $date->add($aday);
                    }

                    foreach ($new_data_points as $v) {
                        $nice_date = $v[0]->format("d M");

                        $nice_date = str_replace("Jan", "янв", $nice_date);
                        $nice_date = str_replace("Feb", "фев", $nice_date);
                        $nice_date = str_replace("Mar", "мар", $nice_date);
                        $nice_date = str_replace("Apr", "апр", $nice_date);
                        $nice_date = str_replace("May", "мая", $nice_date);
                        $nice_date = str_replace("Jun", "июн", $nice_date);
                        $nice_date = str_replace("Jul", "июл", $nice_date);
                        $nice_date = str_replace("Aug", "авг", $nice_date);
                        $nice_date = str_replace("Sep", "сен", $nice_date);
                        $nice_date = str_replace("Oct", "окт", $nice_date);
                        $nice_date = str_replace("Nov", "ноя", $nice_date);
                        $nice_date = str_replace("Dec", "дек", $nice_date);

                        echo "['". $nice_date ."', ". $v[1] ."],\n";
                    }
                    
                }
                else {
                    $chart1_data = Array();
                }

                ?>
            ]);

            new google.visualization.LineChart(document.getElementById('average_chart')).draw(average_data, {hAxis: {textPosition: 'none'}, vAxis: {ticks: [1,2,3,4,5,6,7,8,9,10]}, legend: {position: 'none'}, chartArea:{top: 50, left: 70, width: "80%",height: "75%"}, });

            var dayofweek_data = google.visualization.arrayToDataTable([
            ['День недели', 'Просмотры',],
                <?php

                $res1 = $database->query("SELECT `json_data` FROM `chart_data` WHERE `chart_id` = 'bar_chart_views_dayofweek'");

                if($res1->num_rows) {
                    $chart3_data = json_decode($res1->fetch_row()[0]);

                    $chart3_data_assoc = Array();

                     $chart3_data_assoc["Пн"] = $chart3_data[1];
                     $chart3_data_assoc["Вт"] = $chart3_data[2];
                     $chart3_data_assoc["Ср"] = $chart3_data[3];
                     $chart3_data_assoc["Чт"] = $chart3_data[4];
                     $chart3_data_assoc["Пт"] = $chart3_data[5];
                     $chart3_data_assoc["Сб"] = $chart3_data[6];
                     $chart3_data_assoc["Вс"] = $chart3_data[0];

                    foreach($chart3_data_assoc as $a => $b) {
                        echo "['". $a ."', ". $b ."],\n";
                    }
                }
                else {
                    $chart1_data = Array();
                }

                ?>
            ]);

            new google.visualization.ColumnChart(document.getElementById('dayofweek_chart')).draw(dayofweek_data, {legend: {position: 'none'}, chartArea:{top: 50, left: 70, width: "80%",height: "75%"}, });

            var correlation_data = google.visualization.arrayToDataTable([
              [' ', ' '],
                  <?php

                  $res1 = $database->query("SELECT `kinopoisk_score`, `score` FROM `movies` WHERE `score` >= 1 AND `kinopoisk_score` != -1");

                  if($res1->num_rows) {
                    while($next_row = $res1->fetch_row()) {
                        echo "[". $next_row[0] .", ". $next_row[1] ."],\n";
                    }
                  }

                  ?>
            ]);

            new google.visualization.ScatterChart(document.getElementById('correlation_chart')).draw(correlation_data, {legend: {position: 'none'}, hAxis: {title: 'Кинопоиск', minValue: 1, maxValue: 10}, vAxis: {title: 'Я', minValue: 1, maxValue: 10}, chartArea:{top: 50, left: 70, width: "80%",height: "75%"}, trendlines: { 0: { type: 'polynomial', color: 'rgb(44, 88, 176)', lineWidth: 5, opacity: 0.5, showR2: false } }, });

          }
        </script>
        <script type="text/javascript" src="//yastatic.net/share/share.js" charset="utf-8"></script>
    </head>
    <body>

        <div class="wrapper">

            <div class="header">
                <h1>Я буду просматривать сейчас все фильмы!</h1>
                <div class="header-info">
                    <p><?php
                        $res1 = $database->query("SELECT `tagline` FROM `movies` WHERE `is_on` = 1");

                        $taglines = Array();
                        while($next_row = $res1->fetch_row()) {
                            array_push($taglines, $next_row[0]);
                        }

                        // Раньше был цикл с array_rand, но он мог закциклиться, если в массиве не было ни одного нормального теглайна
                        shuffle($taglines);

                        $selected_tagline = $DEFAULT_TAGLINE;

                        for($i = 0; $i < count($taglines); $i++) {
                            if($taglines[$i] != "") {
                                $selected_tagline = $taglines[$i];
                                break;
                            }
                        }

                        echo $selected_tagline;
                    ?>
                    <p>
                        <!-- Тут я че-то наговнякал со стилями элементов - это потому что они в хроме криво стояли -->
                        <div class="yashare-auto-init" data-yashareL10n="ru" data-yashareType="small" data-yashareQuickServices="vkontakte,facebook,twitter,odnoklassniki,gplus" data-yashareTheme="counter" data-yashareImage="http://st-im.kinopoisk.ru/im/kadr/2/2/4/kinopoisk.ru-Carrie-2240454.jpg" style="display: inline-block; vertical-align: top;"></div>
                        <a class="github-button" href="https://github.com/frnkySila/ybpsvf/fork" data-icon="octicon-repo-forked" data-count-href="/frnkySila/ybpsvf/network" data-count-api="/repos/frnkySila/ybpsvf#forks_count" data-count-aria-label="# forks on GitHub" aria-label="Fork frnkySila/ybpsvf on GitHub" style="display: inline-block;">Fork</a>
                </div>
            </div>

            <?php

            // Сегодня в кино

            $res1 = $database->query("SELECT `kinopoisk_url`, `img_local`, `name`, `score` FROM `movies` WHERE `is_on` = 1 ORDER BY RAND()");

            if($res1->num_rows) {
                echo "<div class=\"block\"><h2>Сегодня в кино</h2><div class=\"block-posters\">";

                while($next_row = $res1->fetch_row()) {
                    if($next_row[3] == -1) {
                        $name_with_score = $next_row[2] . " — без оценки";
                    }
                    else if($next_row[3] == 0) {
                        $name_with_score = $next_row[2];
                    }
                    else {
                        $name_with_score = $next_row[2] . " — " . $next_row[3] . "/10";
                    }
                    

                    echo "<div><a target=\"_blank\" href=\"http://www.kinopoisk.ru". $next_row[0] ."\"><img src=\"". $next_row[1] ."\" title=\"". $name_with_score ."\" /></a></div>";
                }

                echo "</div></div>";

            }
                    
            ?>

            <?php

            // Просмотрено

            $res1 = $database->query("SELECT `kinopoisk_url`, `img_local`, `name`, `score` FROM `movies` WHERE `score` != 0 ORDER BY `date_checked` DESC");
            
            if($res1->num_rows) {
                echo "<div class=\"block\"><h2>Просмотрено <span>". $res1->num_rows ."</span></h2><div class=\"block-posters\">";
                
                while($next_row = $res1->fetch_row()) {
                    if($next_row[3] == -1) {
                        $name_with_score = $next_row[2] . " — без оценки";
                    }
                    else {
                        $name_with_score = $next_row[2] . " — " . $next_row[3] . "/10";
                    }
                    

                    echo "<div><a target=\"_blank\" href=\"http://www.kinopoisk.ru". $next_row[0] ."\"><img src=\"". $next_row[1] ."\" title=\"". $name_with_score ."\" /></a></div>";
                }
                
                echo "</div></div>";
            }

            ?>

            <?php

            // Осталось посмотреть

            $res1 = $database->query("SELECT `kinopoisk_url`, `img_local`, `name` FROM `movies` WHERE `score` = 0 ORDER BY `date_added` DESC");

            if($res1->num_rows) {
                echo "<div class=\"block\"><h2>Осталось посмотреть <span>". $res1->num_rows ."</span></h2><div class=\"block-posters\">";

                while($next_row = $res1->fetch_row()) {
                    echo "<div><a target=\"_blank\" href=\"http://www.kinopoisk.ru". $next_row[0] ."\"><img src=\"". $next_row[1] ."\" title=\"". $next_row[2] ."\" /></a></div>";
                }

                echo "</div></div>";
            }

            ?>

            <?php

            // Заебись, чётко!

            $res1 = $database->query("SELECT `kinopoisk_url`, `img_local`, `name`, `score` FROM `movies` WHERE `score` = 10 ORDER BY `date_checked` DESC");

            if($res1->num_rows) {
                echo "<div class=\"block\"><h2>Заебись, чётко! <span>только десятки</span></h2><div class=\"block-posters\">";

                while($next_row = $res1->fetch_row()) {
                    if($next_row[3] == -1) {
                        $name_with_score = $next_row[2] . " — без оценки";
                    }
                    else {
                        $name_with_score = $next_row[2] . " — " . $next_row[3] . "/10";
                    }
                    
                    echo "<div><a target=\"_blank\" href=\"http://www.kinopoisk.ru". $next_row[0] ."\"><img src=\"". $next_row[1] ."\" title=\"". $name_with_score ."\" /></a></div>";
                }

                echo "</div></div>";
            }

            ?>

            <?php

            // Парашный угол

            $res1 = $database->query("SELECT `kinopoisk_url`, `img_local`, `name`, `score` FROM `movies` WHERE `score` = 1 ORDER BY `date_checked` DESC");

            if($res1->num_rows) {
                echo "<div class=\"block\"><h2>Парашный угол <span>только единицы</span></h2><div class=\"block-posters\">";

                while($next_row = $res1->fetch_row()) {
                    if($next_row[3] == -1) {
                        $name_with_score = $next_row[2] . " — без оценки";
                    }
                    else {
                        $name_with_score = $next_row[2] . " — " . $next_row[3] . "/10";
                    }
                    
                    echo "<div><a target=\"_blank\" href=\"http://www.kinopoisk.ru". $next_row[0] ."\"><img src=\"". $next_row[1] ."\" title=\"". $name_with_score ."\" /></a></div>";
                }

                echo "</div></div>";
            }

            ?>

            <?php

            $res1 = $database->query("SELECT `json_data` FROM `chart_data` WHERE `chart_id` = 'largenumber_wasted_academic_hours'");

            if($res1->num_rows) {
                $number = json_decode($res1->fetch_row()[0], true)["number"];

                echo "<div class=\"block\"><h2>Проебанные академические часы</h2><div class=\"block-info\">";

                echo "<span style=\"font: 100px Georgia, serif\">". $number ."</span>";

                echo "</div></div>";

            }

            ?>

            <?php

            $res1 = $database->query("SELECT `name`, `score`, `kinopoisk_score` FROM `movies` WHERE `kinopoisk_score` != -1 AND `score` >= 1 ORDER BY ABS(`score` - `kinopoisk_score`) ASC LIMIT 5");

            $res2 = $database->query("SELECT `name`, `score`, `kinopoisk_score` FROM `movies` WHERE `kinopoisk_score` != -1 AND `score` >= 1 ORDER BY ABS(`score` - `kinopoisk_score`) DESC LIMIT 5");

            if($res1->num_rows >= 5 && $res2->num_rows >= 5) {
                echo "<div class=\"block\"><div class=\"block-info\"><table><tr>";

                echo "<td><h2>Фу, мейнстрим!</h2><ul>";

                while($next_row = $res1->fetch_row()) {
                    echo "<li>«". $next_row[0] ."» — ". round(abs($next_row[1] - $next_row[2]), 3) ."";
                }

                echo "</ul></td><td><h2>Хипстер ебаный!</h2><ul>";

                while($next_row = $res2->fetch_row()) {
                    echo "<li>«". $next_row[0] ."» — ". round(abs($next_row[1] - $next_row[2]), 3) ."";
                }

                echo "</ul></td><td><p><em>Фильмы отсортированы по разнице между моей оценкой и оценкой <strike>быд</strike>Кинопоиска.</em></td>";

                echo "</tr></table></div></div>";
            }

            ?>

            <div class="block">
                <h2>Диаграмма оценок</h2>
                <div class="block-info">
                    <div id="ratings_chart" style="max-width: 600px; height: 400px;"></div>
                </div>
            </div>

            <div class="block">
                <h2>Средняя оценка</h2>
                <div class="block-info">
                    <div id="average_chart" style="max-width: 600px; height: 400px;"></div>
                </div>
            </div>

            <div class="block">
                <h2>Просмотры по дням недели</h2>
                <div class="block-info">
                    <div id="dayofweek_chart" style="max-width: 600px; height: 400px;"></div>
                </div>
            </div>

            <div class="block">
                <h2>Показываем корреляцию между мной и Кинопоиском <span>или ее отсутствие</span></h2>
                <div class="block-info">
                    <div id="correlation_chart" style="max-width: 600px; height: 400px;"></div>
                </div>
            </div>

            <div class="block">
                <h2>Среднестатистический постер</h2>
                <div class="block-info">
                    <img src="poster_collages/avgPoster.png" style="margin-left: 30px;" width="270" height="400" />
                </div>
            </div>

            <div class="block">
                <h2>USB-шредер</h2>
                <div class="block-bigposters">
                    <img src="poster_collages/vstripePoster.png" style="margin-left: 30px;" width="270" height="400" />
                    <img src="poster_collages/hstripePoster.png" style="margin-left: 30px;" width="270" height="400" />
                    <img src="poster_collages/dstripePoster.png" style="margin-left: 30px;" width="270" height="400" />
                </div>
            </div>

            <div class="block">
                <h2>Рубрика «Понеслася!»</h2>
                <div class="block-bigposters">
                    <img src="poster_collages/cellPoster.png" style="margin-left: 30px;" width="270" height="400" />
                    <img src="poster_collages/cstripePoster.png" style="margin-left: 30px;" width="270" height="400" />
                    <img src="poster_collages/rstripePoster.png" style="margin-left: 30px;" width="270" height="400" />
                    <img src="poster_collages/sinstripePoster.png" style="margin-left: 30px;" width="270" height="400" />
                    <img src="poster_collages/cosstripePoster.png" style="margin-left: 30px;" width="270" height="400" />
                </div>
            </div>

            <div class="footer">
                <!-- <div class="footer-info">
                    <p>Все данные спизжены неправомерно
                </div> -->
            </div>

        </div>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.1.min.js"><\/script>')</script>

        <!-- <script src="static/js/plugins.js"></script> -->
        <script src="static/js/main.js"></script>
        <!-- От кнопки гитхабовской -->
        <script async defer id="github-bjs" src="https://buttons.github.io/buttons.js"></script>
    </body>
</html>
<?php

if($update_html_output_file) {
    $page = ob_get_contents();
    ob_clean();
    $f = fopen($update_html_output_file, "w");
    fwrite($f, $page);
    fclose($f);
}

?>