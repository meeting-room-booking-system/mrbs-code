<?php // -*-mode: PHP; coding:utf-8;-*-

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is a sl_SI Slovenian file.
// translated by Martin Terbuc 2007/02/24
//
//
//
// This file is PHP code. Treat it as such.

// Used in style.inc
$vocab["mrbs"]               = "Prikaži urnike prostorov";

// Used in functions.inc
$vocab["report"]             = "Poročilo";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Pomoč";
$vocab["search"]             = "Išči";

// Used in index.php
$vocab["bookingsfor"]        = "Rezervacija za";
$vocab["bookingsforpost"]    = ""; // Goes after the date
$vocab["areas"]              = "Področja";
$vocab["daybefore"]          = "Prejšnji dan";
$vocab["dayafter"]           = "Naslednji dan";
$vocab["gototoday"]          = "Danes";
$vocab["goto"]               = "pojdi";
$vocab["nav_day"]            = "Dan";
$vocab["nav_week"]           = "Teden";
$vocab["nav_month"]          = "Mesec";
$vocab["highlight_line"]     = "Poudari to vrsto";
$vocab["click_to_reserve"]   = "Za dodajanje rezervacije klikni na celico.";
$vocab["weekbefore"]         = "Prejšni teden";
$vocab["weekafter"]          = "Naslednji teden";
$vocab["gotothisweek"]       = "Ta teden";
$vocab["monthbefore"]        = "Prejšni mesec";
$vocab["monthafter"]         = "Naslednji mesec";
$vocab["gotothismonth"]      = "Ta mesec";
$vocab["no_rooms_for_area"]  = "Ni definiranih prostorov v tem področju";

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
$vocab["fulldescription"]    = "Dolgi opis";
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
$vocab["save"]               = "Shrani";
$vocab["rep_type"]           = "Način ponavljanja";
$vocab["rep_type_0"]         = "Brez";
$vocab["rep_type_1"]         = "Dnevno";
$vocab["rep_type_2"]         = "Tedensko";
$vocab["rep_type_3"]         = "Mesečno";
$vocab["rep_type_4"]         = "Letno";
$vocab["rep_end_date"]       = "Datum konca ponavljanj";
$vocab["rep_rep_day"]        = "Ponavljaj dni";
$vocab["ctrl_click"]         = "Uporabite Ctrl+klik za izbiro več prostorov";
$vocab["entryid"]            = "ID vnosa ";
$vocab["repeat_id"]          = "ID ponavljanj"; 
$vocab["brief_description"]  = "kratek opis.";

// Used in view_entry.php
$vocab["description"]        = "Opis";
$vocab["room"]               = "Prostor";
$vocab["createdby"]          = "Vnesel";
$vocab["lastupdate"]         = "Zadnja sprememba";
$vocab["deleteentry"]        = "Izbriši vnos";
$vocab["deleteseries"]       = "Izbriši ponavljanja";
$vocab["confirmdel"]         = "Ste prepričani da želite izbrisati ta vnos?";
$vocab["returnprev"]         = "Vrni na prejšnjo stran";
$vocab["invalid_entry_id"]   = "Napačen vnos.";
$vocab["invalid_series_id"]  = "Napačen vnos ponavljanj.";

// Used in edit_entry_handler.php
$vocab["error"]              = "Napaka";
$vocab["sched_conflict"]     = "Konflikt rezervacij";
$vocab["conflict"]           = "Konflikt nove rezervacije z naslednjim(i) obsoječim(i)";
$vocab["too_many_entries"]   = "Izbrane nastavitve bi ustvarile preveč vnosov.<br>Prosimo izvedite drugačno izbiro!";
$vocab["returncal"]          = "Vrnitev na pogled koledarja";
$vocab["failed_to_acquire"]  = "Napaka pri dostopu do baze";
$vocab["invalid_booking"]    = "Napačna rezervacija";
$vocab["must_set_description"] = "Vnesti morate kratek opis rezervacije. Prosimo vrnite se in jo vnesite.";
$vocab["mail_subject_new_entry"]     = "Vnos dodan/spremenjen za vaš MRBS"; // $mrbs_company
$vocab["mail_subject_changed_entry"] = "Vnos dodan/spremenjen za vaš MRBS"; // $mrbs_company
$vocab["mail_subject_delete"]        = "Vnos za vaš MRBS je bil izbrisan"; // $mrbs_company
$vocab["mail_body_new_entry"]     = "Dodan je bil nov vnos in tukaj so podrobnosti:";
$vocab["mail_body_changed_entry"] = "Vnos je bil spremenjen in tukaj so podrobnosti:";
$vocab["mail_body_del_entry"]     = "Vnos je bil izbrisan in tukaj so podrobnosti:";

// Authentication stuff
$vocab["accessdenied"]       = "Dostop zavrnjen";
$vocab["norights"]           = "Nimate pravice spreminjanja tega.";
$vocab["please_login"]       = "Prosim, prijavite se";
$vocab["users.name"]          = "Uporabniško ime";
$vocab["users.password"]      = "Geslo";
$vocab["users.level"]         = "Pravice";
$vocab["unknown_user"]       = "Neznan uporabnik";
$vocab["login"]              = "Prijava";
$vocab["logoff"]             = "Odjava";
$vocab["username_or_email"]  = "Uporabniško ime ali e-poštni naslo";

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

// Used in edit_area.php and/or edit_room.php
$vocab["editarea"]           = "Uredi področje";
$vocab["change"]             = "Uporabi";
$vocab["backadmin"]          = "Nazaj v Admin";
$vocab["editroom"]           = "Uredi prostor";
$vocab["not_found"]          = " ne najdem";
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

// Entry types
$vocab["type.I"]             = "Interno";
$vocab["type.E"]             = "Zunanje";

