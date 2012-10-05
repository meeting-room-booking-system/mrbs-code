<?php // -*-mode: PHP; coding:utf-8;-*-
// $Id$
// Modified and updated by JLMartin 2011-03-02 

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is a Spanish file.
//
//
//
//
// This file is PHP code. Treat it as such.

// Used in style.inc
$vocab["mrbs"]               = "Sistema de Reservas de Salas y Aulas";

// Used in functions.inc
$vocab["report"]             = "Informes";
$vocab["admin"]              = "Administración";
$vocab["help"]               = "Ayuda";
$vocab["search"]             = "Búsqueda";
$vocab["not_php3"]           = "ATENCIÓN: Puede que esto no funcione con PHP3";
$vocab["outstanding"]        = "Reservas pendientes";

// Used in day.php
$vocab["bookingsfor"]        = "Reservas para el";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Agrupaciones";
$vocab["daybefore"]          = "Día Anterior";
$vocab["dayafter"]           = "Día Siguiente";
$vocab["gototoday"]          = "Día Actual";
$vocab["goto"]               = "Ir a";
$vocab["highlight_line"]     = "Remarcar esta Línea";
$vocab["click_to_reserve"]   = "Selecciona una Casilla para hacer una Reserva.";
$vocab["timezone"]           = "Zona horaria";

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
$vocab["start"]              = "Comienzo";
$vocab["end"]                = "Final";
$vocab["start_date"]         = "Fecha Inicio";
$vocab["end_date"]           = "Fecha Fin";
$vocab["time"]               = "Hora";
$vocab["period"]             = "Periodo";
$vocab["duration"]           = "Duración";
$vocab["second_lc"]          = "segundo";
$vocab["seconds"]            = "Segundos";
$vocab["minute_lc"]          = "minuto";
$vocab["minutes"]            = "Minutos";
$vocab["hour_lc"]            = "hora";
$vocab["hours"]              = "Horas";
$vocab["day_lc"]             = "día";
$vocab["days"]               = "Días";
$vocab["week_lc"]            = "semana";
$vocab["weeks"]              = "Semanas";
$vocab["year_lc"]            = "año";
$vocab["years"]              = "Años";
$vocab["period_lc"]          = "periodo";
$vocab["periods"]            = "Periodos";
$vocab["all_day"]            = "Día Completo";
$vocab["area"]               = "Agrupación";
$vocab["type"]               = "Tipo";
$vocab["save"]               = "Guardar";
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
$vocab["ctrl_click"]         = "Usar Control-Click para seleccionar más de una Sala";
$vocab["entryid"]            = "ID de Entrada ";
$vocab["repeat_id"]          = "ID de Repetición "; 
$vocab["you_have_not_entered"] = "No ha introducido ningún";
$vocab["brief_description"]  = "Breve Descripción.";
$vocab["useful_n-weekly_value"] = "valor útil de n-Semanalmente.";
$vocab["status"]             = "Estado";
$vocab["public"]             = "Pública";
$vocab["private"]            = "Privada";
$vocab["unavailable"]        = "No disponible";
$vocab["is_mandatory_field"] = "este es un campo obligatorio, por favor, escriba un valor";
$vocab["missing_mandatory_field"] = "No se ha incluído un valor para un campo obligatorio";
$vocab["confirmed"]          = "Confirmada";
$vocab["start_after_end"]    = "Día inicial es posterior al día final";
$vocab["start_after_end_long"] = "Error: el día de comienzo es posterior al día de finalización";

