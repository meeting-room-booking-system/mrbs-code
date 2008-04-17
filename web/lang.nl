<?php
// $Id$

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is an NL Dutch file.
//
// Translations provided by: Marc ter Horst
//
//
// This file is PHP code. Treat it as such.

// The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

// Used in style.inc
$vocab["mrbs"]               = "Vergaderruimte Boekingssysteem";

// Used in functions.inc
$vocab["report"]             = "Rapportage";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Help";
$vocab["search"]             = "Zoek";
$vocab["not_php3"]             = "<H1>Waarschuwing: Werkt waarschijnlijk niet met PHP3</H1>";

// Used in day.php
$vocab["bookingsfor"]        = "Boekingen voor";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Gebouwen";
$vocab["daybefore"]          = "Naar Vorige Dag";
$vocab["dayafter"]           = "Naar Volgende Dag";
$vocab["gototoday"]          = "Naar Vandaag";
$vocab["goto"]               = "ga naar";
$vocab["highlight_line"]     = "Markeer deze regel";
$vocab["click_to_reserve"]   = "Klik op dit vak om een reservering te maken.";

// Used in trailer.inc
$vocab["viewday"]            = "Bekijk Dag";
$vocab["viewweek"]           = "Bekijk Week";
$vocab["viewmonth"]          = "Bekijk Maand";
$vocab["ppreview"]           = "Afdruk Voorbeeld";

// Used in edit_entry.php
$vocab["addentry"]           = "Boeking Toevoegen";
$vocab["editentry"]          = "Boeking Wijzigen";
$vocab["editseries"]         = "Wijzig Reeks";
$vocab["namebooker"]         = "Korte Omschrijving";
$vocab["fulldescription"]    = "Volledige Omschrijving:<br>&nbsp;&nbsp;(Aantal mensen,<br>&nbsp;&nbsp;Intern/Extern etc)";
$vocab["date"]               = "Datum";
$vocab["start_date"]         = "Start Tijd";
$vocab["end_date"]           = "Eind Tijd";
$vocab["time"]               = "Tijd";
$vocab["period"]             = "Period";
$vocab["duration"]           = "Tijdsduur";
$vocab["seconds"]            = "seconden";
$vocab["minutes"]            = "minuten";
$vocab["hours"]              = "uren";
$vocab["days"]               = "dagen";
$vocab["weeks"]              = "weken";
$vocab["years"]              = "jaren";
$vocab["periods"]            = "periods";
$vocab["all_day"]            = "Hele Dag";
$vocab["type"]               = "Soort";
$vocab["internal"]           = "Intern";
$vocab["external"]           = "Extern";
$vocab["save"]               = "Opslaan";
$vocab["rep_type"]           = "Soort Herhaling";
$vocab["rep_type_0"]         = "Geen";
$vocab["rep_type_1"]         = "Dagelijks";
$vocab["rep_type_2"]         = "Wekelijks";
$vocab["rep_type_3"]         = "Maandelijks";
$vocab["rep_type_4"]         = "Jaarlijks";
$vocab["rep_type_5"]         = "Maandelijks, Overeenkomstige dag";
$vocab["rep_type_6"]         = "n-wekelijks";
$vocab["rep_end_date"]       = "Einde herhaling datum";
$vocab["rep_rep_day"]        = "Herhalingsdag";
$vocab["rep_for_weekly"]     = "(t.b.v. wekelijks)";
$vocab["rep_freq"]           = "Frequentie";
$vocab["rep_num_weeks"]      = "Aantal weken";
$vocab["rep_for_nweekly"]    = "(Voor n-wekelijks)";
$vocab["ctrl_click"]         = "Gebruik Control-Linker muis klik om meer dan 1 ruimte te reserveren";
$vocab["entryid"]            = "Boeking-ID ";
$vocab["repeat_id"]          = "Herhalings-ID "; 
$vocab["you_have_not_entered"] = "U heeft het volgende niet ingevoerd : ";
$vocab["you_have_not_selected"] = "U heeft het volgende niet geselecteerd : ";
$vocab["valid_room"]         = "kamer.";
$vocab["valid_time_of_day"]  = "geldige tijd.";
$vocab["brief_description"]  = "Korte Omschrijving.";
$vocab["useful_n-weekly_value"] = "bruikbaar n-wekelijks aantal.";

