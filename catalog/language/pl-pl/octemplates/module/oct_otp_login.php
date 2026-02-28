<?php
// Heading
$_['heading_title']             = 'Autoryzacja';

// Entry
$_['entry_telephone']           = 'Numer telefonu';
$_['entry_otp']                 = 'Jednorazowe hasło (OTP)';

// Button
$_['button_send_otp']           = 'Wyślij';
$_['button_verify_otp']         = 'Zweryfikuj';
$_['button_resend_otp']         = 'Wyślij nowy kod';

// Text
$_['text_otp_sent']             = 'Kod weryfikacyjny został wysłany na Twój numer telefonu.';
$_['text_otp_resent']           = 'Nowy kod weryfikacyjny został wysłany na Twój numer telefonu.';
$_['text_success_login']        = 'Zalogowano pomyślnie, za chwilę zostaniesz przekierowany.';
$_['default_sms_template']      = 'Twój kod weryfikacyjny: [code]';
$_['text_entry_telephone']      = 'Proszę wprowadzić swój numer telefonu, aby otrzymać kod weryfikacyjny.';
$_['text_otp_tab']              = 'Przez telefon';
$_['text_email_tab']            = 'Przez E-mail';
$_['text_otp_not_sent']         = 'Jeśli nie otrzymałeś kodu w ciągu 60 sekund, wykonaj następujące czynności:';
$_['text_otp_not_sent_list']    = '<ul class="ps-3"><li>sprawdź poprawność wprowadzonego numeru telefonu;</li><li>upewnij się, że jest połączenie z Internetem;</li><li>sprawdź pamięć w telefonie, spróbuj usunąć kilka dialogów, aby upewnić się, że jest wolne miejsce;</li><li>zrestartuj telefon.</li></ul>';
$_['text_otp_not_received']     = 'Nie otrzymałeś kodu?';

// Error
$_['error_module_disabled']     = 'Moduł autoryzacji przez telefon jest wyłączony.';
$_['error_telephone']           = 'Wprowadź poprawny numer telefonu.';
$_['error_not_found']           = 'Nie znaleziono użytkownika z takim numerem telefonu.';
$_['error_otp']                 = 'Nieprawidłowy kod OTP. Proszę spróbować ponownie.';
$_['error_otp_expired']         = 'Kod OTP wygasł. Proszę poprosić o nowy.';
$_['error_otp_not_found']       = 'Nie znaleziono kodu OTP. Proszę poprosić o nowy.';
$_['error_max_attempts']        = 'Przekroczono maksymalną liczbę prób. Proszę spróbować ponownie za %s minut(y).';
$_['error_throttle']            = 'Następna próba wysłania kodu OTP możliwa za %s sekund(y).';
$_['error_session_expired']     = 'Sesja wygasła. Proszę rozpocząć proces od nowa.';
$_['error_empty_telephone']     = 'Wprowadź numer telefonu.';
$_['error_csrf']                = 'Неверный токен CSRF.';