// Used in view_entry.php
$vocab["description"]        = "Descripción";
$vocab["room"]               = "Sala";
$vocab["createdby"]          = "Creada por";
$vocab["lastupdate"]         = "Última Actualización";
$vocab["deleteentry"]        = "Borrar Reserva";
$vocab["deleteseries"]       = "Borrar Serie";
$vocab["exportentry"]        = "Exportar Reserva";
$vocab["exportseries"]       = "Exportar Serie";
$vocab["confirmdel"]         = "¿Seguro que desea borrar esta reserva?";
$vocab["returnprev"]         = "Volver a Página Anterior";
$vocab["invalid_entry_id"]   = "ID de Reserva Incorrecto.";
$vocab["invalid_series_id"]  = "ID de Serie Incorrecto.";
$vocab["confirmation_status"] = "Estado de confirmación";
$vocab["tentative"]           = "Provisional";
$vocab["approval_status"]     = "Estado de aprobación";
$vocab["approved"]            = "Aprobada";
$vocab["awaiting_approval"]   = "Esperando aprobación";
$vocab["approve"]             = "Aprobar";
$vocab["reject"]              = "Rechazar";
$vocab["more_info"]           = "Más información";
$vocab["remind_admin"]        = "Recordar a los Administradores";
$vocab["series"]              = "Serie";
$vocab["request_more_info"]   = "Por favor, describa la información extra que se necesita";
$vocab["reject_reason"]       = "Por favor, describa el motivo para rechazar esta solitud";
$vocab["send"]                = "Enviar";
$vocab["approve_failed"]      = "La reserva no pudo ser aprobada.";
$vocab["no_request_yet"]      = "Todavía no se ha enviado ninguna solicitud"; // Used for the title tooltip on More Info button
$vocab["last_request"]        = "Última solicitud enviada a ";         // Used for the title tooltip on More Info button
$vocab["by"]                  = "por";                           // Used for the title tooltip on More Info button
$vocab["sent_at"]             = "Enviada a ";
$vocab["yes"]                 = "Si";
$vocab["no"]                  = "No";

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
$vocab["mail_subject_approved"]  = "Reserva aprobada para $mrbs_company";
$vocab["mail_subject_rejected"]  = "Reserva rechazada para $mrbs_company";
$vocab["mail_subject_more_info"] = "$mrbs_company: solicitud de más información";
$vocab["mail_subject_reminder"]  = "Recordatorio para $mrbs_company";
$vocab["mail_body_approved"]     = "Una reserva ha sido aprobada por los administradores; estos son los detalles:";
$vocab["mail_body_rej_entry"]    = "Una reserva ha sido rechazada por los administradores; estos son los detalles:";
$vocab["mail_body_more_info"]    = "Los administradores requieren más información para la reserva; estos son los detalles:";
$vocab["mail_body_reminder"]     = "Recordatorio - una reserva está a la espera de aprobación; estos son los detalles:";
$vocab["mail_subject_new_entry"]     = "Creada una reserva para $mrbs_company";
$vocab["mail_subject_changed_entry"] = "Modificada una reserva para $mrbs_company";
$vocab["mail_subject_delete"] = "Reserva borrada en el Sistema de Reservas $mrbs_company";
$vocab["mail_body_new_entry"] = "Nueva Reserva añadida, aquí están los detalles:";
$vocab["mail_body_changed_entry"] = "Reserva modificada, aquí están los detalles:";
$vocab["mail_body_del_entry"] = "Reserva borrada, aquí están los detalles:";
$vocab["new_value"]           = "Nuevo";
$vocab["old_value"]           = "Antiguo";
$vocab["deleted_by"]          = "Borrado por";
$vocab["reason"]              = "Motivo";
$vocab["info_requested"]      = "Información requerida";
$vocab["min_time_before"]     = "El intervalo mínimo desde ahora hasta el comienzo de la reservas es";
$vocab["max_time_before"]     = "El intervalo máximo desde ahora hasta el comienzo de la reservas es";

// Used in pending.php
$vocab["pending"]            = "Reserva en espera de ser aprobada";
$vocab["none_outstanding"]   = "Usted no tiene reservas en espera de ser aprobadas.";

