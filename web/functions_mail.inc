<?php
// +---------------------------------------------------------------------------+
// | Meeting Room Booking System.                                              |
// +---------------------------------------------------------------------------+
// | Functions dedicated to emails handling.                                   |
// |---------------------------------------------------------------------------+
// | I keeped these functions in a separated file to avoid burden the main     |
// | function.inc files if emails are not used.                                |
// |                                                                           |
// | USE : This file should be included in all files where emails functions    |
// |        are likely to be used.                                             |
// +---------------------------------------------------------------------------+
// | @author    thierry_bo.                                                    |
// | @version   $Revision$.                                              |
// +---------------------------------------------------------------------------+
//
// $Id$

// {{{ removeMailUnicode()

/**
 * Convert already utf-8 encoded strings to charset defined for mails in
 * c.i.php.
 *
 * @param string    $string   string to convert
 * @return string   $string   string converted to $mail_vocab["charset"]
 */
function removeMailUnicode($string)
{
    global $unicode_encoding, $mail_vocab;
    //
    if ($unicode_encoding)
    {
        return iconv("utf-8", $mail_vocab["charset"], $string);
    }
    else
    {
        return $string;
    }
}

// }}}
// {{{ getMailPeriodDateString()

/**
 * Format a timestamp in non-unicode output (for emails).
 *
 * @param   timestamp   $t
 * @param   int         $mod_time
 * @return  array
 */
function getMailPeriodDateString($t, $mod_time=0)
{
    global $periods;
    //
    $time = getdate($t);
    $p_num = $time['minutes'] + $mod_time;
    ( $p_num < 0 ) ? $p_num = 0 : '';
    ( $p_num >= count($periods) - 1 ) ? $p_num = count($periods ) - 1 : '';
    // I have made the separater a ',' as a '-' leads to an ambiguious
    // display in report.php when showing end times.
    return array($p_num, $periods[$p_num] . strftime(", %A %d %B %Y",$t));
}

// }}}
// {{{ getMailTimeDateString()

/**
 * Format a timestamp in non-unicode output (for emails).
 *
 * @param   timestamp   $t  timestamp to format
 * @return  string          formated string
 */
function getMailTimeDateString($t)
{
    global $twentyfourhour_format;
    // This bit's necessary, because it seems %p in strftime format
    // strings doesn't work
    $ampm = date("a",$t);
    if ($twentyfourhour_format)
    {
        return strftime("%H:%M:%S - %A %d %B %Y",$t);
    }
    else
    {
        return strftime("%I:%M:%S$ampm - %A %d %B %Y",$t);
    }
}

// }}}
// {{{ notifyAdminOnBooking()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param bool    $new_entry    to know if this is a new entry or not
 * @param int     $new_id       used for create a link to the new entry
 * @return bool                 TRUE or PEAR error object if fails
 */
