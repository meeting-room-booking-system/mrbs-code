<?php // -*-mode: PHP; coding:iso-8859-1;-*-
// $Id$

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is a Spanish file.
//
//
//
//
// This file is PHP code. Treat it as such.

// The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

// Used in style.inc
$vocab["mrbs"]               = "Sistema de Reservas de Salas y Aulas";

// Used in functions.inc
$vocab["report"]             = "Informes";
$vocab["admin"]              = "Administración";
$vocab["help"]               = "Ayuda";
$vocab["search"]             = "Búsqueda";
$vocab["not_php3"]           = "ATENCIÓN: Puede que esto no funcione con PHP3";

// Used in day.php
$vocab["bookingsfor"]        = "Reservas para el";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Edificios";
$vocab["daybefore"]          = "Día Anterior";
$vocab["dayafter"]           = "Día Siguiente";
$vocab["gototoday"]          = "Día Actual";
$vocab["goto"]               = "Ir a";
$vocab["highlight_line"]     = "Remarcar esta Línea";
$vocab["click_to_reserve"]   = "Selecciona una Casilla para hacer una Reserva.";

// Used in trailer.inc
$vocab["viewday"]            = "Ver Día";
$vocab["viewweek"]           = "Ver Semana";
$vocab["viewmonth"]          = "Ver Mes";
$vocab["ppreview"]           = "Vista Previa";

// Used in edit_entry.php
$vocab["addentry"]           = "Nueva Reserva";
$vocab["editentry"]          = "Editar Reserva";
$vocab["copyentry"]          = "Copiar Reserva";
$vocab["editseries"]         = "Editar Serie";
$vocab["copyseries"]         = "Copiar Serie";
$vocab["namebooker"]         = "Nombre";
$vocab["fulldescription"]    = "Descripción Completa:";
$vocab["date"]               = "Fecha";
$vocab["start_date"]         = "Fecha Inicio";
$vocab["end_date"]           = "Fecha Fin";
$vocab["time"]               = "Hora";
$vocab["period"]             = "Periodo";
$vocab["duration"]           = "Duración";
$vocab["seconds"]            = "Segundos";
$vocab["minutes"]            = "Minutos";
$vocab["hours"]              = "Horas";
$vocab["days"]               = "Días";
$vocab["weeks"]              = "Semanas";
$vocab["years"]              = "Aaños";
$vocab["periods"]            = "Periodos";
$vocab["all_day"]            = "Día Completo";
$vocab["area"]               = "Edificio";
$vocab["type"]               = "Tipo";
$vocab["internal"]           = "Interna";
$vocab["external"]           = "Externa";
$vocab["save"]               = "Salvar";
$vocab["rep_type"]           = "Tipo Repetición";
$vocab["rep_type_0"]         = "Ninguna";
$vocab["rep_type_1"]         = "Diaria";
$vocab["rep_type_2"]         = "Semanal";
$vocab["rep_type_3"]         = "Mensual";
$vocab["rep_type_4"]         = "Anual";
$vocab["rep_type_5"]         = "Día correspondiente del Mes";
$vocab["rep_type_6"]         = "n-Semanal";
$vocab["rep_end_date"]       = "Fecha Tope Repetición";
$vocab["rep_rep_day"]        = "Día Repetición";
$vocab["rep_for_weekly"]     = "(Semanal)";
$vocab["rep_freq"]           = "Frecuencia";
$vocab["rep_num_weeks"]      = "Número de Semanas";
$vocab["rep_for_nweekly"]    = "(n-Semanas)";
$vocab["ctrl_click"]         = "Usar Control-Click para seleccionar más de una Sala";
$vocab["entryid"]            = "ID de Entrada ";
$vocab["repeat_id"]          = "ID de Repetición "; 
$vocab["you_have_not_entered"] = "No ha introducido ningún";
$vocab["you_have_not_selected"] = "No ha seleccionado ningún";
$vocab["valid_room"]         = "Sala.";
$vocab["valid_time_of_day"]  = "Hora Válida del Día.";
$vocab["brief_description"]  = "Breve Descripción.";
$vocab["useful_n-weekly_value"] = "valor útil de n-Semanalmente.";
$vocab["private"]            = "Privado";
$vocab["unavailable"]        = "No disponible";

