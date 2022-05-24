<?php
require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

/* Доступ к сервису только для перечисленных групп:
 * 1 - Администраторы
 * 2 - Продавцы
 * */

global $USER;

$user_group = [
    'Access' => false,
    'allow_group_list' => [1, 2],
    'current_group' => \CUser::GetUserGroup($USER->GetID()),
];

foreach ($user_group['current_group'] as $id) {
    if (in_array((int)$id, (array)$user_group['allow_group_list'], true)) {
        $user_group['Access'] = true;
        break;
    }
}