// Authentication stuff
$vocab["accessdenied"]       = "Acceso Denegado";
$vocab["norights"]           = "No tiene autorización para modificar este dato.";
$vocab["please_login"]       = "Introduzca su Nombre de Usuario";
$vocab["users.name"]          = "Nombre";
$vocab["users.password"]      = "Contraseña";
$vocab["users.level"]         = "Privilegios";
$vocab["unknown_user"]       = "Usuario Anónimo";
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
$vocab["level_1"]            = "usuario";
$vocab["level_2"]            = "administración";
$vocab["level_3"]            = "usuario Administrador";

// Authentication database
$vocab["user_list"]          = "Lista de Usuarios";
$vocab["edit_user"]          = "Editar Usuario";
$vocab["delete_user"]        = "Borrar este Usuario";
//$vocab["user_name"]         = Use the same as above, for consistency.
//$vocab["user_password"]     = Use the same as above, for consistency.
$vocab["users.email"]         = "Dirección de Correo Electrónico";
$vocab["password_twice"]     = "Si quiere cambiar la contraseña, por favor escriba la nueva dos veces";
$vocab["passwords_not_eq"]   = "Error: Las contraseñas no son iguales.";
$vocab["password_invalid"]   = "La contraseña no cumple los requisitos.  Debe contener, al menos:";
$vocab["policy_length"]      = "carácter(es)";
$vocab["policy_alpha"]       = "letra(s)";
$vocab["policy_lower"]       = "letra(s) minúscula(s)";
$vocab["policy_upper"]       = "letra(s) mayúscula(s)";
$vocab["policy_numeric"]     = "número(s)";
$vocab["policy_special"]     = "carácter(es) especial(es)";
$vocab["add_new_user"]       = "Añadir un Nuevo Usuario";
$vocab["action"]             = "Acciones";
$vocab["user"]               = "Usuario";
$vocab["administrator"]      = "Administrador";
$vocab["unknown"]            = "Desconocido";
$vocab["ok"]                 = "Aceptar";
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
$vocab["advanced_search"]    = "Búsqueda Advanzada";
$vocab["search_button"]      = "Búsqueda";
$vocab["search_for"]         = "Buscar por";
$vocab["from"]               = "Desde";

// Used in report.php
$vocab["report_on"]          = "Informe de Reuniones";
$vocab["report_start"]       = "Fecha Inicio";
$vocab["report_end"]         = "Fecha Fin";
$vocab["match_area"]         = "Agrupación";
$vocab["match_room"]         = "Sala";
$vocab["match_type"]         = "Tipo de Coincidencia";
$vocab["ctrl_click_type"]    = "Use Control-Click para seleccionar más de un Tipo";
$vocab["match_entry"]        = "Descripción Breve";
$vocab["match_descr"]        = "Descripción Completa";
$vocab["ical"]               = "Informe como iCalendar (archivo .ics) - excluyendo periodos";
$vocab["summarize_by"]       = "Resumir por";
$vocab["sum_by_descrip"]     = "Descripción Breve";
$vocab["sum_by_creator"]     = "Creador";
$vocab["entry_found"]        = "registro encontrado";
$vocab["entries_found"]      = "registros encontrados";
$vocab["summary_header"]     = "Resumen de (Registros) Horas";
$vocab["summary_header_per"] = "Resumen de (Entradas) Periodos";
$vocab["summary_header_both"] = "Resumen de (Entradas) Horas/Periodos";
$vocab["entries"]             = "Registros";
$vocab["total"]              = "Total";
$vocab["submitquery"]        = "Pedir Informe";
$vocab["sort_rep"]           = "Ordenar Informe por";
$vocab["sort_rep_time"]      = "Fecha/Hora de Comienzo";
$vocab["fulldescription_short"] = "Descripción completa";
$vocab["both"]                  = "Todos";
$vocab["privacy_status"]        = "Estado de privacidad";
$vocab["search_criteria"]       = "Criterios de búsqueda";
$vocab["presentation_options"]  = "Opciones de presentación";

