<?php
// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');
$room_name = get_form_var('room_name', 'string');
$area_name = get_form_var('area_name', 'string');
$description = get_form_var('description', 'string');
$capacity = get_form_var('capacity', 'int');
$room_admin_email = get_form_var('room_admin_email', 'string');
$area_admin_email = get_form_var('area_admin_email', 'string');
$change_done = get_form_var('change_done', 'string');
$change_room = get_form_var('change_room', 'string');
$change_area = get_form_var('change_area', 'string');

// If we dont know the right date then make it up
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

if (!getAuthorised(2))
{
  showAccessDenied($day, $month, $year, $area, "");
  exit();
}

// Done changing area or room information?
if (isset($change_done))
{
  if (!empty($room)) // Get the area the room is in
  {
    $area = sql_query1("SELECT area_id from $tbl_room where id=$room");
  }
  Header("Location: admin.php?day=$day&month=$month&year=$year&area=$area");
  exit();
}

print_header($day, $month, $year, isset($area) ? $area : "", isset($room) ? $room : "");

?>

<h2><?php echo get_vocab("editroomarea") ?></h2>

<?php
if (!empty($room))
{
  include_once 'Mail/RFC822.php';
  (!isset($room_admin_email)) ? $room_admin_email = '': '';
  $emails = explode(',', $room_admin_email);
  $valid_email = TRUE;
  $email_validator = new Mail_RFC822();
  foreach ($emails as $email)
  {
    // if no email address is entered, this is OK, even if isValidInetAddress
    // does not return TRUE
    if ( !$email_validator->isValidInetAddress($email, $strict = FALSE)
         && ('' != $room_admin_email) )
    {
      $valid_email = FALSE;
    }
  }
  //
  if ( isset($change_room) && (FALSE != $valid_email) )
  {
    if (empty($capacity))
    {
      $capacity = 0;
    }
    $sql = "UPDATE $tbl_room SET room_name='" . addslashes($room_name)
      . "', description='" . addslashes($description)
      . "', capacity=$capacity, room_admin_email='"
      . addslashes($room_admin_email) . "' WHERE id=$room";
    if (sql_command($sql) < 0)
    {
      fatal_error(0, get_vocab("update_room_failed") . sql_error());
    }
  }

  $res = sql_query("SELECT * FROM $tbl_room WHERE id=$room");
  if (! $res)
  {
    fatal_error(0, get_vocab("error_room") . $room . get_vocab("not_found"));
  }
  $row = sql_row_keyed($res, 0);
  sql_free($res);
?>

<form class="form_edit_area_room" action="edit_area_room.php" method="post">
  <fieldset class="admin">
  <legend><?php echo get_vocab("editroom") ?></legend>
  
    <fieldset>
    <legend></legend>
      <span class="error">
         <?php echo ((FALSE == $valid_email) ? get_vocab('invalid_email') : "&nbsp;"); ?>
      </span>
    </fieldset>
    
    <input type="hidden" name="room" value="<?php echo $row["id"]?>">
    
    <div>
    <label for="room_name"><?php echo get_vocab("name") ?>:</label>
    <input type="text" id="room_name" name="room_name" value="<?php echo htmlspecialchars($row["room_name"]); ?>">
    </div>
    
    <div>
    <label for="description"><?php echo get_vocab("description") ?>:</label>
    <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($row["description"]); ?>"> 
    </div>
    
    <div>
    <label for="capacity"><?php echo get_vocab("capacity") ?>:</label>
    <input type="text" id="capacity" name="capacity" value="<?php echo $row["capacity"]; ?>">
    </div>
    
    <div>
    <label for="room_admin_email"><?php echo get_vocab("room_admin_email") ?>:</label>
    <input type="text" id="room_admin_email" name="room_admin_email" maxlength="75" value="<?php echo htmlspecialchars($row["room_admin_email"]); ?>">
    </div>
    
    <fieldset class="submit_buttons">
    <legend></legend>
      <input type="submit" name="change_room" value="<?php echo get_vocab("change") ?>">
      <input type="submit" name="change_done" value="<?php echo get_vocab("backadmin") ?>">
    </fieldset>
    
  </fieldset>
</form>

<?php
}
?>

<?php
if (!empty($area))
{
  include_once 'Mail/RFC822.php';
  (!isset($area_admin_email)) ? $area_admin_email = '': '';
  $emails = explode(',', $area_admin_email);
  $valid_email = TRUE;
  $email_validator = new Mail_RFC822();
  foreach ($emails as $email)
  {
    // if no email address is entered, this is OK, even if isValidInetAddress
    // does not return TRUE
    if ( !$email_validator->isValidInetAddress($email, $strict = FALSE)
         && ('' != $area_admin_email) )
    {
      $valid_email = FALSE;
    }
  }
  //
  if ( isset($change_area) && (FALSE != $valid_email) )
  {
    $sql = "UPDATE $tbl_area SET area_name='" . addslashes($area_name)
      . "', area_admin_email='" . addslashes($area_admin_email)
      . "' WHERE id=$area";
    if (sql_command($sql) < 0)
    {
      fatal_error(0, get_vocab("update_area_failed") . sql_error());
    }
  }

  $res = sql_query("SELECT * FROM $tbl_area WHERE id=$area");
  if (! $res)
  {
    fatal_error(0, get_vocab("error_area") . $area . get_vocab("not_found"));
  }
  $row = sql_row_keyed($res, 0);
  sql_free($res);
?>

<form class="form_edit_area_room" action="edit_area_room.php" method="post">
  <fieldset class="admin">
  <legend><?php echo get_vocab("editarea") ?></legend>
  
    <fieldset>
    <legend></legend>
      <span class="error">
         <?php echo ((FALSE == $valid_email) ? get_vocab('invalid_email') : "&nbsp;"); ?>
      </span>
    </fieldset>
  
    <input type="hidden" name="area" value="<?php echo $row["id"]?>">
    
    <div>
    <label for="area_name"><?php echo get_vocab("name") ?>:</label>
    <input type="text" id="area_name" name="area_name" value="<?php echo htmlspecialchars($row["area_name"]); ?>">
    </div>
    
    <div>
    <label for="area_admin_email"><?php echo get_vocab("area_admin_email") ?>:</label>
    <input type="text" id="area_admin_email" name="area_admin_email" maxlength="75" value="<?php echo htmlspecialchars($row["area_admin_email"]); ?>">
    </div>
    
    <fieldset class="submit_buttons">
    <legend></legend>
      <input type="submit" name="change_area" value="<?php echo get_vocab("change") ?>">
      <input type="submit" name="change_done" value="<?php echo get_vocab("backadmin") ?>">
    </fieldset>
    
  </fieldset>
</form>
<?php
}
?>
<?php include "trailer.inc" ?>
