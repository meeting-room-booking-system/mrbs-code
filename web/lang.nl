<?php
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is an NL Dutch file.
#
# Translations provided by: Marc ter Horst
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

# Used in style.inc
$vocab["mrbs"]               = "Vergaderruimte Boekingssysteem";

# Used in functions.inc
$vocab["report"]             = "Rapportage";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Help";
$vocab["search"]             = "Zoek:";
$vocab["not_php3"]             = "<H1>WARNING: This probably doesn't work with PHP3</H1>";

# Used in day.php
$vocab["bookingsfor"]        = "Boekingen voor";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Gebouwen";
$vocab["daybefore"]          = "Naar Vorige Dag";
$vocab["dayafter"]           = "Naar Volgende Dag";
$vocab["gototoday"]          = "Naar Vandaag";
$vocab["goto"]               = "ga naar";

# Used in trailer.inc
$vocab["viewday"]            = "Bekijk Dag";
$vocab["viewweek"]           = "Bekijk Week";
$vocab["viewmonth"]          = "Bekijk Maand";
$vocab["ppreview"]           = "Afdruk Voorbeeld";

# Used in edit_entry.php
$vocab["addentry"]           = "Boeking Toevoegen";
$vocab["editentry"]          = "Boeking Wijzigen";
$vocab["editseries"]         = "Wijzig Reeks";
$vocab["namebooker"]         = "Korte Omschrijving:";
$vocab["fulldescription"]    = "Volledige Omschrijving:<br>&nbsp;&nbsp;(Aantal mensen,<br>&nbsp;&nbsp;Intern/Extern etc)";
$vocab["date"]               = "Datum:";
$vocab["start_date"]         = "Start Tijd:";
$vocab["end_date"]           = "Eind Tijd:";
$vocab["time"]               = "Tijd:";
$vocab["duration"]           = "Tijdsduur:";
$vocab["seconds"]            = "seconden";
$vocab["minutes"]            = "minuten";
$vocab["hours"]              = "uren";
$vocab["days"]               = "dagen";
$vocab["weeks"]              = "weken";
$vocab["years"]              = "jaren";
$vocab["all_day"]            = "Hele Dag";
$vocab["type"]               = "Soort:";
$vocab["internal"]           = "Intern";
$vocab["external"]           = "Extern";
$vocab["save"]               = "Opslaan";
$vocab["rep_type"]           = "Soort Herhaling:";
$vocab["rep_type_0"]         = "Geen";
$vocab["rep_type_1"]         = "Dagelijks";
$vocab["rep_type_2"]         = "Wekelijks";
$vocab["rep_type_3"]         = "Maandelijks";
$vocab["rep_type_4"]         = "Jaarlijks";
$vocab["rep_type_5"]         = "Maandelijks, Overeenkomstige dag";
$vocab["rep_type_6"]         = "n-wekelijks";
$vocab["rep_end_date"]       = "Einde herhaling datum:";
$vocab["rep_rep_day"]        = "Herhalingsdag:";
$vocab["rep_for_weekly"]     = "(t.b.v. wekelijks)";
$vocab["rep_freq"]           = "Frequentie:";
$vocab["rep_num_weeks"]      = "Aantal weken";
$vocab["rep_for_nweekly"]    = "(Voor n-wekelijks)";
$vocab["ctrl_click"]         = "Use Control-Click to select more than one room";
$vocab["entryid"]            = "Entry ID ";
$vocab["repeat_id"]          = "Repeat ID "; 
$vocab["you_have_not_entered"] = "You have not entered a";
$vocab["valid_time_of_day"]  = "valid time of day.";
$vocab["brief_description"]  = "Brief Description.";
$vocab["useful_n-weekly_value"] = "useful n-weekly value.";

# Used in view_entry.php
$vocab["description"]        = "Omschrijving:";
$vocab["room"]               = "Kamer:";
$vocab["createdby"]          = "Aangemaakt door:";
$vocab["lastupdate"]         = "Laatste aanpassing:";
$vocab["deleteentry"]        = "Boeking verwijderen";
$vocab["deleteseries"]       = "Herhalingen verwijderen";
$vocab["confirmdel"]         = "Weet U zeker\\ndat U deze\\nBoeking wilt verwijderen?\\n\\n";
$vocab["returnprev"]         = "Terug naar vorige pagina";
$vocab["invalid_entry_id"]   = "Invalid entry id.";

