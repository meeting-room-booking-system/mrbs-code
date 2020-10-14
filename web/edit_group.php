<?php
namespace MRBS;

require "defaultincludes.inc";

function generate_groups_table()
{
  global $auth;

  $groups = new Groups();

  echo "<table id=\"roles\">\n";

  echo "<thead>\n";
  echo "<tr>";
  if ($auth['type'] == 'db')
  {
    echo "<th>";
    // TODO 1. Implement delete button
    // generate_delete_button($group);
    echo "</th>";
  }
  echo "<th>" . htmlspecialchars(get_vocab('group')) . "</th>";
  echo "<tr>\n";
  echo "</thead>\n";

  echo "<tbody>\n";

  foreach ($groups as $group)
  {
    echo "<tr>";
    if ($auth['type'] == 'db')
    {
      echo "<td>";
      // TODO 1. Implement delete button
      // generate_delete_button($group);
      echo "</td>";
    }

    echo "<td>";
    $href = multisite(this_page() . '?group_id=' . $group->id);
    echo '<a href="' . htmlspecialchars($href). '">' . htmlspecialchars($group->name) . '</a>';
    echo "</td>";
    echo "</tr>\n";
  }

  echo "</tbody>\n";
  echo "</table>\n";
}


// Check the user is authorised for this page
checkAuthorised(this_page());

$context = array(
  'view'      => $view,
  'view_all'  => $view_all,
  'year'      => $year,
  'month'     => $month,
  'day'       => $day,
  'area'      => isset($area) ? $area : null,
  'room'      => isset($room) ? $room : null
);

print_header($context);

echo "<h2>" . htmlspecialchars(get_vocab('groups')) . "</h2>";

generate_groups_table();

print_footer();