// Used in week.php
$vocab["weekbefore"]         = "Ir a Semana Anterior";
$vocab["weekafter"]          = "Ir a Semana Posterior";
$vocab["gotothisweek"]       = "Ir a Semana Corriente";

// Used in month.php
$vocab["monthbefore"]        = "Ir a Mes Anterior";
$vocab["monthafter"]         = "Ir a Mes Posterior";
$vocab["gotothismonth"]      = "Ir a Mes Corriente";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "No hay Salas definidas para esta Agrupación";

// Used in admin.php
$vocab["edit"]               = "Editar";
$vocab["delete"]             = "Borrar";
$vocab["rooms"]              = "Salas";
$vocab["in"]                 = "en";
$vocab["noareas"]            = "No hay Agrupaciones";
$vocab["noareas_enabled"]    = "No hay Agrupaciones habilitadas.";
$vocab["addarea"]            = "Agregar Agrupación";
$vocab["name"]               = "Nombre";
$vocab["noarea"]             = "No se seleccionó Agrupación";
$vocab["browserlang"]        = "Su navegador está configurado para usar los siguientes juegos de caracteres";
$vocab["addroom"]            = "Agregar Sala";
$vocab["capacity"]           = "Capacidad (Personas)";
$vocab["norooms"]            = "No hay Salas.";
$vocab["norooms_enabled"]    = "No hay Salas habilitadas.";
$vocab["administration"]     = "Administración";
$vocab["invalid_area_name"]  = "Este nombre de Agrupación ya está siendo utilizado";
$vocab["empty_name"]         = "¡No se ha escrito un nombre!";

// Used in edit_area_room.php
$vocab["editarea"]           = "Editar Agrupación";
$vocab["change"]             = "Cambiar";
$vocab["backadmin"]          = "Volver a Admin";
$vocab["editroomarea"]       = "Editar descripción de Agrupación o Sala";
$vocab["editroom"]           = "Editar Sala";
$vocab["viewroom"]                = "Ver Sala";
$vocab["update_room_failed"] = "Actualización de Sala fallida: ";
$vocab["error_room"]         = "Error: Sala ";
$vocab["not_found"]          = " no encontrado";
$vocab["update_area_failed"] = "Actualización de Agrupación fallida: ";
$vocab["error_area"]         = "Error: Agrupación ";
$vocab["room_admin_email"]   = "Correo Electrónico del Administrador de Sala";
$vocab["area_admin_email"]   = "Correo Electrónico del Administrador de Agrupación";
$vocab["area_first_slot_start"]   = "Comienzo del primer periodo";
$vocab["area_last_slot_start"]    = "Comienzo del último periodo";
$vocab["area_res_mins"]           = "Duración (minutos)";
$vocab["area_def_duration_mins"]  = "Duración por defecto (minutos)";
$vocab["invalid_area"]            = "¡Agrupación inválida!";
$vocab["invalid_room_name"]       = "¡Este nombre de Sala ya se ha usado en esta Agrupación!";
$vocab["invalid_email"]           = "¡Correo Electrónico Incorrecto!";
$vocab["invalid_resolution"]      = "¡Combinación incorrecta de duración y periodos primero y último!";
$vocab["too_many_slots"]          = '¡Es necesario aumentar el valor de $max_slots en el archivo de configuración!';
$vocab["general_settings"]        = "Generales";
$vocab["time_settings"]           = "Periodos horarios";
$vocab["confirmation_settings"]   = "Ajustes de confirmación";
$vocab["allow_confirmation"]      = "Permitir reservas provisionales";
$vocab["default_settings_conf"]   = "Valores por defecto";
$vocab["default_confirmed"]       = "Confirmada";
$vocab["default_tentative"]       = "Provisional";
$vocab["approval_settings"]       = "Ajustes de aprobación";
$vocab["enable_approval"]         = "Requerir aprobación para las reservas";
$vocab["enable_reminders"]        = "Permitir a usuarios recordar a los administradores";
$vocab["private_settings"]        = "Reservas privadas";
$vocab["allow_private"]           = "Permitir reservas privadas";
$vocab["force_private"]           = "Forzar reservas privadas";
$vocab["default_settings"]        = "Ajustes por defecto/forzados";
$vocab["default_private"]         = "Privado";
$vocab["default_public"]          = "Público";
$vocab["private_display"]         = "Reservas privadas (mostrar)";
$vocab["private_display_label"]   = "Como deben mostrarse las reservas privadas?";
$vocab["private_display_caution"] = "¡CUIDADO: piense en las implicaciones sobre la privacidad, antes de cambiar estos ajustes!";
$vocab["treat_respect"]           = "Respetar el ajuste de privacidad de la reserva";
$vocab["treat_private"]           = "Tratar todas las reservas como privadas, ignorando su ajuste de privacidad";
$vocab["treat_public"]            = "Tratar todas las reservas como públicas, ignorando su ajuste de privacidad";
$vocab["sort_key"]                = "Clave de ordenación";
$vocab["sort_key_note"]           = "Esta es la clave utilizada para ordenar las salas";
$vocab["booking_policies"]        = "Política de reservas";
$vocab["min_book_ahead"]          = "Reservas futuras - mínimo";
$vocab["max_book_ahead"]          = "Reservas futuras - máximo";
$vocab["custom_html"]             = "HTML a medida";
$vocab["custom_html_note"]        = "Este campo puede usarse para mostrar un HTML propio, por ejemplo un mapa de Google embebido";
$vocab["email_list_note"]         = "Incluir una lista de direcciones email separadas por comas o saltos de línea";
$vocab["mode"]                    = "Modo";
$vocab["mode_periods"]            = "Periodos";
$vocab["mode_times"]              = "Horas";
$vocab["times_only"]              = "Solamente modo Horas";               
$vocab["enabled"]                 = "Habilitada";
$vocab["disabled"]                = "Deshabilitada";
$vocab["disabled_area_note"]      = "Si esta Agrupación está deshabilitada, no aparecerá en las vistas de calendario " .
                                    "y no será posible hacer nuevas reservas en ella. Sin embargo, las reservas existentes " .
                                    "serán respetadas y por ello serán visibles en los resultados de Búsqueda e Informes.";
