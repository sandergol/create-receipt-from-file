<?php

ini_set('memory_limit', '32M');
ini_set('upload_tmp_dir', __DIR__.'files/temp');
ini_set('upload_max_filesize', 5000000);
ini_set('max_file_uploads', 1);
// ini_set('display_errors', 1);

if (!file_exists(__DIR__.'/methods/Send.php')) {
    throw(new \Exception('Not file "Send.php"'));
} else {
    require_once(__DIR__.'/methods/Send.php');
}

use Send\Send;

// Вернем образец таблицы, если это GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && str_contains($_GET['download'], 'example.csv')) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="example.csv"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-cache');
    header('Pragma: no-cache');

    readfile(__DIR__.'/files/example.csv');

    exit();
}

// Серия проверок файла
switch ($_FILES['content']['error']) {
    case 1:
    case 2:
        exit(response([
            'status' => 'error',
            'message' => 'Превышен максимальный размер файла в 5 мегабайт.',
        ]));
    case 3:
        exit(response([
            'status' => 'error',
            'message' => 'Файл не был загружен. Проверьте качество соединения и повторите попытку.',
        ]));
    case 4:
        exit(response([
            'status' => 'error',
            'message' => 'Файл не был загружен.',
        ]));
    case 6:
        exit(response([
            'status' => 'error',
            'message' => 'Отсутствует временная папка.',
        ]));
    case 7:
        exit(response([
            'status' => 'error',
            'message' => 'Не удалось записать файл на диск.',
        ]));
    case 8:
        exit(response([
            'status' => 'error',
            'message' => 'Внутренняя ошибка сервера. Файл не удалось загрузить.',
        ]));
}

$allow_file_props = [
    'size' => 5000000,
    'type' => ['text/csv', 'text/plain'],
    'format' => ['csv'],
];

if ($_FILES['content']['size'] > $allow_file_props['size']) {
    exit(response([
        'status' => 'error',
        'message' => 'Превышен максимальный размер файла в 5 мегабайт.',
    ]));
}

$file_exp = explode('.', $_FILES['content']['name']);
if (!in_array(end($file_exp), $allow_file_props['format'])) {
    exit(response([
        'status' => 'error',
        'message' => 'Необходимо передать csv-файл',
    ]));
}

// Сохранение файла и работа с ним
$save_path = __DIR__.'/files/temp';
$file_path = getFilePath($save_path, 'content', 'csv');

if (move_uploaded_file($_FILES['content']['tmp_name'], $file_path)) {
    if (!in_array(mime_content_type($file_path), $allow_file_props['type'], true)) {
        if (strpos($file_path, '/files/temp') > 0) unlink($file_path);

        exit(response([
            'status' => 'error',
            'message' => 'Ваш файл не соответствует требуемому формату.',
        ]));
    } else {
        print_r(handlerContent($file_path));

        // Автоматическое удаление старых файлов
        if (strpos($file_path, '/files/temp') > 0) unlink($file_path);

        exit();
    }
}

function getFilePath(string $path, string $name, string $format): string {
    $count = 1;
    $file_path = $path.'/'.$name.'.'.$format;

    while (file_exists($file_path)) {
        $file_path = $path.'/'.$name.'_'.$count.'.'.$format;
        $count++;
    }

    return $file_path;
}

