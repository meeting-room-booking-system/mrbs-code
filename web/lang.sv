<?php
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is the Swedish file.
#
# Translated provede by: Bo Kleve (bok@unit.liu.se)
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

# Used in style.inc
$vocab["mrbs"]               = "MRBS rumsbokningssystem";

# Used in functions.inc
$vocab["report"]             = "Rapport";
$vocab["admin"]              = "Administration";
$vocab["help"]               = "Hjälp";
$vocab["search"]             = "Sök:";
$vocab["not_php3"]             = "<H1>WARNING: This probably doesn't work with PHP3</H1>";

# Used in day.php
$vocab["bookingsfor"]        = "Bokningar för";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Områden";
$vocab["daybefore"]          = "Gå till föregående dag";
$vocab["dayafter"]           = "Gå till nästa dag";
$vocab["gototoday"]          = "Gå till idag";
$vocab["goto"]               = "gå till";

# Used in trailer.inc
$vocab["viewday"]            = "Visa dag";
$vocab["viewweek"]           = "Visa vecka";
$vocab["viewmonth"]          = "Visa Månad";
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
$vocab["duration"]           = "Längd:";
$vocab["seconds"]            = "sekunder";
$vocab["minutes"]            = "minuter";
$vocab["hours"]              = "timmar";
$vocab["days"]               = "dagar";
$vocab["weeks"]              = "veckor";
$vocab["years"]              = "år";
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
$vocab["ctrl_click"]         = "Use Control-Click to select more than one room";
$vocab["entryid"]            = "Entry ID ";
$vocab["repeat_id"]          = "Repeat ID "; 
$vocab["you_have_not_entered"] = "You have not entered a";
$vocab["valid_time_of_day"]  = "valid time of day.";
$vocab["brief_description"]  = "Brief Description.";
$vocab["useful_n-weekly_value"] = "useful n-weekly value.";

# Used in view_entry.php
$vocab["description"]        = "Beskrivning:";
$vocab["room"]               = "Rum:";
$vocab["createdby"]          = "Skapad av:";
$vocab["lastupdate"]         = "Senast uppdaterad:";
$vocab["deleteentry"]        = "Radera bokningen";
$vocab["deleteseries"]       = "Radera serie";
$vocab["confirmdel"]         = "Är du säker att\\ndu vill radera\\nden här bokningen?\\n\\n";
$vocab["returnprev"]         = "Åter till föregående sida";
$vocab["invalid_entry_id"]   = "Invalid entry id.";

# Used in edit_entry_handler.php
$vocab["error"]              = "Fel";
$vocab["sched_conflict"]     = "Bokningskonflikt";
$vocab["conflict"]           = "Den nya bokningen krockar med följande bokning(ar):";
$vocab["too_may_entrys"]     = "De valda inställningarna skapar för många bokningar.<BR>V.G. använd andra inställningar!";
$vocab["returncal"]          = "Återgå till kalendervy";
$vocab["failed_to_acquire"]  = "Failed to acquire exclusive database access"; 

# Authentication stuff
$vocab["accessdenied"]       = "Åtkomst nekad";
$vocab["norights"]           = "Du har inte rättighet att ändra bokningen.";

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
$vocab["advanced_search"]    = "Advanced search";
$vocab["search_button"]      = "Sök";
$vocab["search_for"]         = "Search For";
$vocab["from"]               = "From";

# Used in report.php
$vocab["report_on"]          = "Rapport över Möten:";
$vocab["report_start"]       = "Rapport start datum:";
$vocab["report_end"]         = "Rapport slut datum:";
$vocab["match_area"]         = "Sök på plats:";
$vocab["match_room"]         = "Sök på rum:";
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
$vocab["total"]              = "Total";
$vocab["submitquery"]        = "Run Report";

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
$vocab["administration"]     = "Administration";

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

# Used in help.php
$vocab["about_mrbs"]         = "About MRBS";
$vocab["database"]           = "Database: ";
$vocab["system"]             = "System: ";
$vocab["please_contact"]     = "Please contact ";
$vocab["for_any_questions"]  = "for any questions that aren't answered here.";

# Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Fatal Error: Failed to connect to database";

?>
