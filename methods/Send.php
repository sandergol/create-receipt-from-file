<?php

namespace Send;

class Send {
    private static array $auth_key_list = [
        'pk_key' => [
            'Login' => 'pk_key',
            'Password' => 'password',
            'Inn' => '1234567890',
            'comment' => 'CSV_receipt',
        ],
    ];
    private static string $auth_key_selected;
    private static array $request_data;

    public function __construct(string $auth_key, array $request_data) {
        self::$auth_key_selected = $auth_key;

        self::$request_data = $request_data;
    }

    public function cloudpayments_CreateReceipt(): array {
        $response = $this->cloudpayments_Request('https://api.cloudpayments.ru/kkt/receipt');

        if (json_decode($response['result'], true)['Success']) {
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'].'/log/create_receipt_ok.log', var_export([
                'TIME' => date("d.m.Y H:i:s"),
                'REQUEST' => self::$request_data,
                'RESPONSE' => $response,
                'ADDITIONALLY' => [
                    'KEY_SELECTED' => self::$auth_key_selected,
                ],
            ], true), FILE_APPEND);
        } else {
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'].'/log/create_receipt_error.log', var_export([
                'TIME' => date("d.m.Y H:i:s"),
                'REQUEST' => self::$request_data,
                'RESPONSE' => $response,
                'ADDITIONALLY' => [
                    'KEY_SELECTED' => self::$auth_key_selected,
                ],
            ], true), FILE_APPEND);
        }

        return $response;
    }

    private function cloudpayments_Request(string $url): array {
        // Удаляем лишние данные из идентификатора заказа
        self::$request_data['InvoiceId'] = str_ireplace('Заказ клиента № ', '', self::$request_data['InvoiceId']);;

        // Отправляем запрос
        $request_data = [
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(self::$request_data),
            CURLOPT_USERPWD => self::$auth_key_list[self::$auth_key_selected]['Login'].":".self::$auth_key_list[self::$auth_key_selected]['Password'],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $request_data);

        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        file_put_contents(
            $_SERVER['DOCUMENT_ROOT'].'/log/send_request.log', var_export([
            'TIME' => date("d.m.Y H:i:s"),
            'PROPERTY' => self::$request_data,
            'REQUEST' => $request_data,
            'RESPONSE' => $result,
            'STATUS' => $status,
            'ADDITIONALLY' => [
                'KEY_SELECTED' => self::$auth_key_selected,
            ],
        ], true), FILE_APPEND);

        return [
            "status" => $status,
            "result" => $result,
        ];
    }
}