function handlerContent(string $file_path): array {
    try {
        $service_data = [
            'inputs' => [
                'Description' => [
                    'require' => true,
                    'value' => 0,
                ],
                'Email' => [
                    'require' => true,
                    'value' => 0,
                ],
                'Phone' => [
                    'require' => false,
                    'value' => 0,
                ],
                'Article' => [
                    'require' => false,
                    'value' => 0,
                ],
                'Name' => [
                    'require' => true,
                    'value' => 0,
                ],
                'Count' => [
                    'require' => true,
                    'value' => 0,
                ],
                'Final price' => [
                    'require' => true,
                    'value' => 0,
                ],
            ],
        ];

        $request_data = [
            "Type" => 'Income',        // Признак расчета
            "InvoiceId" => '',         // Номер заказа в вашей системе
            "AccountId" => '',         // Идентификатор пользователя в вашей системе
            "Inn" => '1234567890',     // ИНН вашей организации или ИП, на который зарегистрирована касса
            "CustomerReceipt" => [     // Состав чека
                "Items" => [],         // Содержимое чека
                "taxationSystem" => 2, // Система налогообложения (2 = Упрощенная система налогообложения (Доход минус Расход))
                "email" => '',
                "phone" => '',
                "Amounts" => [
                    "Electronic" => 0, // Сумма оплаты электронными деньгами
                ],
                "CalculationPlace" => 'https://example.com',
            ],
        ];

        foreach (parse_csv($file_path) as $key => $line) {
            // Валидация структуры таблицы
            if ($key === 0) {
                $list_allow_name = '';
                foreach (array_keys($service_data['inputs']) as $index => $text) {
                    $text = $service_data['inputs'][$text]['require'] ? $text.' *' : $text;

                    if ($index < (count($service_data['inputs']) - 1)) {
                        $list_allow_name .= '"'.$text.'"'.', ';
                    } else {
                        $list_allow_name .= '"'.$text.'".';
                    }
                }

                if (count($line) > 7) {
                    return [
                        'status' => 'error',
                        'message' => 'Файл должен содержать только перечисленные столбцы: '.$list_allow_name,
                    ];
                }

                // Проверяем, что названия столбцов правильные и если это так, то присваиваем индекс в $service_data['inputs']
                $undefined_name = '';
                foreach ($line as $index => $item) {
                    $itemSearch = preg_replace('/ ?\*/', '', $item);

                    if (!array_key_exists($itemSearch, $service_data['inputs'])) {
                        if ($index < (count($line) - 1)) {
                            $undefined_name .= '"'.$item.'", ';
                        } else {
                            $undefined_name .= '"'.$item.'"';
                        }
                    }
                    // Записываем позицию найденной колонки
                    else {
                        $service_data['inputs'][$itemSearch]['value'] = $index;
                    }
                }

                if (!empty($undefined_name)) {
                    return [
                        'status' => 'error',
                        'message' => 'В таблице найдены неизвестные названия столбцы: '.$undefined_name.".\n"
                            .'Ожидаемые названия колонок: '.$list_allow_name,
                    ];
                }
            }

            // Пропускаем описание колонок
            if ($key == 0) continue;

            // Заполняем $request_data
            $item_index = $key - 1;
            foreach ($line as $index => $value) {
                // Проверяем, что все обязательные поля заполнены
                foreach ($service_data['inputs'] as $name => $input) {
                    if ($input['require'] === true && $input['value'] == $index && empty($value)) {
                        // У этих колонок заполняется только одна ячейка
                        if (($name == 'Description' || $name == 'Email') && $key > 1) {
                            continue;
                        }

                        $list_require_inputs = '';
                        foreach ($service_data['inputs'] as $position => $item) {
                            if ($item['require'] === true) {
                                if ($position < (count($service_data['inputs']) - 1)) {
                                    $list_require_inputs .= '"'.$position.' *", ';
                                } else {
                                    $list_require_inputs .= '"'.$position.' *"';
                                }
                            }
                        }

                        return [
                            'status' => 'error',
                            'message' => 'Проверьте таблицу!!! Как минимум одна обязательная к заполнению ячейка не заполнена.'."\n"
                                .'В перечисленных колонках все ячейки должны быть заполнены: '.$list_require_inputs,
                        ];
                    }
                }

                if (empty($request_data['InvoiceId']) && $index == $service_data['inputs']['Description']['value']) {
                    $request_data['InvoiceId'] = $value;
                } else if (empty($request_data['AccountId']) && $index == $service_data['inputs']['Email']['value']) {
                    $request_data['AccountId'] = $value;
                    $request_data['CustomerReceipt']['email'] = $value;
                } else if (empty($request_data['CustomerReceipt']['phone']) && $index == $service_data['inputs']['Phone']['value']) {
                    $request_data['CustomerReceipt']['phone'] = $value ?: null;
                }

                // Собираем "Items"
                if ($index == $service_data['inputs']['Article']['value']) {
                    $request_data['CustomerReceipt']['Items'][$item_index]['EAN13'] = (string)$value ?: null;
                } else if ($index == $service_data['inputs']['Name']['value']) {
                    $request_data['CustomerReceipt']['Items'][$item_index]['Label'] = (string)$value;
                } else if ($index == $service_data['inputs']['Count']['value']) {
                    $product_count = (int)$value;

                    if (gettype($value) == 'integer' && $product_count > 0) {
                        return [
                            'status' => 'error',
                            'message' => 'Исправьте столбец "Count *".'."\n"
                                .'В нем должно указываться только количество по каждой позиции от 1-цы и больше.',
                        ];
                    }

                    $request_data['CustomerReceipt']['Items'][$item_index]['Quantity'] = $product_count;
                } else if ($index == $service_data['inputs']['Final price']['value']) {
                    $price = str_replace(',', '.', $value);
                    $request_data['CustomerReceipt']['Items'][$item_index]['Amount'] = round((double)$price, 2);
                }

                // Добавляем "Без НДС"
                $request_data['CustomerReceipt']['Items'][$item_index]['Vat'] = null;
            }
        }

        // Добавляем в Items цену за единицу и получаем итоговую цену
        foreach ($request_data['CustomerReceipt']['Items'] as $key => $item) {
            $request_data['CustomerReceipt']['Amounts']['Electronic'] += (double)$item['Amount'];

            $price = (double)($item['Amount'] / $item['Quantity']);
            $request_data['CustomerReceipt']['Items'][$key]['Price'] = round($price, 2);
        }

        // Округляем итоговую цену до двух знаков после запятой
        $request_data['CustomerReceipt']['Amounts']['Electronic'] = round($request_data['CustomerReceipt']['Amounts']['Electronic'], 2);

        // Создаем чек
        return (new Send('pk_key', $request_data))->cloudpayments_CreateReceipt();
    } catch (\Exception $e) {
        file_put_contents(
            '/log/create_ajax_error.log', var_export([
            'TIME' => date("d.m.Y H:i:s"),
            'STATUS' => $e->getCode(),
            'MESSAGE' => $e->getMessage(),
        ], true), FILE_APPEND);

        return [
            'status' => 'error',
            'message' => json_encode($e->getMessage()),
        ];
    }
}

