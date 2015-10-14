Исходники сайта [ybpsvf.ru](http://ybpsvf.ru/).

Написано на Apache/PHP/MySQL потому что только на них есть хостинги за 10 рублей в месяц.

### Установка

Короче, депенденси (которая щас одна) качаются с помощью скрипта composer. Вводите в папке с этой хуйнёй `php composer.phar install` и вперед.

А еще надо модуль ImageMagick. На всех говнохостингах и в сборках LAMP под винду он есть, а у себя на маке я полдня кочеврыжился.

Еще надо переименовать `config.inc.example.php` в `config.inc.php` и вписать туда свои настройки.

### Использование

Для обновления сайта нужно регулярно запрашивать `/update_data.php?a=<бэ>&mode=<мэ>`, где `<бэ>` — значение переменной `$SECRET` из файла `config.inc.php`, `<мэ>` — одно из следующих значений:

`reg`: скрипт загружает сегодняшний список фильмов, список последних оценок, качает постеры, обновляет диаграммы и генерирует новый `index.html`.

`posters`: генерируются коллажи из постеров сегодняшних фильмов. Дополнительный GET-параметр `which_posters`, которому присваивается строка из девяти нулей и единиц, определяет, какие именно коллажи делать. Если параметр не задан, делаются все (на что на вашем хостинге может не хватить времени, см. ниже).

`kp_ratings`: для фильмов обновляются рейтинги Кинопоиска. Назначение этого функционала состоит в том, что часто при выходе фильма его рейтинг не отображается, и его нужно потом подтянуть. Если задан GET-параметр `portion`, то они загружаются по 10 штук, с `10 * portion` по `10 * portion + 9` включительно, в по возрастанию поля `id` в базе данных.

`update_a_movie_poster`: обновляет jpg-файл с постером для определенного фильма, заданного GET-параметром `which_movie`, который идентифицирует фильм по значению поля `id` в базе данных. Иногда при выходе фильма у него постер нерусский или вообще хуй знает какой, в таком случае его можно обновить.

### По поводу времени выполнения

Обратите внимание, что в дни, когда вышло много новых фильмов, на обновление информации может потребоваться примерно $Dohooya$ секунд. Если на вашем хостинге max_execution_time стоит 30 секунд (а на хостингах за 10 рублей в месяц он такой и стоит), скрипт может не успеть (и не успеет!) выполниться.

В таких случаях его можно запускать несколько раз - ничего страшного не произойдет.

