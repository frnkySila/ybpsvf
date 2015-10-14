<?php

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'movie_tracker';

// Секретное значение гет-параметра, без которого не работают пхп-файлы
$SECRET = 'strashnee_secret';

// Ссылка ваш профайл в кинопоиске (с трейлинг слешем)
$KP_USER_URL = 'http://www.kinopoisk.ru/user/405812/';

// Фраза, которая вверху под заголовком пишется
// (по дефолту, если ни у одного сегодняшнего фильма нет слогана)
$DEFAULT_TAGLINE = ". . .";

// Ссылки на страницы кинотеатров, из которых берутся фильмы для "Сегодня в кино"
// NOTE: Чем больше кинотеатров, тем дольше оно обновляется, т.к. страницы качаются последовательно
$TARGET_CINEMAS = Array(
    "http://www.kinopoisk.ru/afisha/tc/1/cinema/280972/day_view/today/", // Каро Vegas 22 в Мякинино
    "http://www.kinopoisk.ru/afisha/tc/1/cinema/264778/day_view/today/", // Киноград в Лесном городке
    "http://www.kinopoisk.ru/afisha/tc/1/cinema/263296/day_view/today/", // Юность в Одинцово
);

?>
