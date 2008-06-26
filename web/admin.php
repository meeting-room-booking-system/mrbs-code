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
$area_name = get_form_var('area_name', 'string');

// If we dont know the right date then make it up 
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

if (empty($area))
{
  $area = get_default_area();
}

if(!getAuthorised(2))
{
  showAccessDenied($day, $month, $year, $area);
  exit();
}

print_header($day, $month, $year, isset($area) ? $area : "");

// If area is set but area name is not known, get the name.
if (isset($area))
{
  if (empty($area_name))
  {
    $res = sql_query("select area_name from $tbl_area where id=$area");
    if (! $res) fatal_error(0, sql_error());
    if (sql_count($res) == 1)
    {
      $row = sql_row_keyed($res, 0);
      $area_name = $row['area_name'];
    }
    sql_free($res);
  }
}
?>

<h2><?php echo get_vocab("administration") ?></h2>

<table id="admin" class="admin_table">
  <thead>
    <tr>
      <th><?php echo get_vocab("areas") ?></th>
      <th>
	     <?php 
		  echo get_vocab("rooms");
	     if(isset($area_name))
		  { 
		    echo " " . get_vocab("in") . " " . htmlspecialchars($area_name); 
		  }
		  ?>
      </th>
    </tr>
  </thead>

  <tbody>
  <tr>
    <td>
<?php 
// This cell has the areas
$res = sql_query("select id, area_name from $tbl_area order by area_name");
if (! $res) fatal_error(0, sql_error());

if (sql_count($res) == 0)
{
  echo get_vocab("noareas");
}
else
{
  echo "      <ul>\n";
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $area_name_q = urlencode($row['area_name']);
    echo "        <li><a href=\"admin.php?area=".$row['id']."&amp;area_name=$area_name_q\">"
      . htmlspecialchars($row['area_name']) . "</a> (<a href=\"edit_area_room.php?area=".$row['id']."\">" . get_vocab("edit") . "</a>) (<a href=\"del.php?type=area&amp;area=".$row['id']."\">" .  get_vocab("delete") . "</a>)</li>\n";
  }
  echo "      </ul>\n";
}
?>
    </td>
    <td>
<?php
// This one has the rooms
if(isset($area))
{
  $res = sql_query("select id, room_name, description, capacity from $tbl_room where area_id=$area order by room_name");
  if (! $res)
  {
    fatal_error(0, sql_error());
  }
  if (sql_count($res) == 0)
  {
    echo get_vocab("norooms");
  }
  else
  {
    echo "      <ul>";
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
		echo "        <li>" . htmlspecialchars($row['room_name']) . "(" . htmlspecialchars($row['description'])
        . ", ".$row['capacity'].") (<a href=\"edit_area_room.php?room=".$row['id']."\">" . get_vocab("edit") . "</a>) (<a href=\"del.php?type=room&amp;room=".$row['id']."\">" . get_vocab("delete") . "</a>)</li>\n";
	 }
    echo "      </ul>";
  }
}
else
{
  echo get_vocab("noarea");
}

?>
    </td>
  </tr>
  <tr>
    <td>
      <form class="form_admin" action="add.php" method="post">
		  <fieldset>
		  <legend><?php echo get_vocab("addarea") ?></legend>
		  
          <input type="hidden" name="type" value="area">
			 
          <div>
            <label for="area_name"><?php echo get_vocab("name") ?>:</label>
            <input type="text" id="area_name" name="name">
		    </div>
			 
			 <div>
            <input type="submit" class="submit" value="<?php echo get_vocab("addarea") ?>">
		    </div>
		  </fieldset>
      </form>
    </td>

    <td>
<?php
if (0 != $area)
{
?>
      <form class="form_admin" action="add.php" method="post">
		  <fieldset>
		  <legend><?php echo get_vocab("addroom") ?></legend>
		  
        <input type="hidden" name="type" value="room">
        <input type="hidden" name="area" value="<?php echo $area; ?>">
		  
        <div>
          <label for="room_name"><?php echo get_vocab("name") ?>:</label>
          <input type="text" id="room_name" name="name">
        </div>
		  
		  <div>
          <label for="room_description"><?php echo get_vocab("description") ?>:</label>
          <input type="text" id="room_description" name="description">
        </div>
		  
		  <div>
          <label for="room_capacity"><?php echo get_vocab("capacity") ?>:</label>
          <input type="text" id="room_capacity" name="capacity">
        </div>
		 
		  <div>
          <input type="submit" class="submit" value="<?php echo get_vocab("addroom") ?>">
		  </div>
		  
		  </fieldset>
      </form>
<?php
}
else
{
  echo "&nbsp;";
}
?>
    </td>
  </tr>
  </tbody>
</table>
<p>
  <?php echo get_vocab("browserlang") . " " . $HTTP_ACCEPT_LANGUAGE . " " . get_vocab("postbrowserlang") ; ?>
</p>
<?php include "trailer.inc" ?>