function notifyAdminOnBooking($new_entry , $new_id)
{
    global $url_base, $returl, $mail_vocab, $name, $description, $area_name;
    global $room_name, $starttime, $duration, $dur_units, $end_date, $endtime;
    global $rep_enddate, $typel, $type, $create_by, $rep_type, $enable_periods;
    global $tbl_room, $tbl_area, $tbl_entry, $mail_previous;
    //
    $subject = $mail_vocab["mail_subject_entry"];
    if ($new_entry)
    {
        $body = $mail_vocab["mail_body_new_entry"] . "\n\n";
    }
    else
    {
        $body = $mail_vocab["mail_body_changed_entry"] . "\n\n";
    }
    // Set the link to view entry page
    if (isset($url_base) && ($url_base != ""))
    {
        $body .= "$url_base/view_entry.php?id=$new_id\n";
    }
    else
    {
        $url = explode(basename($returl), $returl);
        $body .= $url[0] . "view_entry.php?id=$new_id\n";
    }
    // Displays/don't displays entry details
    if (MAIL_DETAILS)
    {
        $body .= "\n" . $mail_vocab["namebooker"] . " ";
        $body .= compareEntries(removeMailUnicode($name),
            removeMailUnicode($mail_previous['namebooker']), $new_entry)  . "\n";
        $body .= $mail_vocab["description"] . " ";
        $body .= compareEntries(removeMailUnicode($description),
            removeMailUnicode($mail_previous['description']), $new_entry) . "\n";
        $body .= $mail_vocab["room"] . ": " .
            compareEntries(removeMailUnicode($area_name),
            removeMailUnicode($mail_previous['area_name']), $new_entry);
        $body .= " - " . compareEntries(removeMailUnicode($room_name),
            removeMailUnicode($mail_previous['room_name']), $new_entry) . "\n";
        if ( $enable_periods )
        {
            list( $start_period, $start_date) =
                getMailPeriodDateString($starttime);
            $body .= $mail_vocab["start_date"] . " ";
            $body .= compareEntries($start_period, $mail_previous['start_period'], $new_entry)
                . " - " . compareEntries(unHtmlEntities($start_date),
                unHtmlEntities($mail_previous['start_date']), $new_entry) . "\n";
        }
        else
        {
            $start_date = getMailTimeDateString($starttime);
            $body .= $mail_vocab["start_date"] . " " .
                compareEntries($start_date, $mail_previous['start_date'], $new_entry) . "\n";
        }
        $body .= $mail_vocab["duration"] . " " .
            compareEntries($duration, $mail_previous['duration'], $new_entry);
        $body .= " " . compareEntries($mail_vocab["$dur_units"],
            $mail_previous['dur_units'], $new_entry) . "\n";
        if ( $enable_periods )
        {
            // If this is a serie, $endtime = serie end_date
            isset($rep_enddate) ? $endtime = $rep_enddate : '';
            list($end_period, $end_date) =  getMailPeriodDateString($endtime);
            $body .= $mail_vocab["end_date"] . " ";
            $body .= compareEntries($end_period, $mail_previous['end_period'], $new_entry)
                . " - " . compareEntries(unHtmlEntities($end_date),
                unHtmlEntities($mail_previous['end_date']), $new_entry) ."\n";
        }
        else
        {
            // If this is a serie, $endtime = serie end_date
            isset($rep_enddate) ? $endtime = $rep_enddate : '';
            $end_date = getMailTimeDateString($endtime);
            $body .= $mail_vocab["end_date"] . " " .
                compareEntries($end_date, $mail_previous['end_date'], $new_entry) . "\n";
        }
        $body .= $mail_vocab["type"] . " ";
        if ($new_entry)
        {
            $body .= $typel[$type];
        }
        else
        {
            $temp = $mail_previous['type'];
            $body .= compareEntries($typel[$type], $typel[$temp], $new_entry);
        }
        $body .= "\n" . $mail_vocab["createdby"] . " " .
            compareEntries($create_by, $mail_previous['createdby'], $new_entry) . "\n";
        $body .= $mail_vocab["lastupdate"] . " " .
            compareEntries(getMailTimeDateString(time()), $mail_previous['updated'], $new_entry);
        $body .= "\n" . $mail_vocab["rep_type"];
        if ($new_entry)
        {
            $body .= " " . $mail_vocab["rep_type_$rep_type"];
        }
        else
        {
            $temp = $mail_previous['rep_type'];
            $body .=  " " . compareEntries($mail_vocab["rep_type_$rep_type"],
                $mail_vocab["rep_type_$temp"], $new_entry) . "\n";
        }
    }
    $recipients = "";
    (MAIL_ADMIN_ON_BOOKINGS) ? $recipients = MAIL_RECIPIENTS : '';
    if (MAIL_AREA_ADMIN_ON_BOOKINGS)
    {
        empty($recipients) ? '' : $recipients .= ",";
        // Look for list of area admins emails addresses
        $sql = "SELECT a.area_admin_email ";
        $sql .= "FROM $tbl_room r, $tbl_area a, $tbl_entry e ";
        $sql .= "WHERE e.id=$new_id AND r.id=e.room_id AND a.id=r.area_id";
        $res = sql_query($sql);
        (! $res) ? fatal_error(1, sql_error()) : '';
        $row = sql_row($res, 0);
        sql_free($res);
        $recipients .= $row[0];
    }
    if (MAIL_ROOM_ADMIN_ON_BOOKINGS)
    {
        // Look for list of room admins emails addresses
        empty($recipients) ? '' : $recipients .= ",";
        $sql = "SELECT r.room_admin_email ";
        $sql .= "FROM $tbl_room r, $tbl_entry e ";
        $sql .= "WHERE e.id=$new_id AND r.id=e.room_id";
        $res = sql_query($sql);
        (! $res) ? fatal_error(1, sql_error()) : '';
        $row = sql_row($res, 0);
        sql_free($res);
        $recipients .= $row[0];
    }
    $result = sendMail($recipients, $subject, $body, MAIL_CC);
    return $result;
}

