<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementInputSubmit;

require "defaultincludes.inc";


function generate_no_form($room, $area)
{
  $form = new Form();
  
  $attributes = array('action' => 'admin.php',
                      'method' => 'post');
                      
  $form->setAttributes($attributes);

  // Hidden inputs
  $hidden_inputs = array('area' => $area,
                         'room' => $room);
  $form->addHiddenInputs($hidden_inputs);
  
  // The button
  $element = new ElementInputSubmit();
  $element->setAttribute('value', get_vocab("NO"));
  $form->addElement($element);

  $form->render();
}


function generate_yes_form($room, $area)
{
  $form = new Form();
  
  $attributes = array('action' => 'del.php',
                      'method' => 'post');
                      
  $form->setAttributes($attributes);
  
  // Hidden inputs
  $hidden_inputs = array('type'    => 'room',
                         'area'    => $area,
                         'room'    => $room,
                         'confirm' => '1');
  $form->addHiddenInputs($hidden_inputs);
  
  // The button
  $element = new ElementInputSubmit();
  $element->setAttribute('value', get_vocab("YES"));
  $form->addElement($element);

  $form->render();
}


// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised();

// Get non-standard form variables
$type = get_form_var('type', 'string');
$confirm = get_form_var('confirm', 'string', null, INPUT_POST);

// This is gonna blast away something. We want them to be really
// really sure that this is what they want to do.

if ($type == "room")
{
  // We are supposed to delete a room
  if (!empty($confirm))
  {
    // They have confirmed it already, so go blast!
    db()->begin();
    try
    {
      // First take out all appointments for this room
      db()->command("DELETE FROM $tbl_entry WHERE room_id=?", array($room));
      db()->command("DELETE FROM $tbl_repeat WHERE room_id=?", array($room));
      // Now take out the room itself
      db()->command("DELETE FROM $tbl_room WHERE id=?", array($room));
    }
    catch (DBException $e)
    {
      db()->rollback();
      throw $e;
    }
   
    db()->commit();
   
    // Go back to the admin page
    header("Location: admin.php?area=$area");
    exit;
  }
  else
  {
    print_header($day, $month, $year, $area, isset($room) ? $room : null);
   
    // We tell them how bad what they're about to do is
    // Find out how many appointments would be deleted
    $limit = 20;
    
    $sql = "SELECT COUNT(*) FROM $tbl_entry WHERE room_id=?";
    $n_bookings = db()->query1($sql, array($room));
    
    // The LIMIT parameter should ideally be one of the parameters to the
    // query, but MySQL throws an error at the moment because it gets bound
    // as a string.  Doesn't matter in this case because we know where $limit
    // has come from, but for the general case MRBS needs to provide the ability
    // to bind it as an integer.
    //
    // Order in descending order because the latest bookings are probably the most
    // important.
    $sql = "SELECT name, start_time, end_time
              FROM $tbl_entry WHERE room_id=?
          ORDER BY start_time DESC
             LIMIT $limit";
    $res = db()->query($sql, array($room));
    
    if ($res->count() > 0)
    {
      echo "<p>\n";
      echo get_vocab("deletefollowing") . ":\n";
      echo "</p>\n";
      
      echo "<ul>\n";
      
      for ($i = 0; ($row = $res->row_keyed($i)); $i++)
      {
        echo "<li>".htmlspecialchars($row['name'])." (";
        echo time_date_string($row['start_time']) . " -> ";
        echo time_date_string($row['end_time']) . ")</li>\n";
      }
      
      echo "</ul>\n";
    }
    
    if ($n_bookings > $limit)
    {
      echo "<p>";
      echo get_vocab("and_n_more", number_format_locale($n_bookings - $limit)) . '.';
      echo "</p>";
    }
   
    echo "<div id=\"del_room_confirm\">\n";
    echo "<p>" .  get_vocab("sure") . "</p>\n";
    
    generate_yes_form($room, $area);
    generate_no_form($room, $area);

    echo "</div>\n";
    output_trailer();
    exit;
  }
}

if ($type == "area")
{
  // We are only going to let them delete an area if there are
  // no rooms. its easier
  $n = db()->query1("SELECT COUNT(*) FROM $tbl_room WHERE area_id=?", array($area));
  if ($n == 0)
  {
    // OK, nothing there, lets blast it away
    db()->command("DELETE FROM $tbl_area WHERE id=?", array($area));
   
    // Redirect back to the admin page
    header("Location: admin.php");
    exit;
  }
  else
  {
    // There are rooms left in the area
    print_header($day, $month, $year, $area, isset($room) ? $room : null);
    echo "<p>\n";
    echo get_vocab("delarea");
    echo "<a href=\"admin.php\">" . get_vocab("backadmin") . "</a>";
    echo "</p>\n";
    output_trailer();
    exit;
  }
}

throw new \Exception ("Unknown type");