$vocab["disabled_room_note"]      = "Si esta Sala está deshabilitada, no aparecerá en las vistas de calendario " .
                                    "y no será posible hacer nuevas reservas en ella. Sin embargo, las reservas existentes " .
                                    "serán respetadas y por ello serán visibles en los resultados de Búsqueda e Informes.";
$vocab["book_ahead_note_periods"] = "Cuando se usan periodos, las horas de reservas futuras se redondearán al día completo más cercano  .";

// Used in edit_users.php
$vocab["name_empty"]         = "Se debe introducir un nombre.";
$vocab["name_not_unique"]    = "ya existe.  Por favor, elija otro nombre.";

// Used in del.php
$vocab["deletefollowing"]    = "Esto borrará las siguientes Agendas";
$vocab["sure"]               = "ESTÁ SEGURO?";
$vocab["YES"]                = "SÍ";
$vocab["NO"]                 = "NO";
$vocab["delarea"]            = "Debe borrar todas las Salas antes de borrar esta Agrupación<p>";

// Used in help.php
$vocab["about_mrbs"]         = "Acerca de MRBS";
$vocab["database"]           = "Base de Datos";
$vocab["system"]             = "Sistema";
$vocab["servertime"]         = "Hora del Servidor";
$vocab["please_contact"]     = "Contacte con ";
$vocab["for_any_questions"]  = "para cualquier duda.";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Error: No se pudo conectar a la Base de Datos";

// Entry types
$vocab["type.I"]             = "Interna";
$vocab["type.E"]             = "Externa";

// General
$vocab["fatal_db_error"]     = "Error: la base de datos no está disponible en este momento.";
?>