// Used in view_entry.php
$vocab["description"]        = "Descripción";
$vocab["room"]               = "Sala";
$vocab["createdby"]          = "Creada por";
$vocab["lastupdate"]         = "Ultima Actualización";
$vocab["deleteentry"]        = "Borrar Reserva";
$vocab["deleteseries"]       = "Borrar Serie";
$vocab["confirmdel"]         = "Seguro que desea borrar esta reserva?";
$vocab["returnprev"]         = "Volver a Página Anterior";
$vocab["invalid_entry_id"]   = "ID de Entrada Incorrecto.";
$vocab["invalid_series_id"]  = "ID de Serie Incorrecto.";

// Used in edit_entry_handler.php
$vocab["error"]              = "Error";
$vocab["sched_conflict"]     = "Conflicto de Planificación";
$vocab["conflict"]           = "La nueva reserva entra en conflicto con la(s) siguiente(s) entrada(s)";
$vocab["rules_broken"]       = "La nueva reserva entra en conflicto con los procedimientos";
$vocab["too_may_entrys"]     = "Las opciones seleccionadas crearán demasiadas entradas.<br>Por favor, revise las opciones";
$vocab["returncal"]          = "Volver a Vista de Calendario";
$vocab["failed_to_acquire"]  = "Error al obtener acceso a la Base de Datos"; 
$vocab["invalid_booking"]    = "Reserva Incorrecta";
$vocab["must_set_description"] = "Debes introducir una breve descripción para la Reserva. Por favor, vuelve atrás e introduce una.";
$vocab["mail_subject_entry"] = "Reserva creada/modificada en el Sistema de Reservas $mrbs_company";
$vocab["mail_body_new_entry"] = "Nueva Reserva añadida, aquí están los detalles:";
$vocab["mail_body_del_entry"] = "Reserva borrada, aquí están los detalles:";
$vocab["mail_body_changed_entry"] = "Reserva modificada, aquí están los detalles:";
$vocab["mail_subject_delete"] = "Reserva borrada en el Sistema de Reservas $mrbs_company";

// Authentication stuff
$vocab["accessdenied"]       = "Acceso Denegado";
$vocab["norights"]           = "No tiene autorización para modificar este dato.";
$vocab["please_login"]       = "Introduzca su Nombre de Usuario";
$vocab["user_name"]          = "Nombre";
$vocab["user_password"]      = "Contraseña";
$vocab["unknown_user"]       = "Usuario Anónimo";
$vocab["user_level"]         = "Privilegios";
$vocab["you_are"]            = "Hola";
$vocab["login"]              = "Entrar";
$vocab["logoff"]             = "Salir";

// Database upgrade code
$vocab["database_login"]           = "Acceso a Base de Datos";
$vocab["upgrade_required"]         = "La Base de Datos ha de ser actualizada.";
$vocab["supply_userpass"]          = "Por favor, utilice un acceso de usuario con derechos de administración .";
$vocab["contact_admin"]            = "Si usted no es administrador, por favor póngase en contacto con $mrbs_admin.";
$vocab["upgrade_to_version"]       = "Actualizando Base de Datos a versión";
$vocab["upgrade_to_local_version"] = "Actualizando Base de Datos a versión local";
$vocab["upgrade_completed"]        = "Completada la actualización de la Base de Datos.";

// User access levels
$vocab["level_0"]            = "ninguno";
$vocab["level_1"]            = "ususario";
$vocab["level_2"]            = "administración";
$vocab["level_3"]            = "usuario Administrador";

// Authentication database
$vocab["user_list"]          = "Lista de Usuarios";
$vocab["edit_user"]          = "Editar Usuario";
$vocab["delete_user"]        = "Borrar este Usuario";
//$vocab["user_name"]         = Use the same as above, for consistency.
//$vocab["user_password"]     = Use the same as above, for consistency.
$vocab["user_email"]         = "Dirección de Correo Electrónico";
$vocab["password_twice"]     = "Si quieres cambiar la contraseña, por favor teclee la nueva dos veces";
$vocab["passwords_not_eq"]   = "Error: Las contraseñas no son iguales.";
$vocab["add_new_user"]       = "Añadir un Nuevo Usuario";
$vocab["action"]             = "Acciones";
$vocab["user"]               = "Usuario";
$vocab["administrator"]      = "Administrador";
$vocab["unknown"]            = "Desconocido";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Click para mostrar todos mis eventos futuros";
$vocab["no_users_initial"]   = "No hay usuarios en la Base de Datos, permitiendo la creacion del usuario inicial";
$vocab["no_users_create_first_admin"] = "Crea un Usuario con permisos de Administrador y entonces podrás acceder y crear más Usuarios.";
$vocab["warning_last_admin"] = "¡Atención! Este es el último administrador y por eso no puede ser borrado o quitarle los derechos de administración; si se hiciera, el sistema quedaría bloqueado.";

