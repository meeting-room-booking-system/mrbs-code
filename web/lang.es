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
$vocab["report"]             = "Reportes";
$vocab["admin"]              = "Administraci&oacute;n";
$vocab["help"]               = "Ayuda";
$vocab["search"]             = "B&uacute;squeda:";
$vocab["not_php3"]             = "<H1>WARNING: This probably doesn't work with PHP3</H1>";

# Used in day.php
$vocab["bookingsfor"]        = "Reservas para el";
$vocab["bookingsforpost"]    = ""; # Goes after the date
$vocab["areas"]              = "Edificios";
$vocab["daybefore"]          = "D&iacute;a Anterior";
$vocab["dayafter"]           = "D&iacute;a Siguiente";
$vocab["gototoday"]          = "D&iacute;a Actual";
$vocab["goto"]               = "Ir a";
$vocab["highlight_line"]     = "Highlight this line";

# Used in trailer.inc
$vocab["viewday"]            = "Ver D&iacute;a";
$vocab["viewweek"]           = "Ver Semana";
$vocab["viewmonth"]          = "Ver Mes";
$vocab["ppreview"]           = "Print Preview";

# Used in edit_entry.php
$vocab["addentry"]           = "Nueva Reserva";
$vocab["editentry"]          = "Editar Reserva";
$vocab["editseries"]         = "Edit Series";
$vocab["namebooker"]         = "Nombre Titular:";
$vocab["fulldescription"]    = "Descripci&oacute;n Completa:<br>&nbsp;&nbsp;(N&uacute;mero de personas,<br>&nbsp;&nbsp;Interna/Externa etc)";
$vocab["date"]               = "Fecha:";
$vocab["start_date"]         = "Fecha Comienzo:";
$vocab["end_date"]           = "Fecha Final:";
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
$vocab["rep_num_weeks"]      = "N&uacute;mero de semanas";
$vocab["rep_for_nweekly"]    = "(n-semanas)";
$vocab["ctrl_click"]         = "Use Control-Click to select more than one room";
$vocab["entryid"]            = "Entry ID ";
$vocab["repeat_id"]          = "Repeat ID "; 
$vocab["you_have_not_entered"] = "You have not entered a";
$vocab["valid_time_of_day"]  = "valid time of day.";
$vocab["brief_description"]  = "Brief Description.";
$vocab["useful_n-weekly_value"] = "useful n-weekly value.";

# Used in view_entry.php
$vocab["description"]        = "Descripci&oacute;n:";
$vocab["room"]               = "Sala:";
$vocab["createdby"]          = "Creada Por:";
$vocab["lastupdate"]         = "Ultima Actualizaci&oacute;n:";
$vocab["deleteentry"]        = "Borrar Reserva";
$vocab["deleteseries"]       = "Borrar Serie";
$vocab["confirmdel"]         = "Seguro que\\ndesea borrar\\nesta reserva?\\n\\n";
$vocab["returnprev"]         = "Volver a p&aacute;gina anterior";
$vocab["invalid_entry_id"]   = "Invalid entry id.";

# Used in edit_entry_handler.php
$vocab["error"]              = "Error";
$vocab["sched_conflict"]     = "Conflicto de Planificaci&oacute;n";
$vocab["conflict"]           = "La nueva reserva entra en conflicto con la(s) siguiente(s) entrada(s):";
$vocab["too_may_entrys"]     = "Las opciones seleccionadas crear&aacute;n demasiadas entradas.<BR>Por favor, revise las opciones";
$vocab["returncal"]          = "Volver a vista de calendario";
$vocab["failed_to_acquire"]  = "Failed to acquire exclusive database access"; 

# Authentication stuff
$vocab["accessdenied"]       = "Acceso Denegado";
$vocab["norights"]           = "No tiene autorizaci&oacute;n para modificar este dato.";
$vocab["please_login"]       = "Please log in";
$vocab["user_name"]          = "Name";
$vocab["user_password"]      = "Password";
$vocab["unknown_user"]       = "Unknown user";
$vocab["you_are"]            = "You are";
$vocab["login"]              = "Log in";
$vocab["logoff"]             = "Log Off";

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
$vocab["advanced_search"]    = "Advanced search";
$vocab["search_button"]      = "B&uacute;squeda";
$vocab["search_for"]         = "Search For";
$vocab["from"]               = "From";

# Used in report.php
$vocab["report_on"]          = "Reporte de Reuniones:";
$vocab["report_start"]       = "Fecha desde:";
$vocab["report_end"]         = "Fecha hasta:";
$vocab["match_area"]         = "Encontrar edificio:";
$vocab["match_room"]         = "Encontar sala:";
$vocab["match_entry"]        = "Encontar descripci&oacute;n breve:";
$vocab["match_descr"]        = "Encontar descripci&oacute;n completa:";
$vocab["include"]            = "Incluir:";
$vocab["report_only"]        = "Solamente Reporte";
$vocab["summary_only"]       = "Solamente Resumen";
$vocab["report_and_summary"] = "Reporte y Resumen";
$vocab["summarize_by"]       = "Resumir por:";
$vocab["sum_by_descrip"]     = "Descripci&oacute;n breve";
$vocab["sum_by_creator"]     = "Creador";
$vocab["entry_found"]        = "registro encontrado";
$vocab["entries_found"]      = "registros encontrados";
$vocab["summary_header"]     = "Resumen de (Registros) Horas";
$vocab["total"]              = "Total";
$vocab["submitquery"]        = "Correr Reporte";

# Used in week.php
$vocab["weekbefore"]         = "Ir a Semana Anterior";
$vocab["weekafter"]          = "Ir a Semana Posteriorl";
$vocab["gotothisweek"]       = "Ir a Semana Corriente";

# Used in month.php
$vocab["monthbefore"]        = "Ir a Mes Anterior";
$vocab["monthafter"]         = "Ir a Mes Posterior";
$vocab["gotothismonth"]      = "Ir a Mes Corriente";

# Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "No hay salas definidas para este edificio";

# Used in admin.php
$vocab["edit"]               = "Editar";
$vocab["delete"]             = "Borrar";
$vocab["rooms"]              = "Salas";
$vocab["in"]                 = "en";
$vocab["noareas"]            = "No hay Edificios";
$vocab["addarea"]            = "Agregar Edificio";
$vocab["name"]               = "Nombre";
$vocab["noarea"]             = "No se seleccion&oacute; edificio";
$vocab["browserlang"]        = "Su visor esta configurado para usar lenguajes:";
$vocab["postbrowserlang"]    = ".";
$vocab["addroom"]            = "Agregar sala";
$vocab["capacity"]           = "Capacidad (personas)";
$vocab["norooms"]            = "No hay salas.";
$vocab["administration"]     = "Administration";

# Used in edit_area_room.php
$vocab["editarea"]           = "Editar Edificio";
$vocab["change"]             = "Cambiar";
$vocab["backadmin"]          = "Volver a Admin";
$vocab["editroomarea"]       = "Editar Descripci&oacute;n de Edificio o Sala";
$vocab["editroom"]           = "Editar Sala";
$vocab["update_room_failed"] = "Update room failed: ";
$vocab["error_room"]         = "Error: room ";
$vocab["not_found"]          = " not found";
$vocab["update_area_failed"] = "Update area failed: ";
$vocab["error_area"]         = "Error: area ";

# Used in del.php
$vocab["deletefollowing"]    = "Esto borara las siguientes agendas";
$vocab["sure"]               = "ESTA SEGURO?";
$vocab["YES"]                = "SI";
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
