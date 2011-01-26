<?php // -*-mode: PHP; coding:utf-8;-*-
// $Id$

// Ten plik zawiera kod PHP, który określa język konkretnych stringów.
// Domyślne stringi pochodzą z lang.en, i jakiekolwiek zmiany w konkretnych
// plikach lokalizacji nadpiszą domyślne stringi. To jest plik dla języka angielskiego US/UK.
//
//
//
//
// Ten plik to kod PHP i proszę traktować go jako taki.

// Uzyte w style.inc
$vocab["mrbs"]               = "Meeting Room Booking System";

// Uzyte w functions.inc
$vocab["report"]             = "Raport";
$vocab["admin"]              = "Administrator";
$vocab["help"]               = "Pomoc";
$vocab["search"]             = "Szukaj";
$vocab["not_php3"]           = "UWAGA: To najprawdopodobniej nie dziala z PHP3";

// Uzyte w day.php
$vocab["bookingsfor"]        = "Rezerwacja dla";
$vocab["bookingsforpost"]    = ""; // To idzie po dacie
$vocab["areas"]              = "Strefy";
$vocab["daybefore"]          = "Idź do dnia przed";
$vocab["dayafter"]           = "Idź do dnia po";
$vocab["gototoday"]          = "Idź do dnia dzisiejszego";
$vocab["goto"]               = "Idź do";
$vocab["highlight_line"]     = "Podświetl tą linie";
$vocab["click_to_reserve"]   = "Kliknij na komórkę aby zarezerwować.";

// Uzyte w trailer.inc
$vocab["viewday"]            = "Zobacz Dzień";
$vocab["viewweek"]           = "Zobacz Tydzień";
$vocab["viewmonth"]          = "Zobacz Miesiąc";
$vocab["ppreview"]           = "Wydrukuj Podgląd";

// Uzyte w edit_entry.php
$vocab["addentry"]           = "Dodaj Wpis";
$vocab["editentry"]          = "Edytuj Wpis";
$vocab["copyentry"]          = "Kopiuj Wpis";
$vocab["editseries"]         = "Edytuj Serie";
$vocab["copyseries"]         = "Kopiuj Serie";
$vocab["namebooker"]         = "Krótki Opis";
$vocab["fulldescription"]    = "Pełen Opis:<br>&nbsp;&nbsp;(Liczba osób,<br>&nbsp;&nbsp;Wewnętrzne/Zewnętrzne itp)";
$vocab["date"]               = "Data";
$vocab["start_date"]         = "Czas Rozpoczęcia";
$vocab["end_date"]           = "Czas Edycji";
$vocab["time"]               = "Czas";
$vocab["period"]             = "Okres";
$vocab["duration"]           = "Czas Trwania";
$vocab["seconds"]            = "sekundy";
$vocab["minutes"]            = "minuty";
$vocab["hours"]              = "godziny";
$vocab["days"]               = "dni";
$vocab["weeks"]              = "tygodnie";
$vocab["years"]              = "lata";
$vocab["periods"]            = "okresy";
$vocab["all_day"]            = "Cały dzień";
$vocab["type"]               = "Typ";
$vocab["internal"]           = "Wewnętrzny";
$vocab["external"]           = "Zewnętrzny";
$vocab["save"]               = "Zapisz";
$vocab["rep_type"]           = "Powtórz Typ";
$vocab["rep_type_0"]         = "Żaden";
$vocab["rep_type_1"]         = "Dzienny";
$vocab["rep_type_2"]         = "Tygodniowy";
$vocab["rep_type_3"]         = "Miesięczny";
$vocab["rep_type_4"]         = "Roczny";
$vocab["rep_type_5"]         = "Miesięczny, odpowiadający dzień";
$vocab["rep_type_6"]         = "n-Tygodniowy";
$vocab["rep_end_date"]       = "Powtórz Datę końcową";
$vocab["rep_rep_day"]        = "Powtórz Datę";
$vocab["rep_for_weekly"]     = "(dla (n-)tygodniowy)";
$vocab["rep_freq"]           = "Częstotliwość";
$vocab["rep_num_weeks"]      = "Liczba Tygodni";
$vocab["rep_for_nweekly"]    = "(dla n-tygodniowy)";
$vocab["ctrl_click"]         = "Klikaj myszą przytrzymując klawisz Ctrl aby wybrać więcej niż jeden pokój";
$vocab["entryid"]            = "ID Wpisu ";
$vocab["repeat_id"]          = "Powtórz ID ";
$vocab["you_have_not_entered"] = "Nie wprowadziłeś";
$vocab["you_have_not_selected"] = "Nie wybrałeś";
$vocab["valid_room"]         = "pokój.";
$vocab["brief_description"]  = "Krótki Opis.";
$vocab["useful_n-weekly_value"] = "przydatna n-tygodniowa wartość.";