// Used in search.php
$vocab["invalid_search"]     = "Cadena de búsqueda vacía o incorrecta.";
$vocab["search_results"]     = "Buscar resultados de";
$vocab["nothing_found"]      = "No se encontraron coincidencias.";
$vocab["records"]            = "Registros ";
$vocab["through"]            = " a través ";
$vocab["of"]                 = " de ";
$vocab["previous"]           = "Anterior";
$vocab["next"]               = "Siguiente";
$vocab["entry"]              = "Entrada";
$vocab["view"]               = "Ver";
$vocab["advanced_search"]    = "Búsqueda Advanzada";
$vocab["search_button"]      = "Búsqueda";
$vocab["search_for"]         = "Buscar por";
$vocab["from"]               = "Desde";

// Used in report.php
$vocab["report_on"]          = "Informe de Reuniones";
$vocab["report_start"]       = "Fecha Inicio";
$vocab["report_end"]         = "Fecha Fin";
$vocab["match_area"]         = "Edificio";
$vocab["match_room"]         = "Sala";
$vocab["match_type"]         = "Tipo de Coincidencia";
$vocab["ctrl_click_type"]    = "Use Control-Click para seleccionar más de un Tipo";
$vocab["match_entry"]        = "Descripción Breve";
$vocab["match_descr"]        = "Descripción Completa";
$vocab["include"]            = "Incluir";
$vocab["report_only"]        = "Solamente Informe";
$vocab["summary_only"]       = "Solamente Resumen";
$vocab["report_and_summary"] = "Informe y Resumen";
$vocab["report_as_csv"]         = "Informe en formato CSV";
$vocab["summary_as_csv"]        = "Sumario en formato CSV";
$vocab["summarize_by"]       = "Resumir por";
$vocab["sum_by_descrip"]     = "Descripción Breve";
$vocab["sum_by_creator"]     = "Creador";
$vocab["entry_found"]        = "registro encontrado";
$vocab["entries_found"]      = "registros encontrados";
$vocab["summary_header"]     = "Resumen de (Registros) Horas";
$vocab["summary_header_per"] = "Resumen de (Entradas) Periodos";
$vocab["entries"]               = "Registros";
$vocab["total"]              = "Total";
$vocab["submitquery"]        = "Pedir Informe";
$vocab["sort_rep"]           = "Ordenar Informe por";
$vocab["sort_rep_time"]      = "Fecha/Hora de Comienzo";
$vocab["rep_dsp"]            = "Mostrar en Informe";
$vocab["rep_dsp_dur"]        = "Duración";
$vocab["rep_dsp_end"]        = "Hora de Finalización";
$vocab["fulldescription_short"] = "Descripción completa";

// Used in week.php
$vocab["weekbefore"]         = "Ir a Semana Anterior";
$vocab["weekafter"]          = "Ir a Semana Posterior";
$vocab["gotothisweek"]       = "Ir a Semana Corriente";

// Used in month.php
$vocab["monthbefore"]        = "Ir a Mes Anterior";
$vocab["monthafter"]         = "Ir a Mes Posterior";
$vocab["gotothismonth"]      = "Ir a Mes Corriente";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "No hay Salas definidas para este Edificio";

