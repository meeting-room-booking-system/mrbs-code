<?

/** mrbsCheckFree()
 * 
 * Check to see if the time period specified is free
 * 
 * $room_id   - Which room are we checking
 * $starttime - The start of period
 * $endtime   - The end of the period
 * $ignore    - Any values to ignore (can be a single value, or an array)
 * 
 * Returns:
 *   nothing   - The area is free
 *   something - An error occured, the return value is human readable
 */
function mrbsCheckFree($room_id, $starttime, $endtime, $ignore)
{
	$sql = "SELECT id, name FROM mrbs_entry WHERE 
	(
	  ($starttime <= start_time AND $endtime   > start_time)
	 OR
	  ($endtime   >= end_time   AND $starttime < end_time  )
	 OR
	  ($starttime >= start_time AND $endtime  <= end_time  )
	)
	AND room_id = $room_id";
	
	if(isset($ignore))
	{
		if(gettype($ignore) == "array")
		{
			$sql .= " AND id NOT IN (";
			for($i = 0; $i < $ignore.count(); $i++)
			{
				if($i) $sql .= ", ";
				
				$sql .= $ignore[i];
			}
			$sql .= ")";
		}
		else
			$sql .= " AND id <> $ignore";
	}
	
	$res = mysql_query($sql);
	
	if(mysql_error())
		return mysql_error();
	
	if(mysql_num_rows($res) == 0)
		return;
	
	$err = $lang[conflict];
	
	while($row = mysql_fetch_row($res))
		$err .= "<br><a href=view_entry.php3?id=$row[0]>$row[1]</a>";
	
	return $err;
}

/** mrbsDelEntry()
 * 
 * Delete an entry, or optionally all entrys.
 * 
 * $user - Who's making the request
 * $id   - The entry to delete
 * $all  - If set, delete all entrys
 *
 * Returns:
 *   0        - An error occured
 *   non-zero - The entry was deleted
 */
function mrbsDelEntry($user, $id, $all)
{
	$res = mysql_query("SELECT repeat_id FROM mrbs_entry WHERE id=$id");
	if(mysql_num_rows($res) < 1)
		return 0;
	
	$row = mysql_fetch_row($res);
	$repeat_id = $row[0];
	
	$sql = "SELECT create_by, id, entry_type FROM mrbs_entry WHERE ";
	
	if($all)
		$sql .= "repeat_id=$repeat_id";
	else
		$sql .= "id=$id";
	
	$res = mysql_query($sql);
	
	$removed = 0;
	
	while($row = mysql_fetch_row($res))
	{
		if(!getWritable($row[0], $user))
			continue;
		
		if(($row[2] == 2) && $all)
			continue;
		
		$del_res = mysql_query("DELETE FROM mrbs_entry WHERE id=" . $row[1]);
		if(mysql_affected_rows())
			$removed++;
	}
	
	$res = mysql_query("SELECT id FROM mrbs_entry WHERE repeat_id=$repeat_id");
	if(mysql_num_rows($res) < 1)
		mysql_query("DELETE FROM mrbs_repeat WHERE id=$repeat_id");
	
	return $removed > 0;
}

/** mrbsCreateSingleEntry()
 * 
 * Create a single (non-repeating) entry in the database
 * 
 * $starttime   - Start time of entry
 * $endtime     - End time of entry
 * $entry_type  - Entry type
 * $repeat_id   - Repeat ID
 * $room_id     - Room ID
 * $owner       - Owner
 * $name        - Name
 * $type        - Type (Internal/External)
 * $description - Description
 * 
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id,
                               $owner, $name, $type, $description)
{
	$name        = addslashes($name);
	$description = addslashes($description);
	
	$sql = "INSERT INTO mrbs_entry (  start_time,   end_time,   entry_type,    repeat_id,   room_id,
	                                  create_by,    name,       type,          description)
	                        VALUES ('$starttime', '$endtime', '$entry_type', '$repeat_id', '$room_id',
	                                '$owner',     '$name',    '$type',       '$description')";
	
	$res = mysql_query($sql);
	
	return mysql_insert_id();
}

/** mrbsCreateRepeatEntry()
 * 
 * Creates a repeat entry in the data base
 * 
 * $starttime   - Start time of entry
 * $endtime     - End time of entry
 * $rep_type    - The repeat type
 * $rep_enddate - When the repeating ends
 * $rep_opt     - Any options associated with the entry
 * $room_id     - Room ID
 * $owner       - Owner
 * $name        - Name
 * $type        - Type (Internal/External)
 * $description - Description
 * 
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateRepeatEntry($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt,
                               $room_id, $owner, $name, $type, $description)
{
	$name        = addslashes($name);
	$description = addslashes($description);
	
	$sql = "INSERT INTO mrbs_repeat (  start_time,   end_time,    rep_type,    end_date,       rep_opt,
	                                   room_id,      create_by,   type,        name,           description)
	                         VALUES ('$starttime', '$endtime',  '$rep_type', '$rep_enddate', '$rep_opt',
	                                 '$room_id',   '$owner',    '$type',     '$name',        '$description')";
	
	mysql_query($sql);
	
	return mysql_insert_id();
}

/** mrbsGetRepeatEntryList
 * 
 * Returns a list of the repeating entrys
 * 
 * $time     - The start time
 * $enddate  - When the repeat ends
 * $rep_type - What type of repeat is it
 * $rep_opt  - The repeat entrys
 * $max_ittr - After going through this many entrys assume an error has occured
 * 
 * Returns:
 *   empty     - The entry does not repeat
 *   an array  - This is a list of start times of each of the repeat entrys
 */