// Used in view_entry.php
$vocab["description"]        = "Omschrijving";
$vocab["room"]               = "Kamer";
$vocab["createdby"]          = "Aangemaakt door";
$vocab["lastupdate"]         = "Laatste aanpassing";
$vocab["deleteentry"]        = "Boeking verwijderen";
$vocab["deleteseries"]       = "Herhalingen verwijderen";
$vocab["confirmdel"]         = "Weet U zeker\\ndat U deze\\nBoeking wilt verwijderen?\\n\\n";
$vocab["returnprev"]         = "Terug naar vorige pagina";
$vocab["invalid_entry_id"]   = "Ongeldig Boeking-ID.";
$vocab["invalid_series_id"]  = "Ongeldig Herhalings-ID.";

// Used in edit_entry_handler.php
$vocab["error"]              = "Fout";
$vocab["sched_conflict"]     = "Overlappende Boeking";
$vocab["conflict"]           = "De nieuwe boeking overlapt de volgende boeking(en)";
$vocab["too_may_entrys"]     = "De door U geselecteerde opties zullen teveel boekingen genereren.<br>Pas A.U.B. uw opties aan !";
$vocab["returncal"]          = "Terug naar kalender overzicht";
$vocab["failed_to_acquire"]  = "Het is niet gelukt om exclusive toegang tot de database te verkrijgen"; 
$vocab["mail_subject_entry"] = $mail["subject"];
$vocab["mail_body_new_entry"] = $mail["new_entry"];
$vocab["mail_body_del_entry"] = $mail["deleted_entry"];
$vocab["mail_body_changed_entry"] = $mail["changed_entry"];
$vocab["mail_subject_delete"] = $mail["subject_delete"];

// Authentication stuff
$vocab["accessdenied"]       = "Geen Toegang";
$vocab["norights"]           = "U heeft geen rechten om deze boeking aan te passen.";
$vocab["please_login"]       = "Inloggen A.U.B";
$vocab["user_name"]          = "Naam";
$vocab["user_password"]      = "Wachtwoord";
$vocab["unknown_user"]       = "Onbekende gebruiker";
$vocab["you_are"]            = "U bent";
$vocab["login"]              = "Inloggen";
$vocab["logoff"]             = "Uitloggen";

// Authentication database
$vocab["user_list"]          = "Gebruikerslijst";
$vocab["edit_user"]          = "Gebruiker aanpassen";
$vocab["delete_user"]        = "Deze gebruiker verwijderen";
//$vocab["user_name"]         = Use the same as above, for consistency.
//$vocab["user_password"]     = Use the same as above, for consistency.
$vocab["user_email"]         = "Email adres";
$vocab["password_twice"]     = "Als u het wachtwoord wilt wijzigen dient u het nieuwe wachtwoord tweemaal in te voeren.";
$vocab["passwords_not_eq"]   = "Fout: De wachtwoorden komen niet overeen.";
$vocab["add_new_user"]       = "Nieuwe gebruiker toevoegen";
$vocab["rights"]             = "Rechten";
$vocab["action"]             = "Handelingen";
$vocab["user"]               = "Gebruiker";
$vocab["administrator"]      = "Beheerder";
$vocab["unknown"]            = "Onbekend";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Klikken om al mijn aankomende boekingen te tonen.";

// Used in search.php
$vocab["invalid_search"]     = "Niet bestaand of ongeldig zoek argument.";
$vocab["search_results"]     = "Zoek resultaten voor";
$vocab["nothing_found"]      = "Geen resultaten voor uw zoek opdracht gevonden.";
$vocab["records"]            = "Boekingregels ";
$vocab["through"]            = " tot en met ";
$vocab["of"]                 = " van ";
$vocab["previous"]           = "Vorige";
$vocab["next"]               = "Volgende";
$vocab["entry"]              = "Boeking";
$vocab["view"]               = "Overzicht";
$vocab["advanced_search"]    = "Advanced search";
$vocab["search_button"]      = "Zoek";
$vocab["search_for"]         = "Zoeken naar";
$vocab["from"]               = "Van";