// }}}
// {{{ notifyAdminOnDelete()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param   array   $mail_previous  contains deleted entry data forr email body
 * @return  bool    TRUE or PEAR error object if fails
 */
function notifyAdminOnDelete($mail_previous)
{
    global $mail_vocab, $typel, $enable_periods;
    //
    $subject = $mail_vocab["mail_subject_delete"];
    $body = $mail_vocab["mail_body_del_entry"] . "\n\n";
    // Displays deleted entry details
    $body .= "\n" . $mail_vocab["namebooker"] . " ";
    $body .= removeMailUnicode($mail_previous['namebooker']) . "\n";
    $body .= $mail_vocab["description"] . " ";
    $body .= removeMailUnicode($mail_previous['description']) . "\n";
    $body .= $mail_vocab["room"] . ": ";
    $body .= removeMailUnicode($mail_previous['area_name']);
    $body .= " - " . removeMailUnicode($mail_previous['room_name']) . "\n";
    $body .= $mail_vocab["start_date"] . " ";
    if ( $enable_periods )
    {
        $body .= $mail_previous['start_period'] . " - ";
        $body .= unHtmlEntities($mail_previous['start_date']) . "\n";
    }
    else
    {
        $body .= $mail_vocab["start_date"] . " ";
        $body .= $mail_previous['start_date'] . "\n";
    }
    $body .= $mail_vocab["duration"] . " " . $mail_previous['duration'] . " ";
    $body .= $mail_previous['dur_units'] . "\n";
    if ( $enable_periods )
    {
        $body .= $mail_vocab["end_date"] . " ";
        $body .= $mail_previous['end_period'] . " - ";
        $body .= unHtmlEntities($mail_previous['end_date']) ."\n";
    }
    else
    {
        $body .= $mail_vocab["end_date"] . " " . $mail_previous['end_date'];
        $body .= "\n";
    }
    $body .= $mail_vocab["type"] . " ";
    $body .=  (empty($typel[$mail_previous['type']])) ? "?" .
        $mail_previous['type'] . "?" : $typel[$mail_previous['type']];
    $body .= "\n" . $mail_vocab["createdby"] . " ";
    $body .= $mail_previous['createdby'] . "\n";
    $body .= $mail_vocab["lastupdate"] . " " . $mail_previous['updated'];
    $body .= "\n" . $mail_vocab["rep_type"];
    $temp = $mail_previous['rep_type'];
    $body .=  " " . $mail_vocab["rep_type_$temp"] . "\n";
    // End of mail details
    $recipients = "";
    (MAIL_ADMIN_ON_BOOKINGS) ? $recipients = MAIL_RECIPIENTS : '';
    if (MAIL_AREA_ADMIN_ON_BOOKINGS)
    {
        empty($recipients) ? '' : $recipients .= ",";
        $sql = "SELECT a.area_admin_email ";
        $sql .= "FROM $tbl_room r, $tbl_area a, $tbl_entry e ";
        $sql .= "WHERE e.id=$new_id AND r.id=e.room_id AND a.id=r.area_id";
        $res = sql_query($sql);
        (! $res) ? fatal_error(1, sql_error()) : '';
        $row = sql_row($res, 0);
        sql_free($res);
        $recipients .= $row[0];
    }
    if (MAIL_ROOM_ADMIN_ON_BOOKINGS)
    {
        empty($recipients) ? '' : $recipients .= ",";
        $sql = "SELECT r.room_admin_email ";
        $sql .= "FROM $tbl_room r, $tbl_entry e ";
        $sql .= "WHERE e.id=$new_id AND r.id=e.room_id";
        $res = sql_query($sql);
        (! $res) ? fatal_error(1, sql_error()) : '';
        $row = sql_row($res, 0);
        sql_free($res);
        $recipients .= $row[0];
    }
    $result = sendMail($recipients, $subject, $body, MAIL_CC);
    return $result;
}

// }}}
// {{{ getPreviousEntryData()

/**
 * Gather all fields values for an entry. Used for emails to get previous
 * entry state.
 *
 * @param int     $id       entry id to get data
 * @param int     $series   1 if this is a serie or 0
 * @return bool             TRUE or PEAR error object if fails
 */