# Used in edit_entry_handler.php
$vocab["error"]              = "Fout";
$vocab["sched_conflict"]     = "Overlappende Boeking";
$vocab["conflict"]           = "Er zijn overlappende boekingen";
$vocab["too_may_entrys"]     = "De door U geselecteerde opties zullen teveel boekingen genereren.<BR>Pas A.U.B. uw opties aan !";
$vocab["returncal"]          = "Terug naar kalender overzicht";
$vocab["failed_to_acquire"]  = "Failed to acquire exclusive database access"; 

# Authentication stuff
$vocab["accessdenied"]       = "Geen Toegang";
$vocab["norights"]           = "U heeft geen rechten om deze boeking aan te passen.";

# Used in search.php
$vocab["invalid_search"]     = "Niet bestaand of ongeldig zoek argument.";
$vocab["search_results"]     = "Zoek resultaten voor:";
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
$vocab["search_for"]         = "Search For";
$vocab["from"]               = "From";

# Used in report.php
$vocab["report_on"]          = "Boekingsoverzicht:";
$vocab["report_start"]       = "Start datum overzicht:";
$vocab["report_end"]         = "Eind datum overzicht:";
$vocab["match_area"]         = "Gebied als:";
$vocab["match_room"]         = "Kamer als:";
$vocab["match_entry"]        = "Korte omschrijving als:";
$vocab["match_descr"]        = "Volledige omschrijving als:";
$vocab["include"]            = "Neem mee:";
$vocab["report_only"]        = "Alleen overzicht";
$vocab["summary_only"]       = "Alleen samenvatting";
$vocab["report_and_summary"] = "Overzicht en samenvatting";
$vocab["summarize_by"]       = "Samenvatten volgens:";
$vocab["sum_by_descrip"]     = "Korte omschrijving";
$vocab["sum_by_creator"]     = "Boeker";
$vocab["entry_found"]        = "boeking gevonden";
$vocab["entries_found"]      = "boekingen gevonden";
$vocab["summary_header"]     = "Totaal aan (geboekte) uren";
$vocab["total"]              = "Totaal";
$vocab["submitquery"]        = "Run Report";

# Used in week.php
$vocab["weekbefore"]         = "Ga naar vorige week";
$vocab["weekafter"]          = "Ga naar volgende week";
$vocab["gotothisweek"]       = "Ga naar deze week";

# Used in month.php
$vocab["monthbefore"]        = "Ga naar vorige maand";
$vocab["monthafter"]         = "Ga naar volgende maand";
$vocab["gotothismonth"]      = "Ga naar deze maand";

# Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Nog geen kamers gedefiniëerd voor dit gebouw";

# Used in admin.php
$vocab["edit"]               = "Edit";
$vocab["delete"]             = "Delete";
$vocab["rooms"]              = "Rooms";
$vocab["in"]                 = "in";
$vocab["noareas"]            = "No Areas";
$vocab["addarea"]            = "Add Area";
$vocab["name"]               = "Name";
$vocab["noarea"]             = "No area selected";
$vocab["browserlang"]        = "Your browser is set to use";
$vocab["postbrowserlang"]    = "language.";
$vocab["addroom"]            = "Add Room";
$vocab["capacity"]           = "Capacity";
$vocab["norooms"]            = "No rooms.";

# Used in edit_area_room.php
$vocab["editarea"]           = "Edit Area";
$vocab["change"]             = "Change";
$vocab["backadmin"]          = "Back to Admin";
$vocab["editroomarea"]       = "Edit Area or Room Description";
$vocab["editroom"]           = "Edit Room";
$vocab["update_room_failed"] = "Update room failed: ";
$vocab["error_room"]         = "Error: room ";
$vocab["not_found"]          = " not found";
$vocab["update_area_failed"] = "Update area failed: ";
$vocab["error_area"]         = "Error: area ";

# Used in del.php
$vocab["deletefollowing"]    = "This will delete the following bookings";
$vocab["sure"]               = "Are you sure?";
$vocab["YES"]                = "YES";
$vocab["NO"]                 = "NO";
$vocab["delarea"]            = "You must delete all rooms in this area before you can delete it<p>";
$vocab["backadmin"]          = "Go back to Admin page";

# Used in help.php
$vocab["about_mrbs"]         = "About MRBS";
$vocab["database"]           = "Database: ";
$vocab["system"]             = "System: ";
$vocab["please_contact"]     = "Please contact ";
$vocab["for_any_questions"]  = "for any questions that aren't answered here.";

# Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Fatal Error: Failed to connect to database";

?>
