<?php
// Heading
$_['heading_title']             = 'Octemplates - Autoryzacja przez SMS (OTP)';

// Text
$_['text_extension']            = 'Rozszerzenia';
$_['text_success']              = 'Ustawienia modułu zostały pomyślnie zmienione!';
$_['text_edit']                 = 'Edytowanie modułu Autoryzacji przez telefon';
$_['text_enabled']              = 'Włączone';
$_['text_disabled']             = 'Wyłączone';
$_['text_install_success']      = 'Moduł został pomyślnie zainstalowany!';
$_['text_uninstall_success']    = 'Moduł został pomyślnie usunięty!';
$_['text_logs']                 = 'Logi OTP';
$_['text_list']                 = 'Lista logów';
$_['text_pagination']           = 'Wyświetlono od %d do %d z %d (%d Stron)';
$_['text_success_logs_deleted'] = 'Logi zostały pomyślnie usunięte!';
$_['text_loading']              = 'Ładowanie...';
$_['text_no_results']           = 'Brak danych do wyświetlenia.';
$_['text_confirm_delete']       = 'Czy na pewno chcesz usunąć wszystkie logi?';
$_['text_success_logs_deleted'] = 'Logi zostały pomyślnie usunięte!';
$_['text_success_ip_logs_deleted'] = 'Logi IP zostały pomyślnie usunięte!';
$_['help_ip_logs']              = 'Ta lista zawiera wszystkie adresy IP, które próbowały się zalogować i wprowadziły nieprawidłowy numer telefonu lub kod OTP.';

// Entry
$_['entry_status']              = 'Status';
$_['entry_otp_length']          = 'Długość OTP';
$_['entry_otp_expiry']          = 'Czas ważności OTP (minuty)';
$_['entry_max_attempts']        = 'Maksymalna liczba prób';
$_['entry_lockout_time']        = 'Czas blokady (minuty)';
$_['entry_throttle_time']       = 'Czas między żądaniami (sekundy)';
$_['entry_phone_mask']          = 'Maska telefonu';
$_['entry_logging']             = 'Włącz logowanie';
$_['entry_customer_id']         = 'ID Klienta';
$_['entry_telephone']           = 'Telefon';

// Help
$_['help_otp_length']           = 'Liczba cyfr w kodzie OTP (od 4 do 10).';
$_['help_otp_expiry']           = 'Czas w minutach, przez który OTP jest ważny.';
$_['help_max_attempts']         = 'Maksymalna liczba niepoprawnych wprowadzeń OTP lub numeru telefonu przed zablokowaniem.';
$_['help_lockout_time']         = 'Czas w minutach, przez który użytkownik będzie zablokowany po przekroczeniu liczby prób.';
$_['help_throttle_time']        = 'Minimalny czas w sekundach między żądaniami OTP, aby zapobiec spamowi.';
$_['help_logging']              = 'Włącz, aby zapisywać próby OTP w logu.';
$_['help_logs']                 = 'Ta lista zawiera wszystkie próby wprowadzenia OTP przez użytkowników.';

// Error
$_['error_permission']          = 'Uwaga: Nie masz uprawnień do zmiany modułu Autoryzacji przez telefon!';
$_['error_status']              = 'Status modułu jest wymagany!';
$_['error_otp_length']          = 'Długość OTP musi być liczbą od 4 do 10!';
$_['error_otp_expiry']          = 'Czas ważności OTP musi być większy niż 0!';
$_['error_max_attempts']        = 'Maksymalna liczba prób musi być większa niż 0!';
$_['error_lockout_time']        = 'Czas blokady musi być większy niż 0!';
$_['error_throttle_time']       = 'Czas między żądaniami musi być większy niż 0!';
$_['error_no_settings']         = 'Nie znaleziono ustawień modułu!';
$_['warning_sms_template']      = 'Uwaga: Szablon wiadomości SMS nie jest zainstalowany lub wyłączony! Włącz szablon "Autoryzacja przez SMS" w module "Ustawienia powiadomień SMS"!';

// Tabs
$_['tab_settings']              = 'Ustawienia';
$_['tab_logs']                  = 'Logi OTP';
$_['tab_ip_logs']               = 'Logi IP';

// Buttons
$_['button_save']               = 'Zapisz';
$_['button_cancel']             = 'Anuluj';
$_['button_delete_logs']        = 'Usuń logi';
$_['button_close']              = 'Zamknij';

// Columns
$_['column_log_id']             = 'ID Logu';
$_['column_customer_id']        = 'ID Klienta';
$_['column_telephone']          = 'Telefon';
$_['column_status']             = 'Status';
$_['column_date_added']         = 'Data dodania';
$_['column_attempt_id']         = 'ID';
$_['column_ip_address']         = 'Adres IP';
$_['column_attempt_count']      = 'Liczba prób';
$_['column_last_attempt']       = 'Ostatnia próba';