function getPreviousEntryData($id, $series)
{
    global $tbl_area, $tbl_entry, $tbl_repeat, $tbl_room, $enable_periods;
    //
    $sql = "
    SELECT  $tbl_entry.name,
            $tbl_entry.description,
            $tbl_entry.create_by,
            $tbl_room.room_name,
            $tbl_area.area_name,
            $tbl_entry.type,
            $tbl_entry.room_id,
            $tbl_entry.repeat_id, " .
            sql_syntax_timestamp_to_unix("$tbl_entry.timestamp") . ",
            ($tbl_entry.end_time - $tbl_entry.start_time) AS tbl_e_duration,
            $tbl_entry.start_time AS tbl_e_start_time,
            $tbl_entry.end_time AS tbl_e_end_time";
    // Here we could just use $tbl_repeat.start_time, and not use alias,
    // as the last column will take precedence using mysql_fetch_array,
    // but for portability purpose I will not use it.
    if (1 == $series)
    {
        $sql .= ", $tbl_repeat.rep_type, ($tbl_repeat.end_time -
            $tbl_repeat.start_time) AS tbl_r_duration,
            $tbl_repeat.start_time AS tbl_r_start_time,
            $tbl_repeat.end_date AS tbl_r_end_date";
    }
    $sql .= "
    FROM $tbl_entry, $tbl_room, $tbl_area, $tbl_repeat
    WHERE $tbl_entry.room_id = $tbl_room.id
    AND $tbl_room.area_id = $tbl_area.id
    AND $tbl_entry.id=$id";
    (1 == $series) ? $sql .= " AND $tbl_entry.repeat_id = $tbl_repeat.id" : '';
    //
    $res = sql_query($sql);
    (! $res) ? fatal_error(0, sql_error()) : '';
    (sql_count($res) < 1) ? fatal_error(0, get_vocab("invalid_entry_id")) : '';
    $row = sql_row_keyed($res, 0);
    sql_free($res);
    // Store all needed values in $mail_previous array to pass to
    // notifyAdminOnDelete function (shorter than individual variables -:) )
    $mail_previous['namebooker']    = $row['name'];
    $mail_previous['description']   = $row['description'];
    $mail_previous['createdby']     = $row['create_by'];
    $mail_previous['room_name']     = $row['room_name'];
    $mail_previous['area_name']     = $row['area_name'];
    $mail_previous['type']          = $row['type'];
    $mail_previous['room_id']       = $row['room_id'];
    $mail_previous['repeat_id']     = $row['repeat_id'];
    $mail_previous['updated']       = getMailTimeDateString($row[8]);
    // If we use periods
    if ( $enable_periods )
    {
        // If we delete a serie, start_time and end_time must
        // come from $tbl_repeat, not $tbl_entry.
        //
        // This is not a serie
        if (1 != $series)
        {
            list( $mail_previous['start_period'], $mail_previous['start_date'])
                =  getMailPeriodDateString($row['tbl_e_start_time']);
            list( $mail_previous['end_period'] , $mail_previous['end_date']) =
                getMailPeriodDateString($row['tbl_e_end_time'], 0);
            // need to make DST correct in opposite direction to entry creation
            // so that user see what he expects to see
            $mail_previous['duration'] = $row['tbl_e_duration'] -
                cross_dst($row['tbl_e_start_time'], $row['tbl_e_end_time']);
        }
        // This is a serie
        else
        {
            list( $mail_previous['start_period'], $mail_previous['start_date'])
                =  getMailPeriodDateString($row['tbl_r_start_time']);
            list( $mail_previous['end_period'] , $mail_previous['end_date']) =
                getMailPeriodDateString($row['tbl_r_end_date'], 0);
            // need to make DST correct in opposite direction to entry creation
            // so that user see what he expects to see
            $mail_previous['duration'] = $row['tbl_r_duration'] -
                cross_dst($row['tbl_r_start_time'], $row['tbl_r_end_date']);
        }
        toPeriodString($mail_previous['start_period'],
            $mail_previous['duration'], $mail_previous['dur_units']);
    }
    // If we don't use periods
    else
    {
        // This is not a serie
        if (1 != $series)
        {
            $mail_previous['start_date'] =
                getMailTimeDateString($row['tbl_e_start_time']);
            $mail_previous['end_date'] =
                getMailTimeDateString($row['tbl_e_end_time']);
            // need to make DST correct in opposite direction to entry creation
            // so that user see what he expects to see
            $mail_previous['duration'] = $row['tbl_e_duration'] -
                cross_dst($row['tbl_e_start_time'], $row['tbl_e_end_time']);
        }
        // This is a serie
        else
        {
            $mail_previous['start_date'] =
                getMailTimeDateString($row['tbl_r_start_time']);
            $mail_previous['end_date'] =
                getMailTimeDateString($row['tbl_r_end_date']);
            // need to make DST correct in opposite direction to entry creation
            // so that user see what he expects to see
            $mail_previous['duration'] = $row['tbl_r_duration'] -
                cross_dst($row['tbl_r_start_time'], $row['tbl_r_end_date']);
        }
        toTimeString($mail_previous['duration'], $mail_previous['dur_units']);
    }
    (1 == $series) ? $mail_previous['rep_type'] = $row['rep_type']
        : $mail_previous['rep_type'] = 0;
    // return entry previous data as an array
    return $mail_previous;
}

