<?php
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is the Swedish file.
#
# Translated provede by: Bo Kleve (bok@unit.liu.se), MissterX
#
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

# Used in style.inc
$vocab["mrbs"]               = "MRBS - MötesRums BokningsSystem";

# Used in functions.inc
$vocab["report"]             = "Rapport";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Hjälp";
$vocab["search"]             = "Sök:";
$vocab["not_php3"]             = "<H1>VARNING: Detta fungerar förmodligen inte med PHP3</H1>";

# Used in day.php
$vocab["bookingsfor"]        = "Bokningar för";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Områden";
$vocab["daybefore"]          = "Gå till föregående dag";
$vocab["dayafter"]           = "Gå till nästa dag";
$vocab["gototoday"]          = "Gå till idag";
$vocab["goto"]               = "gå till";
$vocab["highlight_line"]     = "Highlight this line";
$vocab["click_to_reserve"]   = "Click on the cell to make a reservation.";

# Used in trailer.inc
$vocab["viewday"]            = "Visa dag";
$vocab["viewweek"]           = "Visa vecka";
$vocab["viewmonth"]          = "Visa månad";
$vocab["ppreview"]           = "Förhandsgranska";

# Used in edit_entry.php
$vocab["addentry"]           = "Boka !";
$vocab["editentry"]          = "Ändra bokningen";
$vocab["editseries"]         = "Ändra serie";
$vocab["namebooker"]         = "Kort beskrivning:";
$vocab["fulldescription"]    = "Full beskrivning:<br>&nbsp;&nbsp;(Antal personer,<br>&nbsp;&nbsp;Internt/Externt etc)";
$vocab["date"]               = "Datum:";
$vocab["start_date"]         = "Starttid:";
$vocab["end_date"]           = "Sluttid:";
$vocab["time"]               = "Tid:";
$vocab["period"]             = "Period:";
$vocab["duration"]           = "Längd:";
$vocab["seconds"]            = "sekunder";
$vocab["minutes"]            = "minuter";
$vocab["hours"]              = "timmar";
$vocab["days"]               = "dagar";
$vocab["weeks"]              = "veckor";
$vocab["years"]              = "år";
$vocab["periods"]            = "periods";
$vocab["all_day"]            = "hela dagen";
$vocab["type"]               = "Typ:";
$vocab["internal"]           = "Internt";
$vocab["external"]           = "Externt";
$vocab["save"]               = "Spara";
$vocab["rep_type"]           = "Repetitionstyp:";
$vocab["rep_type_0"]         = "ingen";
$vocab["rep_type_1"]         = "dagligen";
$vocab["rep_type_2"]         = "varje vecka";
$vocab["rep_type_3"]         = "månatligen";
$vocab["rep_type_4"]         = "årligen";
$vocab["rep_type_5"]         = "Månadsvis, samma dag";
$vocab["rep_type_6"]         = "Vecko vis";
$vocab["rep_end_date"]       = "Repetition slutdatum:";
$vocab["rep_rep_day"]        = "Repetitionsdag:";
$vocab["rep_for_weekly"]     = "(vid varje vecka)";
$vocab["rep_freq"]           = "Frekvens:";
$vocab["rep_num_weeks"]      = "Antal veckor";
$vocab["rep_for_nweekly"]    = "(För x-veckor)";
$vocab["ctrl_click"]         = "Kontroll-Klicka för att markera mer än ett rum";
$vocab["entryid"]            = "Antecknings ID ";
$vocab["repeat_id"]          = "Repetions ID "; 
$vocab["you_have_not_entered"] = "Du har inte angivit ";
$vocab["you_have_not_selected"] = "You have not selected a";
$vocab["valid_room"]         = "room.";
$vocab["valid_time_of_day"]  = "giltig tidpunkt på dage.";
$vocab["brief_description"]  = "Kort beskrivningBrief Description.";
$vocab["useful_n-weekly_value"] = "användbar n-veckovist värde.";

