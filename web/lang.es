<?
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is a Spanish file.
#
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$lang["charset"]            = "iso-8859-1";

# Used in style.inc
$lang["mrbs"]               = "Sistema de Reservas de Salas de Reuni&oacute;n";

# Used in functions.inc
$lang["report"]             = "Informe";
$lang["admin"]              = "Admin";
$lang["help"]               = "Ayuda";
$lang["search"]             = "B&uacute;squeda:";

# Used in day.php3
$lang["bookingsfor"]        = "Reservas para el";
$lang["bookingsforpost"]    = ""; # Goes after the date
$lang["areas"]              = "Areas";
$lang["daybefore"]          = "D&iacute;a Anterior";
$lang["dayafter"]           = "D&iacute;a Siguiente";
$lang["gototoday"]          = "D&iacute;a Actual";
$lang["goto"]               = "Ir a";

# Used in trailer.inc
$lang["viewday"]            = "Ver D&iacute;a";
$lang["viewweek"]           = "View Week";
$lang["viewmonth"]          = "View Month";
$lang["ppreview"]           = "Print Preview";

# Used in edit_entry.php3
$lang["addentry"]           = "Nueva Reserva";
$lang["editentry"]          = "Editar Reserva";
$lang["editseries"]         = "Edit Series";
$lang["namebooker"]         = "Nombre Titular:";
$lang["fulldescription"]    = "Descripci&oacute;n Completa:<br>&nbsp;&nbsp;(N&uacute;mero de personas,<br>&nbsp;&nbsp;Interna/Externa etc)";
$lang["date"]               = "Fecha:";
$lang["start_date"]         = "Fecha Comienzo:";
$lang["end_date"]           = "Fecha FinalTime:";
$lang["time"]               = "Hora:";
$lang["duration"]           = "Duraci&oacute;n:";
$lang["seconds"]            = "segundos";
$lang["minutes"]            = "minutos";
$lang["hours"]              = "horas";
$lang["days"]               = "d&iacute;as";
$lang["weeks"]              = "semanas";
$lang["years"]              = "a&ntilde;os";
$lang["all_day"]            = "D&iacute;a completo";
$lang["type"]               = "Tipo:";
$lang["internal"]           = "Interna";
$lang["external"]           = "Externa";
$lang["save"]               = "Salvar";
$lang["rep_type"]           = "Tipo Repetici&oacute;n:";
$lang["rep_type_0"]         = "Ninguna";
$lang["rep_type_1"]         = "Diaria";
$lang["rep_type_2"]         = "Semanal";
$lang["rep_type_3"]         = "Mensual";
$lang["rep_type_4"]         = "Anual";
$lang["rep_type_5"]         = "Monthly, corresponding day";
$lang["rep_type_6"]         = "n-Weekly";
$lang["rep_end_date"]       = "Fecha tope Repetici&oacute;n:";
$lang["rep_rep_day"]        = "D&iacute;a Repetici&oacute;n:";
$lang["rep_for_weekly"]     = "(para semanal)";
$lang["rep_freq"]           = "Frecuencia:";
$lang["rep_num_weeks"]      = "Number of weeks";
$lang["rep_for_nweekly"]    = "(for n-weekly)";

# Used in view_entry.php3
$lang["description"]        = "Descripci&oacute;n:";
$lang["room"]               = "Sala:";
$lang["createdby"]          = "Creada Por:";
$lang["lastupdate"]         = "Ultima Actualizaci&oacute;n:";
$lang["deleteentry"]        = "Borrar Reserva";
$lang["deleteseries"]       = "Borrar Serie";
$lang["confirmdel"]         = "Seguro que\\ndesea borrar\\nesta reserva?\\n\\n";
$lang["returnprev"]         = "Volver a p&aacute;gina anterior";

# Used in edit_entry_handler.php3
$lang["error"]              = "Error";
$lang["sched_conflict"]     = "Conflicto de Planificaci&oacute;n";
$lang["conflict"]           = "La nueva reserva entra en conflicto con la(s) siguiente(s) entrada(s):";
$lang["too_may_entrys"]     = "Las opciones seleccionadas crear&aacute;n demasiadas entradas.<BR>Por favor, revise las opciones";
$lang["returncal"]          = "Volver a vista de calendario";

# Authentication stuff
$lang["accessdenied"]       = "Acceso Denegado";
$lang["norights"]           = "No tiene autorizacioacute;n para modificar este dato.";

# Used in search.php3
$lang["invalid_search"]     = "Cadena de b&uacute;squeda vac&iacute;a o incorrecta.";
$lang["search_results"]     = "Buscar resultados de:";
$lang["nothing_found"]      = "No se encontraron coincidencias.";
$lang["records"]            = "Registros ";
$lang["through"]            = " a trav&eacute;s ";
$lang["of"]                 = " de ";
$lang["previous"]           = "Anterior";
$lang["next"]               = "Siguiente";
$lang["entry"]              = "Entrada";
$lang["view"]               = "Ver";

# Used in report.php
$lang["report_on"]          = "Report on Meetings:";
$lang["report_start"]       = "Report start date:";
$lang["report_end"]         = "Report end date:";
$lang["match_area"]         = "Match area:";
$lang["match_room"]         = "Match room:";
$lang["match_entry"]        = "Match brief description:";
$lang["match_descr"]        = "Match full description:";
$lang["include"]            = "Include:";
$lang["report_only"]        = "Report only";
$lang["summary_only"]       = "Summary only";
$lang["report_and_summary"] = "Report and Summary";
$lang["summarize_by"]       = "Summarize by:";
$lang["sum_by_descrip"]     = "Brief description";
$lang["sum_by_creator"]     = "Creator";
$lang["entry_found"]        = "entry found";
$lang["entries_found"]      = "entries found";
$lang["summary_header"]     = "Summary of (Entries) Hours";
$lang["total"]              = "Total";
$lang["submitquery"]        = "Run Report";

# Used in week.php
$lang["weekbefore"]         = "Go To Week Before";
$lang["weekafter"]          = "Go To Week After";
$lang["gotothisweek"]       = "Go To This Week";

# Used in month.php
$lang["monthbefore"]        = "Go To Month Before";
$lang["monthafter"]         = "Go To Month After";
$lang["gotothismonth"]      = "Go To This Month";

# Used in {day week month}.php
$lang["no_rooms_for_area"]  = "No rooms defined for this area";

# Used in admin.php
$lang["edit"]               = "Edit";
$lang["delete"]             = "Delete";
$lang["rooms"]              = "Rooms";
$lang["in"]                 = "in";
$lang["noareas"]            = "No Areas";
$lang["addarea"]            = "Add Area";
$lang["name"]               = "Name";
$lang["noarea"]             = "No area selected";
$lang["browserlang"]        = "Your browser is set to use";
$lang["postbrowserlang"]    = "language.";
$lang["addroom"]            = "Add Room";
$lang["capacity"]           = "Capacity";
$lang["norooms"]            = "No rooms.";

# Used in edit_area_room.php
$lang["editarea"]           = "Edit Area";
$lang["change"]             = "Change";
$lang["backadmin"]          = "Back to Admin";
$lang["editroomarea"]       = "Edit Area or Room Description";
$lang["editroom"]           = "Edit Room";

# Used in del.php
$lang["deletefollowing"]    = "This will delete the following bookings";
$lang["sure"]               = "Are you sure?";
$lang["YES"]                = "YES";
$lang["NO"]                 = "NO";

?>