// }}}
// {{{ compareEntries()

/**
 * Compare entries fields to show in emails.
 *
 * @param string  $new_value       new field value
 * @param string  $previous_value  previous field value
 * @return string                  new value if no difference, new value and
 *                                 previous value in brackets otherwise
 */
function compareEntries($new_value, $previous_value, $new_entry)
{
    $suffix = "";
    if ($new_entry)
    {
        return $new_value;
    }
    if ($new_value != $previous_value)
    {
        $suffix = " ($previous_value)";
    }
    return($new_value . $suffix);
}

// }}}
// {{{ sendMail()

/**
 * Send emails using PEAR::Mail class.
 * How to use this class -> http://www.pear.php.net/package/Mail then link
 * "View documentation".
 * Currently implemented version: Mail 1.1.3 and its dependancies
 * Net_SMTP 1.2.6 and Net_Socket 1.0.2
 *
 * @param string  $recipients       comma separated list of recipients or array
 * @param string  $subject          email subject
 * @param string  $body             text message
 * @param string  $cc               Carbon Copy
 * @param string  $bcc              Blind Carbon Copy
 * @param string  $from             from field
 * @param string  $backend          'mail', 'smtp' or 'sendmail'
 * @param string  $sendmail_path    ie. "/usr/bin/sendmail"
 * @param string  $sendmail_args    ie. "-t -i"
 * @param string  $host             smtp server hostname
 * @param string  $port             smtp server port
 * @param string  $auth             smtp server authentication, TRUE/FALSE
 * @param string  $username         smtp server username
 * @param string  $password         smtp server password
 * @return bool                     TRUE or PEAR error object if fails
 */
function sendMail($recipients, $subject, $body, $cc = NULL, $bcc = NULL,
    $from = MAIL_FROM, $backend = MAIL_ADMIN_BACKEND, $sendmail_path =
    SENDMAIL_PATH, $sendmail_args = SENDMAIL_ARGS, $host = SMTP_HOST, $port =
    SMTP_PORT, $auth = SMTP_AUTH, $username = SMTP_USERNAME, $password =
    SMTP_PASSWORD)
{
    require_once "Mail.php";
    //
    $headers['From']         = $from;
    (NULL != $cc) ? $headers['Cc'] = $cc : '';
    (NULL != $bcc) ? $headers['Bcc'] = $bcc : '';
    $headers['Subject']      = $subject;
    $params['sendmail_path'] = $sendmail_path;
    $params['sendmail_args'] = $sendmail_args;
    $params['host']          = $host;
    $params['port']          = $port;
    $params['auth']          = $auth;
    $params['username']      = $username;
    $params['password']      = $password;
    //
    $mail_object =& Mail::factory($backend, $params);
    $result = $mail_object->send($recipients, $headers, $body);
    return $result;
}

// }}}
// {{{ unHtmlEntities()

/**
 * Convert all HTML entities to their applicable characters.
 * Added to remove HTML entities that are not suitable for plain text emails.
 * May be replaced by PHP function 'html_entity_decode()' but this function
 * only exist since PHP 4.3.0 and is buggy before PHP5.
 *
 * @param  string   $string     string to decode
 * @return string               decoded string
 */
function unHtmlEntities($string)
{
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}

// }}}
?>