function parse_csv($file_path, $file_encodings = ['Windows-1251', 'UTF-8'], $col_delimiter = '', $row_delimiter = ""): array|bool {
    if (!file_exists($file_path)) {
        return false;
    }

    // Конвертируем кодировку в UTF-8
    $cont = trim(file_get_contents($file_path));
    // Если будут возникать проблемы - попробовать перейти на https://www.php.net/manual/ru/function.iconv.php
    $encoded_cont = mb_convert_encoding($cont, 'UTF-8', mb_detect_encoding($cont, $file_encodings));
    // PHP может неверно определять кодировку файла. Например mb_detect_encoding выдает кодировку UTF-8 в то время, как настоящая копировка Windows-1251
    $encoded_cont = !strpos($encoded_cont, '??')
        ? $encoded_cont
        : mb_convert_encoding($cont, 'UTF-8', 'Windows-1251');

    unset($cont);

    // Определим разделитель
    if (!$row_delimiter) {
        $row_delimiter = "\r\n";

        if (strpos($encoded_cont, "\r\n") === false) {
            $row_delimiter = "\n";
        }
    }

    // Очищаем массив от пустых значений
    $lines = explode($row_delimiter, trim($encoded_cont));
    $lines = array_filter($lines);
    $lines = array_map('trim', $lines);

    // Определяем разделитель из двух возможных: ';' или ','.
    // для расчета берем не больше 100 строк
    if (!$col_delimiter) {
        $separator = array_slice($lines, 0, 100);

        // если в строке нет одного из разделителей, то значит другой точно он...
        foreach ($separator as $line) {
            if (!strpos($line, ',')) {
                $col_delimiter = ';';
            }
            if (!strpos($line, ';')) {
                $col_delimiter = ',';
            }
            if ($col_delimiter) {
                break;
            }
        }

        // если первый способ не дал результатов, то погружаемся в задачу и считаем кол разделителей в каждой строке.
        // где больше одинаковых количеств найденного разделителя, тот и разделитель...
        if (!$col_delimiter) {
            $delim_counts = [';' => [], ',' => []];
            foreach ($separator as $line) {
                $delim_counts[','][] = substr_count($line, ',');
                $delim_counts[';'][] = substr_count($line, ';');
            }

            $delim_counts = array_map('array_filter', $delim_counts); // уберем нули

            // кол-во одинаковых значений массива - это потенциальный разделитель
            $delim_counts = array_map('array_count_values', $delim_counts);
            $delim_counts = array_map('max', $delim_counts); // берем только макс. значения вхождений

            if ($delim_counts[';'] === $delim_counts[',']) {
                return ['Не удалось определить разделитель колонок.'];
            }

            $col_delimiter = array_search(max($delim_counts), $delim_counts);
        }
    }

    $data = [];
    foreach ($lines as $key => $line) {
        $data[] = str_getcsv($line, $col_delimiter);
        unset($lines[$key]);
    }

    return $data;
}

function response(array $data): bool|string {
    return json_encode($data);
}
