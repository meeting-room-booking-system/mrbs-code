<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\DB\DBException;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\Form;

require "defaultincludes.inc";


function generate_no_form(int $room, int $area) : void
{
  $form = new Form(Form::METHOD_POST);

  $attributes = array('action' => multisite('admin.php'));

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


function generate_yes_form(int $room, int $area) : void
{
  $form = new Form(Form::METHOD_POST);

  $attributes = array('action' => multisite('del.php'));

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
checkAuthorised(this_page());

// Get non-standard form variables
$type = get_form_var('type', 'string');
$confirm = get_form_var('confirm', 'string', null, INPUT_POST);

$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => $room ?? null
  );

// This is gonna blast away something. We want them to be really
// really sure that this is what they want to do.
if ($type == "room")
{
  // We are supposed to delete a room
  if (!empty($confirm))
  {
    // They have confirmed it already, so go blast!

    // Acquire mutex.
    if (!db()->mutex_lock(_tbl(Room::TABLE_NAME)))
    {
      fatal_error(get_vocab("failed_to_acquire"));
    }

    db()->begin();
    try
    {
      // First take out all appointments for this room
      $sql = "DELETE FROM " . _tbl('entry') . " WHERE room_id=?";
      db()->command($sql, array($room));

      $sql = "DELETE FROM " . _tbl('repeat') . " WHERE room_id=?";
      db()->command($sql, array($room));

      // Now take out the room itself
      Room::deleteById($room);
    }
    catch (DBException $e)
    {
      db()->rollback();
      db()->mutex_unlock(_tbl(Room::TABLE_NAME));
      throw $e;
    }

    db()->commit();

    // Unlock the table
    db()->mutex_unlock(_tbl(Room::TABLE_NAME));

    // Go back to the admin page
    location_header("admin.php?area=$area");
  }
  else
  {
    print_header($context);

    // We tell them how bad what they're about to do is
    // Find out how many appointments would be deleted
    // Do a quick count of the number of entries
    $n_entries = get_n_entries_by_room($room);

    if ($n_entries > 0)
    {
      $limit = 20;
      // Order in descending order because the latest bookings are probably the most important.
      $entries = get_entries_by_room($room, null, null, true, $limit);

      // We can't rely on ($n_entries > 0) because there's a very small chance the number of entries
      // may have changed between the two queries
      if (count($entries) > 0)
      {
        echo "<p>\n";
        echo get_vocab("deletefollowing") . ":\n";
        echo "</p>\n";

        echo "<ul>\n";

        foreach ($entries as $entry)
        {
          $interval = new EntryInterval($entry['start_time'], $entry['end_time'], $enable_periods);
          echo "<li>" . escape_html($entry['name']) . " (" . $interval . ")</li>\n";
        }

        echo "</ul>\n";
      }

      if ($n_entries > $limit)
      {
        echo "<p>";
        echo get_vocab("and_n_more", number_format_locale($n_entries - $limit)) . '.';
        echo "</p>";
      }
    }

    echo "<div id=\"del_room_confirm\">\n";
    echo "<p>" .  get_vocab("sure") . "</p>\n";

    generate_yes_form($room, $area);
    generate_no_form($room, $area);

    echo "</div>\n";
    print_footer();
    exit;
  }
}

if ($type == "area")
{
  // We are only going to let them delete an area if there are
  // no rooms, as it's easier.
  $rooms = new Rooms($area);

  if ($rooms->count() == 0)
  {
    // OK, nothing there, let's blast it away
    // Acquire mutex.
    if (!db()->mutex_lock(_tbl(Area::TABLE_NAME)))
    {
      fatal_error(get_vocab("failed_to_acquire"));
    }

    Area::deleteById($area);

    // Release the mutex
    db()->mutex_unlock(_tbl(Area::TABLE_NAME));
    // Redirect back to the admin page
    location_header('admin.php');
  }
  else
  {
    // There are rooms left in the area
    print_header($context);
    echo "<p>\n";
    echo get_vocab("delarea");
    $query_vars = array('area' => $area);
    $query = http_build_query($query_vars, '', '&');
    echo '<a href="' . escape_html(multisite("admin.php?$query")) . '">' . get_vocab('back') . '</a>';
    echo "</p>\n";
    print_footer();
    exit;
  }
}

throw new \Exception ("Unknown type");

