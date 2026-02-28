<?php

// text
$_['text_invalid_cron_pass']                = 'Ошибка: Неверный пароль для cron!';
$_['text_cron_done']                        = 'Cron выполнен успешно!';
$_['text_module_disabled']                  = 'Модуль отключен!';
$_['text_error_invalid_api_key']            = 'Ошибка: Неверный ключ API!';
$_['text_manual_send_nothing']              = 'Ошибка отправки, или корзина уже была сконвертирована!';
$_['text_manual_send_ok']                   = 'Сообщение успешно отправлено!';
$_['text_error_invalid_restore_token']      = 'Ошибка: Неверный токен восстановления!';
$_['text_success_restore_cart']             = 'Корзина успешно восстановлена!';
$_['text_error_no_cart']                    = 'Ошибка: Корзина не найдена!';
$_['text_price']                            = 'Цена';

// Email
$_['email_subject']                         = 'Сохранили для Вас товары, которыми вы интересовались';
$_['email_message']                         = '<div style="font-weight: bold; font-size: 16px; margin: 8px;">Добрый день, [customer_name]!<br>[promo_code]<br> Товары в корзине ожидают вашего решения:</div> <br>[products]<br> Вы можете быстро приобрести их, перейдя по ссылке: <br><br><a style="font-size: 20px; font-weight: bold;" href="[restore_link]">Завершить покупку</a><br> <br> <br> С уважением, [store_name]';
$_['email_promo_code']                      = 'Ваш персональный промокод на скидку:';   

// SMS
$_['default_sms_template']                  = 'Добрый день![br]Товары в корзине ожидают вашего решения: [restore_link][br]С уважением, [store_name]';
$_['default_sms_template_vs_promocode']     = 'Добрый день![br]Ваш промокод: [promo_code][br]Товары в корзине ожидают вашего решения: [restore_link][br]С уважением, [store_name]';