<?
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is an NL Dutch file.
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$lang["charset"]            = "iso-8859-1";

# Used in style.inc
$lang["mrbs"]               = "Vergaderruimte Boekingssysteem";

# Used in functions.inc
$lang["search_label"]       = "Zoek:";
$lang["report"]             = "Rapportage";
$lang["admin"]              = "Admin";
$lang["help"]               = "Help";

# Used in day.php
$lang["bookingsfor"]        = "Boekingen voor";
$lang["bookingsforpost"]    = ""; # Goes after the date
$lang["areas"]              = "Gebouwen";
$lang["daybefore"]          = "Naar Vorige Dag";
$lang["dayafter"]           = "Naar Volgende Dag";
$lang["gototoday"]          = "Naar Vandaag";
$lang["goto"]               = "ga naar";

# Used in trailer.inc
$lang["viewday"]            = "Bekijk Dag";
$lang["viewweek"]           = "Bekijk Week";
$lang["viewmonth"]          = "Bekijk Maand";

# Used in edit_entry.php
$lang["addentry"]           = "Boeking Toevoegen";
$lang["editentry"]          = "Boeking Wijzigen";
$lang["editseries"]         = "Wijzig Reeks";
$lang["namebooker"]         = "Korte Omschrijving:";
// $lang["namebooker"]         = "Geboekt door:";
$lang["fulldescription"]    = "Volledige Omschrijving:<br>&nbsp;&nbsp;(Aantal mensen,<br>&nbsp;&nbsp;Intern/Extern etc)";
$lang["date"]               = "Datum:";
$lang["start_date"]         = "Start Tijd:";
$lang["end_date"]           = "Eind Tijd:";
$lang["time"]               = "Tijd:";
$lang["duration"]           = "Tijdsduur:";
$lang["seconds"]            = "seconden";
$lang["minutes"]            = "minuten";
$lang["hours"]              = "uren";
$lang["days"]               = "dagen";
$lang["weeks"]              = "weken";
$lang["years"]              = "jaren";
$lang["all_day"]            = "Hele Dag";
$lang["type"]               = "Soort:";
$lang["internal"]           = "Intern";
$lang["external"]           = "Extern";
$lang["save"]               = "Opslaan";
$lang["rep_type"]           = "Soort Herhaling:";
$lang["rep_type_0"]         = "Geen";
$lang["rep_type_1"]         = "Dagelijks";
$lang["rep_type_2"]         = "Wekelijks";
$lang["rep_type_3"]         = "Maandelijks";
$lang["rep_type_4"]         = "Jaarlijks";
$lang["rep_type_5"]         = "Maandelijks, Overeenkomstige dag";
$lang["rep_end_date"]       = "Einde herhaling datum:";
$lang["rep_rep_day"]        = "Herhalingsdag:";
$lang["rep_for_weekly"]     = "(t.b.v. wekelijks)";
$lang["rep_freq"]           = "Frequentie:";

# Used in view_entry.php
$lang["description"]        = "Omschrijving:";
$lang["room"]               = "Kamer:";
$lang["createdby"]          = "Aangemaakt door:";
$lang["lastupdate"]         = "Laatste aanpassing:";
$lang["deleteentry"]        = "Boeking verwijderen";
$lang["deleteseries"]       = "Herhalingen verwijderen";
$lang["confirmdel"]         = "Weet U zeker\\ndat U deze\\nBoeking wilt verwijderen?\\n\\n";
$lang["returnprev"]         = "Terug naar vorige pagina";

# Used in edit_entry_handler.php
$lang["error"]              = "Fout";
$lang["sched_conflict"]     = "Overlappende Boeking";
$lang["conflict"]           = "Er zijn overlappende boekingen";
$lang["too_may_entrys"]     = "De door U geselecteerde opties zullen teveel boekingen genereren.<BR>Pas A.U.B. uw opties aan !";
$lang["returncal"]          = "Terug naar kalender overzicht";

# Authentication stuff
$lang["accessdenied"]       = "Geen Toegang";
// $lang["unandpw"]            = "Voer A.U.B. uw gebruikersnaam en wachtwoord in.";
$lang["norights"]           = "U heeft geen rechten om deze boeking aan te passen.";

# Used in search.php
$lang["invalid_search"]     = "Niet bestaand of ongeldig zoek argument.";
$lang["search_results"]     = "Zoek resultaten voor:";
$lang["nothing_found"]      = "Geen resultaten voor uw zoek opdracht gevonden.";
$lang["records"]            = "Boekingregels ";
$lang["through"]            = " tot en met ";
$lang["of"]                 = " van ";
$lang["previous"]           = "Vorige";
$lang["next"]               = "Volgende";
$lang["entry"]              = "Boeking";
$lang["view"]               = "Overzicht";

# Used in report.php
$lang["report_on"]          = "Boekingsoverzicht:";
$lang["report_start"]       = "Start datum overzicht:";
$lang["report_end"]         = "Eind datum overzicht:";
$lang["match_area"]         = "Gebied als:";
$lang["match_room"]         = "Kamer als:";
$lang["match_entry"]        = "Korte omschrijving als:";
$lang["match_descr"]        = "Volledige omschrijving als:";
$lang["include"]            = "Neem mee:";
$lang["report_only"]        = "Alleen overzicht";
$lang["summary_only"]       = "Alleen samenvatting";
$lang["report_and_summary"] = "Overzicht en samenvatting";
$lang["summarize_by"]       = "Samenvatten volgens:";
$lang["sum_by_descrip"]     = "Korte omschrijving";
$lang["sum_by_creator"]     = "Boeker";
$lang["entry_found"]        = "boeking gevonden";
$lang["entries_found"]      = "boekingen gevonden";
$lang["summary_header"]     = "Totaal aan (geboekte) uren";
$lang["total"]              = "Totaal";

# Used in week.php
$lang["weekbefore"]         = "Ga naar vorige week";
$lang["weekafter"]          = "Ga naar volgende week";
$lang["gotothisweek"]       = "Ga naar deze week";

# Used in month.php
$lang["monthbefore"]        = "Ga naar vorige maand";
$lang["monthafter"]         = "Ga naar volgende maand";
$lang["gotothismonth"]      = "Ga naar deze maand";

# Used in {day week month}.php
$lang["no_rooms_for_area"]  = "Nog geen kamers gedefiniëerd voor dit gebouw";

?>
