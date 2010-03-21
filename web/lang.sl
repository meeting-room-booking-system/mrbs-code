<?php // -*-mode: PHP; coding:utf-8;-*-

// $Id$

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is a sl_SI Slovenian file.
// translated by Martin Terbuc 2007/02/24
//
//
//
// This file is PHP code. Treat it as such.

// The charset to use in "Content-type" header
$vocab["charset"]            = "utf-8";

// Used in style.inc
$vocab["mrbs"]               = "Prikaži urnike prostorov";

// Used in functions.inc
$vocab["report"]             = "Poročilo";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Pomoč";
$vocab["search"]             = "Išči";
$vocab["not_php3"]           = "OPOZORILO: Verjetno ne bo delovalo z PHP3";

// Used in day.php
$vocab["bookingsfor"]        = "Rezervacija za";
$vocab["bookingsforpost"]    = ""; // Goes after the date
$vocab["areas"]              = "Področja";
$vocab["daybefore"]          = "Prejšnji dan";
$vocab["dayafter"]           = "Naslednji dan";
$vocab["gototoday"]          = "Danes";
$vocab["goto"]               = "pojdi";
$vocab["highlight_line"]     = "Poudari to vrsto";
$vocab["click_to_reserve"]   = "Za dodajanje rezervacije klikni na celico.";

// Used in trailer.inc
$vocab["viewday"]            = "Pogled dan";
$vocab["viewweek"]           = "Pogled teden";
$vocab["viewmonth"]          = "Pogled mesec";
$vocab["ppreview"]           = "Predogled tiskanja";

// Used in edit_entry.php
$vocab["addentry"]           = "Dodaj vnos";
$vocab["editentry"]          = "Uredi vnos";
$vocab["copyentry"]          = "Kopiraj vnos";
$vocab["editseries"]         = "Uredi ponavljanja";
$vocab["copyseries"]         = "Kopiraj vrsto";
$vocab["namebooker"]         = "Kratek opis";
$vocab["fulldescription"]    = "Dolgi opis:<br>&nbsp;&nbsp;(Število oseb,<br>&nbsp;&nbsp;Interno/Zunanje, itd.)";
$vocab["date"]               = "Datum";
$vocab["start_date"]         = "Začetni čas";
$vocab["end_date"]           = "Končni čas";
$vocab["time"]               = "Čas";
$vocab["period"]             = "Ponavljajoč";
$vocab["duration"]           = "Trajanje (za decimalko uporabi piko)";
$vocab["seconds"]            = "sekund";
$vocab["minutes"]            = "minut";
$vocab["hours"]              = "ur";
$vocab["days"]               = "dni";
$vocab["weeks"]              = "tednov";
$vocab["years"]              = "let";
$vocab["periods"]            = "ponavljanj";
$vocab["all_day"]            = "Vse dni";
$vocab["type"]               = "Tip";
$vocab["internal"]           = "Interno";
$vocab["external"]           = "Zunanje";
$vocab["save"]               = "Shrani";
$vocab["rep_type"]           = "Način ponavljanja";
$vocab["rep_type_0"]         = "Brez";
$vocab["rep_type_1"]         = "Dnevno";
$vocab["rep_type_2"]         = "Tedensko";
$vocab["rep_type_3"]         = "Mesečno";
$vocab["rep_type_4"]         = "Letno";
$vocab["rep_type_5"]         = "Mesečno na pripadajoč dan v tednu";
$vocab["rep_type_6"]         = "n-tednov";
$vocab["rep_end_date"]       = "Datum konca ponavljanj";
$vocab["rep_rep_day"]        = "Ponavljaj dni";
$vocab["rep_for_weekly"]     = "(ponavljaj (n-tednov)";
$vocab["rep_freq"]           = "Frequenca";
$vocab["rep_num_weeks"]      = "Število tednov ";
$vocab["rep_for_nweekly"]    = "(za n-tednov)";
$vocab["ctrl_click"]         = "Uporabite Ctrl+klik za izbiro več prostorov";
$vocab["entryid"]            = "ID vnosa ";
$vocab["repeat_id"]          = "ID ponavljanj"; 
$vocab["you_have_not_entered"] = "Niste vnesli";
$vocab["you_have_not_selected"] = "Niste izbrali";
$vocab["valid_room"]         = "prostor.";
$vocab["valid_time_of_day"]  = "veljavne ure v dnevu.";
$vocab["brief_description"]  = "kratek opis.";
$vocab["useful_n-weekly_value"] = "prave vrednosti za n-tednov.";