// Used in report.php
$vocab["report_on"]          = "Boekingsoverzicht";
$vocab["report_start"]       = "Start datum overzicht";
$vocab["report_end"]         = "Eind datum overzicht";
$vocab["match_area"]         = "Gebied als";
$vocab["match_room"]         = "Kamer als";
$vocab["match_type"]         = "Match type";
$vocab["ctrl_click_type"]    = "Gebruik Control-Linker muis klik om meer dan 1 type te selekteren";
$vocab["match_entry"]        = "Korte omschrijving als";
$vocab["match_descr"]        = "Volledige omschrijving als";
$vocab["include"]            = "Neem mee";
$vocab["report_only"]        = "Alleen overzicht";
$vocab["summary_only"]       = "Alleen samenvatting";
$vocab["report_and_summary"] = "Overzicht en samenvatting";
$vocab["summarize_by"]       = "Samenvatten volgens";
$vocab["sum_by_descrip"]     = "Korte omschrijving";
$vocab["sum_by_creator"]     = "Boeker";
$vocab["entry_found"]        = "boeking gevonden";
$vocab["entries_found"]      = "boekingen gevonden";
$vocab["summary_header"]     = "Totaal aan (geboekte) uren";
$vocab["summary_header_per"] = "Summary of (Entries) Periods";
$vocab["total"]              = "Totaal";
$vocab["submitquery"]        = "Rapport uitvoeren";
$vocab["sort_rep"]           = "Rapport sorteren op";
$vocab["sort_rep_time"]      = "Start Datum/Tijd";
$vocab["rep_dsp"]            = "Weergeven in rapport";
$vocab["rep_dsp_dur"]        = "Duur";
$vocab["rep_dsp_end"]        = "Eind Tijd";

// Used in week.php
$vocab["weekbefore"]         = "Ga naar vorige week";
$vocab["weekafter"]          = "Ga naar volgende week";
$vocab["gotothisweek"]       = "Ga naar deze week";

// Used in month.php
$vocab["monthbefore"]        = "Ga naar vorige maand";
$vocab["monthafter"]         = "Ga naar volgende maand";
$vocab["gotothismonth"]      = "Ga naar deze maand";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Nog geen kamers gedefiniëerd voor dit gebouw";

// Used in admin.php
$vocab["edit"]               = "Wijzig";
$vocab["delete"]             = "Wis";
$vocab["rooms"]              = "Kamers";
$vocab["in"]                 = "in";
$vocab["noareas"]            = "Gebouwen";
$vocab["addarea"]            = "Gebouw toevoegen";
$vocab["name"]               = "Naam";
$vocab["noarea"]             = "Geen gebouw geselecteerd";
$vocab["browserlang"]        = "Uw browser is ingesteld op ";
$vocab["postbrowserlang"]    = "Nederlands.";
$vocab["addroom"]            = "Kamer toevoegen";
$vocab["capacity"]           = "Zitplaatsen";
$vocab["norooms"]            = "Geen Kamers.";
$vocab["administration"]     = "Beheer";

// Used in edit_area_room.php
$vocab["editarea"]           = "Gebouw Wijzigen";
$vocab["change"]             = "Wijzig";
$vocab["backadmin"]          = "Terug naar Beheer";
$vocab["editroomarea"]       = "Gebouw of Kamer wijzigen";
$vocab["editroom"]           = "Kamer wijzigen";
$vocab["update_room_failed"] = "Wijzigen kamer mislukt: ";
$vocab["error_room"]         = "Fout: kamer ";
$vocab["not_found"]          = " niet gevonden";
$vocab["update_area_failed"] = "Wijzigen gebouw mislukt: ";
$vocab["error_area"]         = "Fout: gebouw ";
$vocab["room_admin_email"]   = "Kamer beheer email";
$vocab["area_admin_email"]   = "Gebouw beheer email";
$vocab["invalid_email"]      = "Ongeldig email adres !";

// Used in del.php
$vocab["deletefollowing"]    = "U gaat hiermee de volgende boekingen verwijderen";
$vocab["sure"]               = "Weet U het zeker?";
$vocab["YES"]                = "JA";
$vocab["NO"]                 = "NEE";
$vocab["delarea"]            = "U moet alle kamers in dit gebouw verwijderen voordat U het kunt verwijderen<p>";

// Used in help.php
$vocab["about_mrbs"]         = "Over MRBS";
$vocab["database"]           = "Database";
$vocab["system"]             = "Systeem";
$vocab["please_contact"]     = "Neem contact op met ";
$vocab["for_any_questions"]  = "Voor alle vragen die hier niet worden beantwoord.";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Fatale Fout: Verbinding naar database server mislukt";

?>
