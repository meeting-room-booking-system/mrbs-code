<?
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is the Swedish file.
# Translated by Bo Kleve (bok@unit.liu.se)
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$lang["charset"]            = "iso-8859-1";

# Used in style.inc
$lang["mrbs"]               = "MRBS rumsbokningssystem";

# Used in functions.inc
$lang["search_label"]       = "Search:";
$lang["report"]             = "Report";
$lang["admin"]              = "Admin";
$lang["help"]               = "Help";

# Used in day.php3
$lang["bookingsfor"]        = "Bokningar för";
$lang["bookingsforpost"]    = ""; # Goes after the date
$lang["areas"]              = "Områden";
$lang["daybefore"]          = "Gå till föregående dag";
$lang["dayafter"]           = "Gå till nästa dag";
$lang["gototoday"]          = "Gå till idag";
$lang["goto"]               = "gå till";

# Used in trailer.inc
$lang["viewday"]            = "Visa dag";

# Used in edit_entry.php3
$lang["addentry"]           = "Gör bokning";
$lang["editentry"]          = "Ändra bokningen";
$lang["editseries"]         = "Ändra serie";
$lang["namebooker"]         = "Kort beskrivning:";
$lang["fulldescription"]    = "Full beskrivning:<br>&nbsp;&nbsp;(Antal personer,<br>&nbsp;&nbsp;Internt/Externt etc)";
$lang["date"]               = "Datum:";
$lang["start_date"]         = "Starttid:";
$lang["end_date"]           = "Sluttid:";
$lang["time"]               = "Tid:";
$lang["duration"]           = "Längd:";
$lang["seconds"]            = "sekunder";
$lang["minutes"]            = "minuter";
$lang["hours"]              = "timmar";
$lang["days"]               = "dagar";
$lang["weeks"]              = "veckor";
$lang["years"]              = "år";
$lang["all_day"]            = "hela dagen";
$lang["type"]               = "Typ:";
$lang["internal"]           = "Internt";
$lang["external"]           = "Externt";
$lang["save"]               = "Spara";
$lang["rep_type"]           = "Repetitionstyp:";
$lang["rep_type_0"]         = "ingen";
$lang["rep_type_1"]         = "dagligen";
$lang["rep_type_2"]         = "varje vecka";
$lang["rep_type_3"]         = "månatligen";
$lang["rep_type_4"]         = "årligen";
$lang["rep_end_date"]       = "Repetition slutdatum:";
$lang["rep_rep_day"]        = "Repetitionsdag:";
$lang["rep_for_weekly"]     = "(vid varje vecka)";
$lang["rep_freq"]           = "Frekvens:";

# Used in view_entry.php3
$lang["description"]        = "Beskrivning:";
$lang["room"]               = "Rum:";
$lang["createdby"]          = "Skapad av:";
$lang["lastupdate"]         = "Senast uppdaterad:";
$lang["deleteentry"]        = "Radera bokningen";
$lang["deleteseries"]       = "Radera serie";
$lang["confirmdel"]         = "Är du säker att\\ndu vill radera\\nden här bokningen?\\n\\n";
$lang["returnprev"]         = "Åter till föregående sida";

# Used in edit_entry_handler.php3
$lang["error"]              = "Fel";
$lang["sched_conflict"]     = "Bokningskonflikt";
$lang["conflict"]           = "Den nya bokningen krockar med följande bokning(ar):";
$lang["too_may_entrys"]     = "De valda inställningarna skapar för många bokningar.<BR>V.G. använd andra inställningar!";
$lang["returncal"]          = "Återgå till kalendervy";

# Authentication stuff
$lang["accessdenied"]       = "Åtkomst nekad";
$lang["norights"]           = "Du har inte rättighet att ändra bokningen.";

# Used in search.php3
$lang["invalid_search"]     = "Tom eller ogiltig söksträng.";
$lang["search_results"]     = "Sökresultat för:";
$lang["nothing_found"]      = "Inga matchande träffar hittade.";
$lang["records"]            = "Bokning ";
$lang["through"]            = " t.o.m. ";
$lang["of"]                 = " av ";
$lang["previous"]           = "Föregående";
$lang["next"]               = "Nästa";
$lang["entry"]              = "Post";
$lang["view"]               = "Visa";

?>
