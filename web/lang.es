<?
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is a Spanish file.
#
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

# Used in style.inc
$vocab["mrbs"]               = "Sistema de Reservas de Salas de Reuni&oacute;n";

# Used in functions.inc
$vocab["report"]             = "Informe";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Ayuda";
$vocab["search"]             = "B&uacute;squeda:";

# Used in day.php
$vocab["bookingsfor"]        = "Reservas para el";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Areas";
$vocab["daybefore"]          = "D&iacute;a Anterior";
$vocab["dayafter"]           = "D&iacute;a Siguiente";
$vocab["gototoday"]          = "D&iacute;a Actual";
$vocab["goto"]               = "Ir a";

# Used in trailer.inc
$vocab["viewday"]            = "Ver D&iacute;a";
$vocab["viewweek"]           = "View Week";
$vocab["viewmonth"]          = "View Month";
$vocab["ppreview"]           = "Print Preview";

# Used in edit_entry.php
$vocab["addentry"]           = "Nueva Reserva";
$vocab["editentry"]          = "Editar Reserva";
$vocab["editseries"]         = "Edit Series";
$vocab["namebooker"]         = "Nombre Titular:";
$vocab["fulldescription"]    = "Descripci&oacute;n Completa:<br>&nbsp;&nbsp;(N&uacute;mero de personas,<br>&nbsp;&nbsp;Interna/Externa etc)";
$vocab["date"]               = "Fecha:";
$vocab["start_date"]         = "Fecha Comienzo:";
$vocab["end_date"]           = "Fecha FinalTime:";
$vocab["time"]               = "Hora:";
$vocab["duration"]           = "Duraci&oacute;n:";
$vocab["seconds"]            = "segundos";
$vocab["minutes"]            = "minutos";
$vocab["hours"]              = "horas";
$vocab["days"]               = "d&iacute;as";
$vocab["weeks"]              = "semanas";
$vocab["years"]              = "a&ntilde;os";
$vocab["all_day"]            = "D&iacute;a completo";
$vocab["type"]               = "Tipo:";
$vocab["internal"]           = "Interna";
$vocab["external"]           = "Externa";
$vocab["save"]               = "Salvar";
$vocab["rep_type"]           = "Tipo Repetici&oacute;n:";
$vocab["rep_type_0"]         = "Ninguna";
$vocab["rep_type_1"]         = "Diaria";
$vocab["rep_type_2"]         = "Semanal";
$vocab["rep_type_3"]         = "Mensual";
$vocab["rep_type_4"]         = "Anual";
$vocab["rep_type_5"]         = "Monthly, corresponding day";
$vocab["rep_type_6"]         = "n-Weekly";
$vocab["rep_end_date"]       = "Fecha tope Repetici&oacute;n:";
$vocab["rep_rep_day"]        = "D&iacute;a Repetici&oacute;n:";
$vocab["rep_for_weekly"]     = "(para semanal)";
$vocab["rep_freq"]           = "Frecuencia:";
$vocab["rep_num_weeks"]      = "Number of weeks";
$vocab["rep_for_nweekly"]    = "(for n-weekly)";
$vocab["ctrl_click"]         = "Use Control-Click to select more than one room";

# Used in view_entry.php
$vocab["description"]        = "Descripci&oacute;n:";
$vocab["room"]               = "Sala:";
$vocab["createdby"]          = "Creada Por:";
$vocab["lastupdate"]         = "Ultima Actualizaci&oacute;n:";
$vocab["deleteentry"]        = "Borrar Reserva";
$vocab["deleteseries"]       = "Borrar Serie";
$vocab["confirmdel"]         = "Seguro que\\ndesea borrar\\nesta reserva?\\n\\n";
$vocab["returnprev"]         = "Volver a p&aacute;gina anterior";

# Used in edit_entry_handler.php
$vocab["error"]              = "Error";
$vocab["sched_conflict"]     = "Conflicto de Planificaci&oacute;n";
$vocab["conflict"]           = "La nueva reserva entra en conflicto con la(s) siguiente(s) entrada(s):";
$vocab["too_may_entrys"]     = "Las opciones seleccionadas crear&aacute;n demasiadas entradas.<BR>Por favor, revise las opciones";
$vocab["returncal"]          = "Volver a vista de calendario";

# Authentication stuff
$vocab["accessdenied"]       = "Acceso Denegado";
$vocab["norights"]           = "No tiene autorizacioacute;n para modificar este dato.";

# Used in search.php
$vocab["invalid_search"]     = "Cadena de b&uacute;squeda vac&iacute;a o incorrecta.";
$vocab["search_results"]     = "Buscar resultados de:";
$vocab["nothing_found"]      = "No se encontraron coincidencias.";
$vocab["records"]            = "Registros ";
$vocab["through"]            = " a trav&eacute;s ";
$vocab["of"]                 = " de ";
$vocab["previous"]           = "Anterior";
$vocab["next"]               = "Siguiente";
$vocab["entry"]              = "Entrada";
$vocab["view"]               = "Ver";

# Used in report.php
$vocab["report_on"]          = "Report on Meetings:";
$vocab["report_start"]       = "Report start date:";
$vocab["report_end"]         = "Report end date:";
$vocab["match_area"]         = "Match area:";
$vocab["match_room"]         = "Match room:";
$vocab["match_entry"]        = "Match brief description:";
$vocab["match_descr"]        = "Match full description:";
$vocab["include"]            = "Include:";
$vocab["report_only"]        = "Report only";
$vocab["summary_only"]       = "Summary only";
$vocab["report_and_summary"] = "Report and Summary";
$vocab["summarize_by"]       = "Summarize by:";
$vocab["sum_by_descrip"]     = "Brief description";
$vocab["sum_by_creator"]     = "Creator";
$vocab["entry_found"]        = "entry found";
$vocab["entries_found"]      = "entries found";
$vocab["summary_header"]     = "Summary of (Entries) Hours";
$vocab["total"]              = "Total";
$vocab["submitquery"]        = "Run Report";

# Used in week.php
$vocab["weekbefore"]         = "Go To Week Before";
$vocab["weekafter"]          = "Go To Week After";
$vocab["gotothisweek"]       = "Go To This Week";

# Used in month.php
$vocab["monthbefore"]        = "Go To Month Before";
$vocab["monthafter"]         = "Go To Month After";
$vocab["gotothismonth"]      = "Go To This Month";

# Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "No rooms defined for this area";

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

# Used in del.php
$vocab["deletefollowing"]    = "This will delete the following bookings";
$vocab["sure"]               = "Are you sure?";
$vocab["YES"]                = "YES";
$vocab["NO"]                 = "NO";

?>