// Uzyte w view_entry.php
$vocab["description"]        = "Opis";
$vocab["room"]               = "Pokój";
$vocab["createdby"]          = "Utworzony Przez";
$vocab["lastupdate"]         = "Ostatnio Aktualizowany";
$vocab["deleteentry"]        = "Usuń Wpis";
$vocab["deleteseries"]       = "Usuń Wpisy";
$vocab["confirmdel"]         = "Czy jesteś pewien\\nże chcesz usunąć\\nten wpis?\\n\\n";
$vocab["returnprev"]         = "Powrót do poprzedniej strony";
$vocab["invalid_entry_id"]   = "Niewłaściwy ID wpisu";
$vocab["invalid_series_id"]  = "Niewłaściwy ID serii";

// Uzyte w edit_entry_handler.php
$vocab["error"]              = "Błąd";
$vocab["sched_conflict"]     = "Konflikt Planowania";
$vocab["conflict"]           = "Nowa rezerwacja będzie kolidowała z następującym(i) wpisem(-ami)";
$vocab["too_may_entrys"]     = "Wybrana opcja utworzy zbyt wiele wpisów.<br>Proszę użyć innego wyboru!";
$vocab["returncal"]          = "Powrót do widoku kalendarza";
$vocab["failed_to_acquire"]  = "Błąd podczas uzyskania wyłącznego dostępu do bazy.";
$vocab["invalid_booking"]    = "Niewłaściwa rezerwacja";
$vocab["must_set_description"] = "Musisz podać krótki opis rezerwacji. Proszę, wróć i wprowadź go.";
$vocab["mail_subject_new_entry"]     = "Wpis wprowadzony w $mrbs_company MRBS";
$vocab["mail_subject_changed_entry"] = "Wpis zmieniony w $mrbs_company MRBS";
$vocab["mail_subject_delete"]        = "Wpis usunięty z $mrbs_company MRBS";
$vocab["mail_body_new_entry"]     = "Wpis został utworzony, oto szczegóły operacji:";
$vocab["mail_body_changed_entry"] = "Wpis został zmieniony, oto szczegóły operacji:";
$vocab["mail_body_del_entry"]     = "Wpis został usunięty, oto szczegóły operacji:";

// Autentykacja
$vocab["accessdenied"]       = "Dostęp Wzbroniony";
$vocab["norights"]           = "Nie masz uprawnień do zmiany tego obiektu.";
$vocab["please_login"]       = "Proszę, zaloguj się";
$vocab["users.name"]          = "Nazwa";
$vocab["users.password"]      = "Hasło";
$vocab["users.level"]         = "Uprawnienia";
$vocab["unknown_user"]       = "Nieznany użytkownik";
$vocab["you_are"]            = "Zalogowany";
$vocab["login"]              = "Zaloguj";
$vocab["logoff"]             = "Wyloguj";

// Autentykacja - baza
$vocab["user_list"]          = "Lista użytkowników";
$vocab["edit_user"]          = "Edytuj";
$vocab["delete_user"]        = "Usuń";
//$vocab["users.name"]         = Use the same as above, for consistency.
//$vocab["users.password"]     = Use the same as above, for consistency.
$vocab["users.email"]         = "adres email";
$vocab["password_twice"]     = "Jeśli chcesz zmienić hasło, wpisz dwukrotnie nowe hasło";
$vocab["passwords_not_eq"]   = "Błąd! Powtórzone hasła różnią się";
$vocab["add_new_user"]       = "Dodaj nowego użytkownika";
$vocab["action"]             = "Akcja";
$vocab["user"]               = "Użytkownik";
$vocab["administrator"]      = "Administrator";
$vocab["unknown"]            = "Nieznany";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Wyświetl moje wszystkie nadchodzące wpisy";
$vocab["no_users_initial"]   = "Brak użytkownika w bazie, możliwe utworzenie początkowego użytkownika";
$vocab["no_users_create_first_admin"] = "Utwórz użytkownika z uprawnieniami administratora. Potem logując się na niego, utworzysz pozostałych użytkowników.";

// Uzyte w search.php
$vocab["invalid_search"]     = "Pusty lub niewłaściwy łańcuch wyszukiwania.";
$vocab["search_results"]     = "Wyniki Wyszukiwania dla";
$vocab["nothing_found"]      = "Brak wyników spełniających kryteria.";
$vocab["records"]            = "Wyniki ";
$vocab["through"]            = " przez ";
$vocab["of"]                 = " z ";
$vocab["previous"]           = "Poprzedni";
$vocab["next"]               = "Następny";
$vocab["entry"]              = "Wpis";
$vocab["view"]               = "Widok";
$vocab["advanced_search"]    = "Wyszukiwanie zaawansowane";
$vocab["search_button"]      = "Wyszukiwanie";
$vocab["search_for"]         = "Szukaj";
$vocab["from"]               = "Od";

