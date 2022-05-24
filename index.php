<?php

/*
 * Используя код в CMS Bitrix, вам понадобятся эти строки
 * */

// require $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php';
// require_once __DIR__.'/access.php';

// $access = $userGroup['Access'] === true;

$access = true;
$title = 'Быстрое создание чека';
?>

<html lang="ru">
<head>
  <link rel="shortcut icon" type="image/svg+xml" href="/favicon.ico"/>
  <link rel="stylesheet" href="css/style.css?data=<?= date('ymdhi') ?>">
  <title><?= $title ?></title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <?php
  if ($access) { ?>
    <div class="container">
      <h1><?= $title ?></h1>
      <div class="container-example">
        <a href="ajax.php?download=example.csv" target="_blank">Посмотреть образец документа</a>
      </div>
      <div class="container-form">
        <form enctype="multipart/form-data">
          <div class="container-input">
            <div class="file">
              <label>
                <p class="name">Загрузите CSV-файл</p>
                <input type="hidden" name="MAX_FILE_SIZE" value="5000000"/>
                <input type="file" name="content" required>
              </label>
            </div>
          </div>
          <div class="container-submit">
            <button class="send" type="submit">Создать чек</button>
          </div>
        </form>
      </div>
    </div>
    <script type="text/javascript" src="js/script.js?data=<?= date('ymdhi') ?>" defer></script>
  <?php
  } else { ?>
    <div class="container">
      <div class="access">У вас нет прав на доступ к этому сервису. Попробуйте сначала
        <a href="https://example.com/login/" target="_blank">авторизоваться</a>.
      </div>
    </div>
    <?php
  } ?>
</body>
</html>