function mrbsGetRepeatEntryList($time, $enddate, $rep_type, $rep_opt, $max_ittr)
{
	$sec   = date("s", $time);
	$min   = date("i", $time);
	$hour  = date("G", $time);
	$day   = date("d", $time);
	$month = date("m", $time);
	$year  = date("Y", $time);
	
	for($i = 0; ($time < $enddate) && ($i < $max_ittr); $i++)
	{
		$time = $entrys[$i] = mktime($hour, $min, $sec, $month, $day, $year);
		
		switch($rep_type)
		{
			// Daily repeat
			case 1:
				$day += 1;
				break;
			
			// Weekly repeat
			case 2:
				$cur_day = date("w", $entrys[$i]);

				for($j = $cur_day + 1; ($j != $cur_day) && !$rep_opt[$j]; $j = ($j + 1) % 7)
					$day += 1;
				
				$day += 1;
				
				break;
			
			// Monthly repeat
			case 3:
				$month += 1;
				break;
			
			// Yearly repeat
			case 4:
				$year += 1;
				break;
			
			// Unknown repeat option
			default:
				return;
		}
	}
	
	return $entrys;
}

/** mrbsCreateRepeatingEntrys()
 * 
 * Creates a repeat entry in the data base + all the repeating entrys
 * 
 * $starttime   - Start time of entry
 * $endtime     - End time of entry
 * $rep_type    - The repeat type
 * $rep_enddate - When the repeating ends
 * $rep_opt     - Any options associated with the entry
 * $room_id     - Room ID
 * $owner       - Owner
 * $name        - Name
 * $type        - Type (Internal/External)
 * $description - Description
 * 
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateRepeatingEntrys($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt,
                                   $room_id, $owner, $name, $type, $description)
{
	global $max_rep_entrys;
	
	$reps = mrbsGetRepeatEntryList($starttime, $rep_enddate, $rep_type, $rep_opt, $max_rep_entrys);
	if(count($reps) > $max_rep_entrys)
		return 0;
	
	if(empty($reps))
	{
		mrbsCreateSingleEntry($starttime, $endtime, 0, 0, $room_id, $owner, $name, $type, $description);
		return;
	}
	
	$ent = mrbsCreateRepeatEntry($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt, $room_id, $owner, $name, $type, $description);
	if(!$ent)
		return 0;
	
	$diff = $endtime - $starttime;
	
	for($i = 0; $i < count($reps); $i++)
		mrbsCreateSingleEntry($reps[$i], $reps[$i] + $diff, 1, $ent, $room_id, $owner, $name, $type, $description);
	
	return $ent;
}

/* mrbsGetEntryInfo()
 *
 * Get the booking's entrys
 * 
 * $id = The ID for which to get the info for.
 * 
 * Returns:
 *    nothing = The ID does not exist
 *    array   = The bookings info
 */
function mrbsGetEntryInfo($id)
{
	$sql = "SELECT start_time, end_time, entry_type, repeat_id, room_id,
	               timestamp, create_by, name, type, description
                FROM mrbs_entry WHERE (ID = $id)";
	
	$res = mysql_query($sql);
	
	if(mysql_num_rows($res) > 0)
	{
		$row = mysql_fetch_row($res);
		
		$ret[start_time]  = $row[0];
		$ret[end_time]    = $row[1];
		$ret[entry_type]  = $row[2];
		$ret[repeat_id]   = $row[3];
		$ret[room_id]     = $row[4];
		$ret[timestamp]   = $row[5];
		$ret[create_by]   = $row[6];
		$ret[name]        = $row[7];
		$ret[type]        = $row[8];
		$ret[description] = $row[9];
	}
	
	return $ret;
}

?>
