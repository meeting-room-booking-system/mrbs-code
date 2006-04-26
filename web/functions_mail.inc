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
 * @param   timestamp   $t         timestamp to format
 * @param   boolean     $inc_time  include time in return string
 * @return  string                 formated string
 */
function getMailTimeDateString($t, $inc_time=TRUE)
{
    global $twentyfourhour_format;
    // This bit's necessary, because it seems %p in strftime format
    // strings doesn't work
    $ampm = date("a",$t);
    if ($inc_time)
    {
        if ($twentyfourhour_format)
        {
            return strftime("%H:%M:%S - %A %d %B %Y",$t);
        }
        else
        {
            return strftime("%I:%M:%S$ampm - %A %d %B %Y",$t);
        }
    }
    else
    {
        return strftime("%A %d %B %Y",$t);
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
    global $rep_opt, $rep_num_weeks;
    global $tbl_room, $tbl_area, $tbl_entry, $tbl_users, $tbl_repeat;
    global $mail_previous, $auth;
    
    //
    $recipients = '';
    $id_table = ($rep_type > 0) ? "rep" : "e";
    (MAIL_ADMIN_ON_BOOKINGS) ? $recipients = MAIL_RECIPIENTS : '';
    if (MAIL_AREA_ADMIN_ON_BOOKINGS)
    {
        // Look for list of area admins emails addresses
        if ($new_entry)
        {
            $sql = "SELECT a.area_admin_email ";
            $sql .= "FROM $tbl_room r, $tbl_area a, $tbl_entry e ";
            // If this is a repeating entry...
            if ($id_table == 'rep')
            {
                // ...use the repeat table
                $sql .= ", $tbl_repeat rep ";
            }
            $sql .= "WHERE ${id_table}.id=$new_id AND r.id=${id_table}.room_id AND a.id=r.area_id";
            $res = sql_query($sql);
            (! $res) ? fatal_error(0, sql_error()) : '';
            $row = sql_row($res, 0);
            if ( !empty($recipients) && (NULL != $row[0]) )
            {
                $recipients .= ',';
            }
            if (NULL != $row[0])
            {
                $recipients .= $row[0];
            }
        }
        else
        // if this is an edited entry, we already have area_admin_email,
        // avoiding a database hit.
        {
           if ( !empty($recipients) && ('' != $mail_previous['area_admin_email']) )
           {
               $recipients .= ',';
           }
           if ('' != $mail_previous['area_admin_email'])
           {
               $recipients .= $mail_previous['area_admin_email'];
           }
        }
    }
    if (MAIL_ROOM_ADMIN_ON_BOOKINGS)
    {
        // Look for list of room admins emails addresses
        if ($new_entry)
        {
            $sql = "SELECT r.room_admin_email ";
            $sql .= "FROM $tbl_room r, $tbl_entry e ";
            // If this is a repeating entry...
            if ($id_table == 'rep')
            {
                // ...use the repeat table
                $sql .= ", $tbl_repeat rep ";
            }
            $sql .= "WHERE ${id_table}.id=$new_id AND r.id=${id_table}.room_id";
            $res = sql_query($sql);
            (! $res) ? fatal_error(0, sql_error()) : '';
            $row = sql_row($res, 0);
            if ( !empty($recipients) && (NULL != $row[0]) )
            {
                $recipients .= ',';
            }
            if (NULL != $row[0])
            {
                $recipients .= $row[0];
            }
        }
        else
        // if this is an edited entry, we already have room_admin_email,
        // avoiding a database hit.
        {
           if ( !empty($recipients) && ('' != $mail_previous['room_admin_email']) )
           {
               $recipients .= ',';
           }
           if ('' != $mail_previous['room_admin_email'])
           {
               $recipients .= $mail_previous['room_admin_email'];
           }
        }
    }
    if (MAIL_BOOKER)
    {
        if ('db' == $auth['type'])
        {
            /* It would be possible to move this query within the query in
               getPreviousEntryData to have all in one central place and to
               reduce database hits by one. However this is a bad idea. If a
               user is deleted from your user database, this will prevent all
               mails to admins when this user previously booked entries will
               be changed, as no user name will match the booker name */
            $sql = "SELECT email FROM $tbl_users WHERE name='";
            $sql .= ($new_entry) ? $create_by : $mail_previous['createdby'];
            $sql .= "'";
            $res = sql_query($sql);
            (! $res) ? fatal_error(0, sql_error()) : '';
            $row = sql_row($res, 0);
            if ( !empty($recipients) && (NULL != $row[0]) )
            {
                $recipients .= ',';
            }
            if (NULL != $row[0])
            {
                $recipients .= $row[0];
            }
        }
        else
        {
            if ($new_entry)
            {
                if ( !empty($recipients) && ('' != $create_by) )
                {
                    $recipients .= ',';
                }
                if ('' != $create_by)
                {
                    $recipients .= str_replace(MAIL_USERNAME_SUFFIX, '',
                        $create_by) . MAIL_DOMAIN;
                }
            }
            else
            {
                if ( !empty($recipients) && ('' != $mail_previous['createdby']) )
                {
                    $recipients .= ',';
                }
                if ('' != $mail_previous['createdby'])
                {
                    $recipients .= str_replace(MAIL_USERNAME_SUFFIX, '',
                        $mail_previous['createdby']) . MAIL_DOMAIN;
                }
            }
        }
    }
    // In case $recipients is empty, no need to go further
    if ('' == $recipients)
    {
        return FALSE;
    }
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
        $body .= "$url_base/view_entry.php?id=$new_id";
    }
    else
    {
        ('' != $returl) ? $url = explode(basename($returl), $returl) : '';
        $body .= $url[0] . "view_entry.php?id=$new_id";
    }
    if ($rep_type > 0)
    {
        $body .= "&series=1";
    }
    $body .= "\n";
    // Displays/don't displays entry details
    if (MAIL_DETAILS)
    {
        $body .= "\n" . $mail_vocab["namebooker"] . " ";
        $body .= compareEntries(removeMailUnicode($name),
            removeMailUnicode($mail_previous['namebooker']), $new_entry)  . "\n";
        
        // Description:
        $body .= $mail_vocab["description"] . " ";
        $body .= compareEntries(removeMailUnicode($description),
            removeMailUnicode($mail_previous['description']), $new_entry) . "\n";
        
        // Room:
        $body .= $mail_vocab["room"] . ": " .
            compareEntries(removeMailUnicode($area_name),
            removeMailUnicode($mail_previous['area_name']), $new_entry);
        $body .= " - " . compareEntries(removeMailUnicode($room_name),
            removeMailUnicode($mail_previous['room_name']), $new_entry) . "\n";
        
        // Start time
        if ( $enable_periods )
        {
            list( $start_period, $start_date) =
                getMailPeriodDateString($starttime);
            $body .= $mail_vocab["start_date"] . " ";
            $body .= compareEntries(unHtmlEntities($start_date),
                unHtmlEntities($mail_previous['start_date']), $new_entry) . "\n";
        }
        else
        {
            $start_date = getMailTimeDateString($starttime);
            $body .= $mail_vocab["start_date"] . " " .
                compareEntries($start_date, $mail_previous['start_date'], $new_entry) . "\n";
        }
        
        // Duration
        $body .= $mail_vocab["duration"] . " " .
            compareEntries($duration, $mail_previous['duration'], $new_entry);
        $body .= " " . compareEntries($mail_vocab["$dur_units"],
            $mail_previous['dur_units'], $new_entry) . "\n";
        
        // End time
        if ( $enable_periods )
        {
            $myendtime = $endtime;
            $mod_time = -1;
            list($end_period, $end_date) =  getMailPeriodDateString($myendtime, $mod_time);
            $body .= $mail_vocab["end_date"] . " ";
            $body .= compareEntries(unHtmlEntities($end_date),
                unHtmlEntities($mail_previous['end_date']), $new_entry) ."\n";
        }
        else
        {
            $myendtime = $endtime;
            $end_date = getMailTimeDateString($myendtime);
            $body .= $mail_vocab["end_date"] . " " .
                compareEntries($end_date, $mail_previous['end_date'], $new_entry) . "\n";
        }
        
        // Type of booking
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
        
        // Created by
        $body .= "\n" . $mail_vocab["createdby"] . " " .
            compareEntries($create_by, $mail_previous['createdby'], $new_entry) . "\n";
        
        // Last updated
        $body .= $mail_vocab["lastupdate"] . " " .
            compareEntries(getMailTimeDateString(time()), $mail_previous['updated'], $new_entry);
        
        // Repeat Type
        $body .= "\n" . $mail_vocab["rep_type"];
        if ($new_entry)
        {
            $body .= " " . $mail_vocab["rep_type_$rep_type"];
        }
        else
        {
            $temp = $mail_previous['rep_type'];
            $body .=  " " . compareEntries($mail_vocab["rep_type_$rep_type"],
                $mail_vocab["rep_type_$temp"], $new_entry);
        }
        
        // Details if a series
        if ($rep_type > 0)
        {
	    $opt = "";
	    if (($rep_type == 2) || ($rep_type == 6))
	    {
		# Display day names according to language and preferred weekday start.
		for ($i = 0; $i < 7; $i++)
		{
			$daynum = ($i + $weekstarts) % 7;
			if ($rep_opt[$daynum]) $opt .= day_name($daynum) . " ";
		}
	    }
	    if ($rep_type == 6)
	    {
		$body .= "\n" . $mail_vocab["rep_num_weeks"];
		$body .=  ": " . compareEntries($rep_num_weeks, $mail_previous["rep_num_weeks"], $new_entry);
	    }
	
	    if($opt || $mail_previous["rep_opt"])
	    {
		$body .= "\n" . $mail_vocab["rep_rep_day"];
		$body .=  " " . compareEntries($opt, $mail_previous["rep_opt"], $new_entry);
	    }

            $body .= "\n" . $mail_vocab["rep_end_date"];
            if ($new_entry)
            {
                $body .= " " . utf8_strftime('%A %d %B %Y',$rep_enddate);
            }
            else
            {
                $temp = utf8_strftime('%A %d %B %Y',$rep_enddate);
                $body .=  " " . 
                    compareEntries($temp, $mail_previous['rep_end_date'], $new_entry) . "\n";
            }
        }
	$body .= "\n";
    }
    $result = sendMail($recipients, $subject, $body, $mail_vocab['charset'] ,MAIL_CC);
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
    global $mail_vocab, $typel, $enable_periods, $auth, $tbl_users;
    //
    $recipients = '';
    (MAIL_ADMIN_ON_BOOKINGS) ? $recipients = MAIL_RECIPIENTS : '';
    if (MAIL_AREA_ADMIN_ON_BOOKINGS)
    {
        if ( !empty($recipients) && ('' != $mail_previous['area_admin_email']) )
        {
            $recipients .= ',';
        }
        if ('' != $mail_previous['area_admin_email'])
        {
            $recipients .= $mail_previous['area_admin_email'];
        }
    }
    if (MAIL_ROOM_ADMIN_ON_BOOKINGS)
    {
        if ( !empty($recipients) && ('' != $mail_previous['room_admin_email']) )
        {
            $recipients .= ',';
        }
        if ('' != $mail_previous['room_admin_email'])
        {
            $recipients .= $mail_previous['room_admin_email'];
        }
    }
    if (MAIL_BOOKER)
    {
        if ('db' == $auth['type'])
        {
            /* It would be possible to move this query within the query in
               getPreviousEntryData to have all in one central place and to
               reduce database hits by one. However this is a bad idea. If a
               user is deleted from your user database, this will prevent all
               mails to admins when this user previously booked entries will
               be changed, as no user name will match the booker name */
            $sql = "SELECT email
                    FROM $tbl_users
                    WHERE name='" . $mail_previous['createdby'] . "'";
            $res = sql_query($sql);
            (! $res) ? fatal_error(0, sql_error()) : '';
            $row = sql_row($res, 0);
            if ( !empty($recipients) && (NULL != $row[0]) )
            {
                $recipients .= ',';
            }
            if (NULL != $row[0])
            {
                $recipients .= $row[0];
            }
        }
        else
        {
            if ( !empty($recipients) && ('' != $mail_previous['createdby']) )
            {
                $recipients .= ',';
            }
            if ('' != $mail_previous['createdby'])
            {
                $recipients .= str_replace(MAIL_USERNAME_SUFFIX, '',
                    $mail_previous['createdby']) . MAIL_DOMAIN;
            }
        }
    }
    // In case mail is allowed but someone forgot to supply email addresses...
    if ('' == $recipients)
    {
        return FALSE;
    }
    //
    $subject = $mail_vocab["mail_subject_delete"];
    $body = $mail_vocab["mail_body_del_entry"] . "\n\n";
    // Displays deleted entry details
    $body .= "\n" . $mail_vocab["namebooker"] . ' ';
    $body .= removeMailUnicode($mail_previous['namebooker']) . "\n";
    $body .= $mail_vocab["description"] . " ";
    $body .= removeMailUnicode($mail_previous['description']) . "\n";
    $body .= $mail_vocab["room"] . ": ";
    $body .= removeMailUnicode($mail_previous['area_name']);
    $body .= " - " . removeMailUnicode($mail_previous['room_name']) . "\n";
    $body .= $mail_vocab["start_date"] . ' ';
    if ( $enable_periods )
    {
        $body .= unHtmlEntities($mail_previous['start_date']) . "\n";
    }
    else
    {
        $body .= $mail_previous['start_date'] . "\n";
    }
    $body .= $mail_vocab["duration"] . ' ' . $mail_previous['duration'] . ' ';
    $body .= $mail_previous['dur_units'] . "\n";
    if ( $enable_periods )
    {
        $body .= $mail_vocab["end_date"] . " ";
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
    $body .=  " " . $mail_vocab["rep_type_$temp"];
    if ($mail_previous['rep_type'] > 0)
    {
        if ($mail_previous['rep_type'] == 6)
        {
           $body .= "\n" . $mail_vocab["rep_num_weeks"];
           $body .=  ": " . $mail_previous["rep_num_weeks"];
        }
   
        if($mail_previous["rep_opt"])
        {
           $body .= "\n" . $mail_vocab["rep_rep_day"];
           $body .=  " " . $mail_previous["rep_opt"];
        }

        $body .= "\n" . $mail_vocab["rep_end_date"];
        $body .=  " " . $mail_previous['rep_end_date'] . "\n";
    }
    $body .= "\n";
    // End of mail details
    $result = sendMail($recipients, $subject, $body, $mail_vocab['charset'], MAIL_CC);
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
    SELECT  e.name,
            e.description,
            e.create_by,
            r.room_name,
            a.area_name,
            e.type,
            e.room_id,
            e.repeat_id, " .
            sql_syntax_timestamp_to_unix("e.timestamp") . ",
            (e.end_time - e.start_time) AS tbl_e_duration,
            e.start_time AS tbl_e_start_time,
            e.end_time AS tbl_e_end_time,
            a.area_admin_email,
            r.room_admin_email";
    // Here we could just use $tbl_repeat.start_time, and not use alias,
    // as the last column will take precedence using mysql_fetch_array,
    // but for portability purpose I will not use it.
    if (1 == $series)
    {
        $sql .= ", re.rep_type, re.rep_opt, re.rep_num_weeks,
            (re.end_time - re.start_time) AS tbl_r_duration,
            re.start_time AS tbl_r_start_time,
            re.end_time AS tbl_r_end_time,
            re.end_date AS tbl_r_end_date";
    }
    $sql .= "
    FROM $tbl_entry e, $tbl_room r, $tbl_area a ";
    (1 == $series) ? $sql .= ', ' . $tbl_repeat . ' re ' : '';
    $sql .= "
    WHERE e.room_id = r.id
    AND r.area_id = a.id
    AND e.id=$id";
    (1 == $series) ? $sql .= " AND e.repeat_id = re.id" : '';
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
    $mail_previous['area_admin_email'] = $row['area_admin_email'];
    $mail_previous['room_admin_email'] = $row['room_admin_email'];
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
                getMailPeriodDateString($row['tbl_e_end_time'], -1);
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
                getMailPeriodDateString($row['tbl_r_end_time'], 0);
            // use getMailTimeDateString as all I want is the date
	    $mail_previous['rep_end_date'] =
                getMailTimeDateString($row['tbl_r_end_date'], FALSE);
            // need to make DST correct in opposite direction to entry creation
            // so that user see what he expects to see
            $mail_previous['duration'] = $row['tbl_r_duration'] -
                cross_dst($row['tbl_r_start_time'], $row['tbl_r_end_time']);
	    
	    $mail_previous['rep_opt'] = "";
	    switch($row['rep_type'])
	    {
		case 2:
		case 6:
			$rep_day[0] = $row['rep_opt'][0] != "0";
			$rep_day[1] = $row['rep_opt'][1] != "0";
			$rep_day[2] = $row['rep_opt'][2] != "0";
			$rep_day[3] = $row['rep_opt'][3] != "0";
			$rep_day[4] = $row['rep_opt'][4] != "0";
			$rep_day[5] = $row['rep_opt'][5] != "0";
			$rep_day[6] = $row['rep_opt'][6] != "0";

			if ($row['rep_type'] == 6)
			{
				$mail_previous['rep_num_weeks'] = $row['rep_num_weeks'];
			}
			else
			{
				$mail_previous['rep_num_weeks'] = "";
			}
			
			break;
		
		default:
			$rep_day = array(0, 0, 0, 0, 0, 0, 0);
	    }
	    for ($i = 0; $i < 7; $i++)
	    {
		$wday = ($i + $weekstarts) % 7;
		if ($rep_day[$wday])
		    $mail_previous['rep_opt'] .= day_name($wday) . " ";
	    }
	    
	    $mail_previous['rep_num_weeks'] = $row['rep_num_weeks'];
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
                getMailTimeDateString($row['tbl_r_end_time']);
            // use getMailTimeDateString as all I want is the date
	    $mail_previous['rep_end_date'] =
                getMailTimeDateString($row['tbl_r_end_date'], FALSE);
            // need to make DST correct in opposite direction to entry creation
            // so that user see what he expects to see
            $mail_previous['duration'] = $row['tbl_r_duration'] -
                cross_dst($row['tbl_r_start_time'], $row['tbl_r_end_time']);
            
	    $mail_previous['rep_opt'] = "";
	    switch($row['rep_type'])
	    {
		case 2:
		case 6:
			$rep_day[0] = $row['rep_opt'][0] != "0";
			$rep_day[1] = $row['rep_opt'][1] != "0";
			$rep_day[2] = $row['rep_opt'][2] != "0";
			$rep_day[3] = $row['rep_opt'][3] != "0";
			$rep_day[4] = $row['rep_opt'][4] != "0";
			$rep_day[5] = $row['rep_opt'][5] != "0";
			$rep_day[6] = $row['rep_opt'][6] != "0";

			if ($row['rep_type'] == 6)
			{
				$mail_previous['rep_num_weeks'] = $row['rep_num_weeks'];
			}
			else
			{
				$mail_previous['rep_num_weeks'] = "";
			}
			
			break;
		
		default:
			$rep_day = array(0, 0, 0, 0, 0, 0, 0);
	    }
	    for ($i = 0; $i < 7; $i++)
	    {
		$wday = ($i + $weekstarts) % 7;
		if ($rep_day[$wday])
		    $mail_previous['rep_opt'] .= day_name($wday) . " ";
	    }
	    
	    $mail_previous['rep_num_weeks'] = $row['rep_num_weeks'];
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
 * @param string  $charset          character set used in body
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
function sendMail($recipients, $subject, $body, $charset = 'us-ascii',
    $cc = NULL, $bcc = NULL, $from = MAIL_FROM, $backend = MAIL_ADMIN_BACKEND,
    $sendmail_path = SENDMAIL_PATH, $sendmail_args = SENDMAIL_ARGS,
    $host = SMTP_HOST, $port = SMTP_PORT, $auth = SMTP_AUTH,
    $username = SMTP_USERNAME, $password = SMTP_PASSWORD)
{
    require_once "Mail.php";

    // Headers part
    $headers['From']         = $from;
    if( $backend != 'mail' ) {
        $headers['To']           = $recipients;
    }
    (NULL != $cc) ? $headers['Cc'] = $cc : '';
    (NULL != $bcc) ? $headers['Bcc'] = $bcc : '';
    $headers['Subject']      = $subject;
    $headers['MIME-Version'] = '1.0';
    $headers['Content-Type'] = 'text/plain; charset=' . $charset;

    // Parameters part
    if( $backend == 'sendmail' ) {
        $params['sendmail_path'] = $sendmail_path;
        $params['sendmail_args'] = $sendmail_args;
    }
    if( $backend == "smtp" ) {
        $params['host']          = $host;
        $params['port']          = $port;
        $params['auth']          = $auth;
        $params['username']      = $username;
        $params['password']      = $password;
    }

    // Call to the PEAR::Mail class
    $mail_object =& Mail::factory($backend, $params);
    $result = $mail_object->send($recipients, $headers, $body);

    if (is_object($result))
    {
      error_log("Error sending email: ".$result->getMessage());
    }
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