// Used in view_entry.php
$vocab["description"]        = "Opis";
$vocab["room"]               = "Prostor";
$vocab["createdby"]          = "Vnesel";
$vocab["lastupdate"]         = "Zadnja sprememba";
$vocab["deleteentry"]        = "Izbriši vnos";
$vocab["deleteseries"]       = "Izbriši ponavljanja";
$vocab["confirmdel"]         = "Ste prepričani\\nda želite\\nizbrisati ta vnos?\\n\\n";
$vocab["returnprev"]         = "Vrni na prejšnjo stran";
$vocab["invalid_entry_id"]   = "Napačen vnos.";
$vocab["invalid_series_id"]  = "Napačen vnos ponavljanj.";

// Used in edit_entry_handler.php
$vocab["error"]              = "Napaka";
$vocab["sched_conflict"]     = "Konflikt rezervacij";
$vocab["conflict"]           = "Konflikt nove rezervacije z naslednjim(i) obsoječim(i)";
$vocab["too_may_entrys"]     = "Izbrane nastavitve bi ustvarile preveč vnosov.<br>Prosimo izvedite drugačno izbiro!";
$vocab["returncal"]          = "Vrnitev na pogled koledarja";
$vocab["failed_to_acquire"]  = "Napaka pri dostopu do baze";
$vocab["invalid_booking"]    = "Napačna rezervacija";
$vocab["must_set_description"] = "Vnesti morate kratek opis rezervacije. Prosimo vrnite se in jo vnesite.";
$vocab["mail_subject_entry"] = "Vnos dodan/spremenjen za vaš MRBS";
$vocab["mail_body_new_entry"] = "Dodan je bil nov vnos in tukaj so podrobnosti:";
$vocab["mail_body_del_entry"] = "Vnos je bil izbrisan in tukaj so podrobnosti:";
$vocab["mail_body_changed_entry"] = "Vnos je bil spremenjen in tukaj so podrobnosti:";
$vocab["mail_subject_delete"] = "Vnos za vaš MRBS je bil izbrisan";

// Authentication stuff
$vocab["accessdenied"]       = "Dostop zavrnjen";
$vocab["norights"]           = "Nimate pravice spreminjanja tega.";
$vocab["please_login"]       = "Prosim, prijavite se";
$vocab["users.name"]          = "Uporabniško ime";
$vocab["users.password"]      = "Geslo";
$vocab["users.level"]         = "Pravice";
$vocab["unknown_user"]       = "Neznan uporabnik";
$vocab["you_are"]            = "Prijavljen";
$vocab["login"]              = "Prijava";
$vocab["logoff"]             = "Odjava";

// Authentication database
$vocab["user_list"]          = "Spisek uporabnikov";
$vocab["edit_user"]          = "Uredi uporabnika";
$vocab["delete_user"]        = "Izbriši tega uporabnika";
//$vocab["users.name"]         = Use the same as above, for consistency.
//$vocab["users.password"]     = Use the same as above, for consistency.
$vocab["users.email"]         = "e-pošni naslov";
$vocab["password_twice"]     = "Če želite zamenjati geslo, ga vtipkajte dvakrat";
$vocab["passwords_not_eq"]   = "Napaka: Gesli se ne ujemata.";
$vocab["add_new_user"]       = "Dodaj novega uporabnika";
$vocab["action"]             = "Dejanja";
$vocab["user"]               = "Uporabnik";
$vocab["administrator"]      = "Administrator";
$vocab["unknown"]            = "Neznan";
$vocab["ok"]                 = "Vredu";
$vocab["show_my_entries"]    = "Kliknite za prikaz vseh prihodnjih dogodkov";
$vocab["no_users_initial"]   = "V bazi ni uporabnikov, kreiranje osnovnih";
$vocab["no_users_create_first_admin"] = "Ustvarite uporabnika konfiguriranega kakor administrator in se prijavite, da boste lahko dodajali uporabnike.";

// Used in search.php
$vocab["invalid_search"]     = "Prazen ali napačen iskalni niz.";
$vocab["search_results"]     = "Rezultati iskanja za";
$vocab["nothing_found"]      = "Ni najdenih vnosov niza.";
$vocab["records"]            = "Vnosi ";
$vocab["through"]            = " do ";
$vocab["of"]                 = " od ";
$vocab["previous"]           = "Predhodni";
$vocab["next"]               = "Naslednji";
$vocab["entry"]              = "Vnos";
$vocab["view"]               = "Poglej";
$vocab["advanced_search"]    = "Napredno iskanje";
$vocab["search_button"]      = "Išči";
$vocab["search_for"]         = "Iskanje";
$vocab["from"]               = "Od";