# Used in view_entry.php
$vocab["description"]        = "Beskrivning:";
$vocab["room"]               = "Rum:";
$vocab["createdby"]          = "Skapad av:";
$vocab["lastupdate"]         = "Senast uppdaterad:";
$vocab["deleteentry"]        = "Radera bokningen";
$vocab["deleteseries"]       = "Radera serie";
$vocab["confirmdel"]         = "Är du säker att\\ndu vill radera\\nden här bokningen?\\n\\n";
$vocab["returnprev"]         = "Åter till föregående sida";
$vocab["invalid_entry_id"]   = "Ogiltigt antecknings ID.";

# Used in edit_entry_handler.php
$vocab["error"]              = "Fel";
$vocab["sched_conflict"]     = "Bokningskonflikt";
$vocab["conflict"]           = "Den nya bokningen krockar med följande bokning(ar):";
$vocab["too_may_entrys"]     = "De valda inställningarna skapar för många bokningar.<BR>V.G. använd andra inställningar!";
$vocab["returncal"]          = "Återgå till kalendervy";
$vocab["failed_to_acquire"]  = "Kunde ej få exclusiv databas åtkomst"; 
$vocab["mail_subject_entry"] = $mail["subject"];
$vocab["mail_body_new_entry"] = $mail["new_entry"];
$vocab["mail_body_del_entry"] = $mail["deleted_entry"];
$vocab["mail_body_changed_entry"] = $mail["changed_entry"];
$vocab["mail_subject_delete"] = $mail["subject_delete"];

# Authentication stuff
$vocab["accessdenied"]       = "Åtkomst nekad";
$vocab["norights"]           = "Du har inte rättighet att ändra bokningen.";
$vocab["please_login"]       = "Please log in";
$vocab["user_name"]          = "Name";
$vocab["user_password"]      = "Password";
$vocab["unknown_user"]       = "Unknown user";
$vocab["you_are"]            = "You are";
$vocab["login"]              = "Log in";
$vocab["logoff"]             = "Log Off";

# Authentication database
$vocab["user_list"]          = "User list";
$vocab["edit_user"]          = "Edit user";
$vocab["delete_user"]        = "Delete this user";
#$vocab["user_name"]         = Use the same as above, for consistency.
#$vocab["user_password"]     = Use the same as above, for consistency.
$vocab["user_email"]         = "Email address";
$vocab["password_twice"]     = "If you wish to change the password, please type the new password twice";
$vocab["passwords_not_eq"]   = "Error: The passwords do not match.";
$vocab["add_new_user"]       = "Add a new user";
$vocab["rights"]             = "Rights";
$vocab["action"]             = "Action";
$vocab["user"]               = "User";
$vocab["administrator"]      = "Administrator";
$vocab["unknown"]            = "Unknown";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Click to display all my upcoming entries";

# Used in search.php
$vocab["invalid_search"]     = "Tom eller ogiltig söksträng.";
$vocab["search_results"]     = "Sökresultat för:";
$vocab["nothing_found"]      = "Inga matchande träffar hittade.";
$vocab["records"]            = "Bokning ";
$vocab["through"]            = " t.o.m. ";
$vocab["of"]                 = " av ";
$vocab["previous"]           = "Föregående";
$vocab["next"]               = "Nästa";
$vocab["entry"]              = "Post";
$vocab["view"]               = "Visa";
$vocab["advanced_search"]    = "Avancerad sökning";
$vocab["search_button"]      = "Sök";
$vocab["search_for"]         = "Sök för";
$vocab["from"]               = "Från";