// Used in admin.php
$vocab["edit"]               = "Editar";
$vocab["delete"]             = "Borrar";
$vocab["rooms"]              = "Salas";
$vocab["in"]                 = "en";
$vocab["noareas"]            = "No hay Edificios";
$vocab["addarea"]            = "Agregar Edificio";
$vocab["name"]               = "Nombre";
$vocab["noarea"]             = "No se seleccionó Edificio";
$vocab["browserlang"]        = "Su navegador está configurado para usar los siguientes juegos de caracteres";
$vocab["addroom"]            = "Agregar Sala";
$vocab["capacity"]           = "Capacidad (Personas)";
$vocab["norooms"]            = "No hay Salas.";
$vocab["administration"]     = "Administración";
$vocab["invalid_area_name"]  = "Este nombre de edificio ya está siendo utilizado";

// Used in edit_area_room.php
$vocab["editarea"]           = "Editar Edificio";
$vocab["change"]             = "Cambiar";
$vocab["backadmin"]          = "Volver a Admin";
$vocab["editroomarea"]       = "Editar Descripción de Edificio o Sala";
$vocab["editroom"]           = "Editar Sala";
$vocab["update_room_failed"] = "Actualización de Sala fallida: ";
$vocab["error_room"]         = "Error: Sala ";
$vocab["not_found"]          = " no encontrado";
$vocab["update_area_failed"] = "Actualización de Edificio fallida: ";
$vocab["error_area"]         = "Error: Edificio ";
$vocab["room_admin_email"]   = "Correo Electrónico del Administrador de Sala";
$vocab["area_admin_email"]   = "Correo Electrónico del Administrador de Edificio";
$vocab["area_first_slot_start"]   = "Comienzo del primer periodo";
$vocab["area_last_slot_start"]    = "Comienzo del último periodo";
$vocab["area_res_mins"]           = "Duración (minutos)";
$vocab["area_def_duration_mins"]  = "Duración por defecto (minutos)";
$vocab["invalid_area"]            = "¡Edificio inválido!";
$vocab["invalid_room_name"]       = "¡Este nombre de sala ya se ha usado en este edificio!";
$vocab["invalid_email"]           = "¡Correo Electrónico Incorrecto!";
$vocab["invalid_resolution"]      = "¡Combinación incorrecta de duración y periodos primero y último!";
$vocab["too_many_slots"]          = '¡Es necesario aumentar el valor de $max_slots en el archivo de configuración!';
$vocab["general_settings"]        = "Generales";
$vocab["time_settings"]           = "Periodos horarios";
$vocab["private_settings"]        = "Reservas privadas";
$vocab["allow_private"]           = "Permitir reservas privadas";
$vocab["force_private"]           = "Forzar reservas privadas";
$vocab["default_settings"]        = "Ajustes por defecto/forzados";
$vocab["default_private"]         = "Privado";
$vocab["default_public"]          = "Público";
$vocab["private_display"]         = "Reservas privadas (mostrar)";
$vocab["private_display_label"]   = "¿Como deben mostrarse las reservas privadas?";
$vocab["private_display_caution"] = "¡CUIDADO: piense en las implicaciones sobre la privacidad, antes de cambiar estos ajustes!";
$vocab["treat_respect"]           = "Respetar el ajuste de privacidad de la reserva";
$vocab["treat_private"]           = "Tratar todas las reservas como privadas, ignorando su ajuste de privacidad";
$vocab["treat_public"]            = "Tratar todas las reservas como públicas, ignorando su ajuste de privacidad";
$vocab["sort_key"]                = "Clave de ordenación";
$vocab["sort_key_note"]           = "Esta es la clave utilizada para ordenar las salas";

// Used in edit_users.php
$vocab["name_empty"]         = "Se debe introducir un nombre.";
$vocab["name_not_unique"]    = "ya existe.  Por favor, elija otro nombre.";

// Used in del.php
$vocab["deletefollowing"]    = "Esto borrará las siguientes Agendas";
$vocab["sure"]               = "ESTÁ SEGURO?";
$vocab["YES"]                = "SÍ";
$vocab["NO"]                 = "NO";
$vocab["delarea"]            = "Debe borrar todas las Salas antes de borrar este Edificio<p>";

// Used in help.php
$vocab["about_mrbs"]         = "Acerca de MRBS";
$vocab["database"]           = "Base de Datos";
$vocab["system"]             = "Sistema";
$vocab["please_contact"]     = "Contacte con ";
$vocab["servertime"]         = "Hora del Servidor";
$vocab["for_any_questions"]  = "para cualquier duda.";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Error: No se pudo conectar a la Base de Datos";

?>