// Uzyte w report.php
$vocab["report_on"]          = "Raport ze Zpotkań";
$vocab["report_start"]       = "Data początku raportu";
$vocab["report_end"]         = "Data końca raportu";
$vocab["match_area"]         = "Zgodny Teren";
$vocab["match_room"]         = "Zgodny Pokój";
$vocab["match_type"]         = "Zgodny Typ";
$vocab["ctrl_click_type"]    = "Klikaj myszą przytrzymując klawisz Ctrl aby wybrać więcej opcji";
$vocab["match_entry"]        = "Zgodny krótki opis";
$vocab["match_descr"]        = "Zgodny pełny opis";
$vocab["include"]            = "Dołącz";
$vocab["report_only"]        = "Tylko Raport";
$vocab["summary_only"]       = "Tylko Podsumowanie";
$vocab["report_and_summary"] = "Raport i Podsumowanie";
$vocab["summarize_by"]       = "Sumowanie po";
$vocab["sum_by_descrip"]     = "Krótki opis";
$vocab["sum_by_creator"]     = "Autor";
$vocab["entry_found"]        = "Pozycja Znaleziona";
$vocab["entries_found"]      = "Pozycje Znaleziono";
$vocab["summary_header"]     = "Podsumowanie Godzin (Pozycje)";
$vocab["summary_header_per"] = "Podsumowanie Okresów (Pozycje)";
$vocab["total"]              = "Razem";
$vocab["submitquery"]        = "Uruchom Raport";
$vocab["sort_rep"]           = "Sortuj Raport po";
$vocab["sort_rep_time"]      = "Start Data/Czas";
$vocab["rep_dsp"]            = "Wyświetl w raporcie";
$vocab["rep_dsp_dur"]        = "Trwanie";
$vocab["rep_dsp_end"]        = "Czas Zakończenia";

// Uzyte w week.php
$vocab["weekbefore"]         = "Przejdź do Poprzedniego Tygodnia";
$vocab["weekafter"]          = "Przejdź do Następnego Tygodnia";
$vocab["gotothisweek"]       = "Przejdź Do Bieżącego Tygodnia";

// Uzyte w month.php
$vocab["monthbefore"]        = "Przejdź do Poprzedniego Miesiąca";
$vocab["monthafter"]         = "Przejdż do Następnego Miesiąca";
$vocab["gotothismonth"]      = "Przejdź do Bieżącegoo Miesiąca";

// Uzyte w {day week month}.php
$vocab["no_rooms_for_area"]  = "Brak zdefiniowanych pokoi dla tej strefy";

// Uzyte w admin.php
$vocab["edit"]               = "Edycja";
$vocab["delete"]             = "Usuń";
$vocab["rooms"]              = "Pokoje";
$vocab["in"]                 = "w";
$vocab["noareas"]            = "Brak Stref";
$vocab["addarea"]            = "Dodaj Strefę";
$vocab["name"]               = "Nazwa";
$vocab["noarea"]             = "Brak wybranych stref";
$vocab["browserlang"]        = "Twoja przeglądarka jest ustwaiona na następującą kolejność preferencji językowych";
$vocab["addroom"]            = "Dodaj Pokój";
$vocab["capacity"]           = "Pojemność";
$vocab["norooms"]            = "Brak Pokoi.";
$vocab["administration"]     = "Administracja";

// Uzyte w edit_area_room.php
$vocab["editarea"]           = "Edytuj Strefę";
$vocab["change"]             = "Zmień";
$vocab["backadmin"]          = "Powrót do Admin";
$vocab["editroomarea"]       = "Edycja Strefy lub Opis Pokoju";
$vocab["editroom"]           = "Edycja Pokoju";
$vocab["update_room_failed"] = "Uaktualnij pole pokoju: ";
$vocab["error_room"]         = "Błąd: Pokój ";
$vocab["not_found"]          = " nie znaleziono";
$vocab["update_area_failed"] = "Uaktualnij pole strefy: ";
$vocab["error_area"]         = "Błąd: strefa ";
$vocab["room_admin_email"]   = "Email do administratora pokoju";
$vocab["area_admin_email"]   = "Email do administratora strefy";
$vocab["invalid_email"]      = "Błędny email!";

// Uzyte w edit_users.php
$vocab["name_empty"]         = "Musisz podać nazwę.";
$vocab["name_not_unique"]    = "Nazwa aktualnie istnieje.  Prosze wybrać inną.";

// Uzyte w del.php
$vocab["deletefollowing"]    = "To skasuje aktualne rezerwacje";
$vocab["sure"]               = "Czy aby na pewno?";
$vocab["YES"]                = "TAK";
$vocab["NO"]                 = "NIE";
$vocab["delarea"]            = "Musisz usunąc wszystkie pokoje w tej strefie przed usunięciem jej<p>";

// Uzyte w help.php
$vocab["about_mrbs"]         = "Informacje o MRBS";
$vocab["database"]           = "Baza Danch";
$vocab["system"]             = "System";
$vocab["servertime"]         = "Czas Serwera";
$vocab["please_contact"]     = "Prosze o kontakt ";
$vocab["for_any_questions"]  = "W sprawie pytań na które brak tu odpowiedzi.";

// Uzyte w mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Błąd Krytyczny: Błąd połączenia z bazą danych";