# Used in report.php
$vocab["report_on"]          = "Rapport över Möten:";
$vocab["report_start"]       = "Rapport start datum:";
$vocab["report_end"]         = "Rapport slut datum:";
$vocab["match_area"]         = "Sök på plats:";
$vocab["match_room"]         = "Sök på rum:";
$vocab["match_type"]         = "Match type:";
$vocab["ctrl_click_type"]    = "Use Control-Click to select more than one type";
$vocab["match_entry"]        = "Sök på kort beskrivning:";
$vocab["match_descr"]        = "Sök på  full beskrivning:";
$vocab["include"]            = "Inkludera:";
$vocab["report_only"]        = "Rapport  enbart";
$vocab["summary_only"]       = "Sammanställning endast";
$vocab["report_and_summary"] = "Rapport och Sammanställning";
$vocab["summarize_by"]       = "Sammanställ på:";
$vocab["sum_by_descrip"]     = "Kort beskrivning";
$vocab["sum_by_creator"]     = "Skapare";
$vocab["entry_found"]        = "Post hittad";
$vocab["entries_found"]      = "Poster hittade";
$vocab["summary_header"]     = "Sammanställning över (Poster) Timmar";
$vocab["summary_header_per"] = "Summary of (Entries) Periods";
$vocab["total"]              = "Total";
$vocab["submitquery"]        = "Run Report";
$vocab["sort_rep"]           = "Sort Report by:";
$vocab["sort_rep_time"]      = "Start Date/Time";
$vocab["rep_dsp"]            = "Display in report:";
$vocab["rep_dsp_dur"]        = "Duration";
$vocab["rep_dsp_end"]        = "End Time";

# Used in week.php
$vocab["weekbefore"]         = "Föregående vecka";
$vocab["weekafter"]          = "Nästa vecka";
$vocab["gotothisweek"]       = "Denna vecka";

# Used in month.php
$vocab["monthbefore"]        = "Föregående månad";
$vocab["monthafter"]         = "Nästa månad";
$vocab["gotothismonth"]      = "Denna månad";

# Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Rum saknas för denna plats";

# Used in admin.php
$vocab["edit"]               = "Ändra";
$vocab["delete"]             = "Radera";
$vocab["rooms"]              = "Rum";
$vocab["in"]                 = "i";
$vocab["noareas"]            = "Inget område";
$vocab["addarea"]            = "Lägg till område";
$vocab["name"]               = "Namn";
$vocab["noarea"]             = "Inget område valt";
$vocab["browserlang"]        = "Din läsare är inställ att använda";
$vocab["postbrowserlang"]    = "språk.";
$vocab["addroom"]            = "Lägg till rum";
$vocab["capacity"]           = "Kapacitet";
$vocab["norooms"]            = "Inga rum.";
$vocab["administration"]     = "Administration";

# Used in edit_area_room.php
$vocab["editarea"]           = "Ändra område";
$vocab["change"]             = "Ändra";
$vocab["backadmin"]          = "Tillbaka till Administration";
$vocab["editroomarea"]       = "Ändra område eller rum beskrivning";
$vocab["editroom"]           = "Ändra rum";
$vocab["update_room_failed"] = "Uppdatering av misslyckades: ";
$vocab["error_room"]         = "Fel: rum ";
$vocab["not_found"]          = " ej hittat";
$vocab["update_area_failed"] = "Uppdatering av område misslyckades: ";
$vocab["error_area"]         = "Fel: område";
$vocab["room_admin_email"]   = "Room admin email:";
$vocab["area_admin_email"]   = "Area admin email:";
$vocab["invalid_email"]      = "Invalid email!";

# Used in del.php
$vocab["deletefollowing"]    = "Detta raderar följande bokningar";
$vocab["sure"]               = "Är du säker?";
$vocab["YES"]                = "JA";
$vocab["NO"]                 = "NEJ";
$vocab["delarea"]            = "Du måste ta bort alla rum i detta område innan du kan ta bort det<p>";
$vocab["backadmin"]          = "Tillbaka till Adminsidan";

# Used in help.php
$vocab["about_mrbs"]         = "Om MRBS";
$vocab["database"]           = "Databas: ";
$vocab["system"]             = "System: ";
$vocab["please_contact"]     = "Var vänlig kontakta ";
$vocab["for_any_questions"]  = "för eventuella frågor som ej är besvarade här.";

# Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Fatal Error: Kunde ej komma i kontakt med database.";

?>