// Used in report.php
$vocab["report_on"]          = "Poročila vnosov";
$vocab["report_start"]       = "Začetni datum poročila";
$vocab["report_end"]         = "Končni datum poročila";
$vocab["match_area"]         = "Ujemanje niza iz opisa področij";
$vocab["match_room"]         = "Ujemanje niza iz opisa prostorov";
$vocab["match_type"]         = "Za tip";
$vocab["ctrl_click_type"]    = "Uporabi Ctrl+klik za izbiro več tipov.";
$vocab["match_entry"]        = "Ujemanje niz iz kratkega opisa";
$vocab["match_descr"]        = "Ujemanje niz iz dolgega opisa";
$vocab["include"]            = "Vključi";
$vocab["report_only"]        = "Samo vnose";
$vocab["summary_only"]       = "Samo pregled";
$vocab["report_and_summary"] = "Vnose in pregled";
$vocab["summarize_by"]       = "Pregled po";
$vocab["sum_by_descrip"]     = "Kratkem opisu";
$vocab["sum_by_creator"]     = "Po vnosniku";
$vocab["entry_found"]        = "najden vnos";
$vocab["entries_found"]      = "najdenih vnosov";
$vocab["summary_header"]     = "Pregled (vnosov) ur";
$vocab["summary_header_per"] = "Pregled (vnosov) ponavljanj";
$vocab["total"]              = "Skupaj";
$vocab["submitquery"]        = "Naredi poročilo";
$vocab["sort_rep"]           = "Uredi poročilo po";
$vocab["sort_rep_time"]      = "Začetni datum/ura";
$vocab["rep_dsp"]            = "V poročilu prikaži";
$vocab["rep_dsp_dur"]        = "Trajanje";
$vocab["rep_dsp_end"]        = "Začetni - končni čas";

// Used in week.php
$vocab["weekbefore"]         = "Prejšni teden";
$vocab["weekafter"]          = "Naslednji teden";
$vocab["gotothisweek"]       = "Ta teden";

// Used in month.php
$vocab["monthbefore"]        = "Prejšni mesec";
$vocab["monthafter"]         = "Naslednji mesec";
$vocab["gotothismonth"]      = "Ta mesec";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Ni definiranih prostorov v tem področju";

// Used in admin.php
$vocab["edit"]               = "Uredi";
$vocab["delete"]             = "Izbriši";
$vocab["rooms"]              = "Prostori";
$vocab["in"]                 = "v";
$vocab["noareas"]            = "Ni področij";
$vocab["addarea"]            = "Dodaj področje";
$vocab["name"]               = "Ime";
$vocab["noarea"]             = "Ni izbranega področja";
$vocab["browserlang"]        = "Vaš brskalnik je nastavljen za uporabo ";
$vocab["addroom"]            = "Dodaj prostor";
$vocab["capacity"]           = "Število mest";
$vocab["norooms"]            = "Ni prostorov.";
$vocab["administration"]     = "Administracija";

// Used in edit_area_room.php
$vocab["editarea"]           = "Uredi področje";
$vocab["change"]             = "Uporabi";
$vocab["backadmin"]          = "Nazaj v Admin";
$vocab["editroomarea"]       = "Uredi opis področja ali prostora";
$vocab["editroom"]           = "Uredi prostor";
$vocab["update_room_failed"] = "Sprememba za prostor ni uspela: ";
$vocab["error_room"]         = "Napaka: prostor ";
$vocab["not_found"]          = " ne najdem";
$vocab["update_area_failed"] = "Ni uspela posodobitev področja: ";
$vocab["error_area"]         = "Napaka: področje ";
$vocab["room_admin_email"]   = "e-pošta upravnika prostora";
$vocab["area_admin_email"]   = "e-pošta upravnika področja";
$vocab["invalid_email"]      = "Napačen e-pošni naslov!";

// Used in del.php
$vocab["deletefollowing"]    = "Izbrisali boste naslednje vnose";
$vocab["sure"]               = "Ste prepričanie?";
$vocab["YES"]                = "Da";
$vocab["NO"]                 = "NE";
$vocab["delarea"]            = "Izbrisati morate vse prostore v področju, preden ga lahko izbrišete<p>";

// Used in help.php
$vocab["about_mrbs"]         = "O MRBS";
$vocab["database"]           = "Podatkovna zbirka";
$vocab["system"]             = "Sistem";
$vocab["servertime"]         = "Čas strežnika";
$vocab["please_contact"]     = "Na dodatna vprašanja vam bo odgovoril ";
$vocab["for_any_questions"]  = ".";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "NAPAKA: ni se možno povezati v podatkovno bazo";

?>

