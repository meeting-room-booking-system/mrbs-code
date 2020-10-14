<?php
namespace MRBS;


class Users extends TableIterator
{

  private $names;

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\User');
    $this->names = array();
    $groups = new Groups();
    $this->names['groups'] = $groups->getNames();
    $roles = new Roles();
    $this->names['roles'] = $roles->getNames();
  }


  public function next()
  {
    $this->cursor++;

    if (false !== ($row = $this->res->next_row_keyed()))
    {
      $this->stringsToArrays($row);
      $this->item = new $this->base_class();
      $this->item->load($row);
    }
  }


  protected function getRes($sort_column = null)
  {
    global $auth;

    $class_name = $this->base_class;
    $table_name = _tbl($class_name::TABLE_NAME);
    $sql_params = array(':auth_type' => $auth['type']);
    $sql = "SELECT U.*, " . db()->syntax_group_array_as_string('R.role_id') . " AS roles,
                        " . db()->syntax_group_array_as_string('G.group_id') . " AS " . db()->quote('groups') . "
              FROM $table_name U
         LEFT JOIN " . _tbl('user_role') . " R
                ON R.user_id=U.id
         LEFT JOIN " . _tbl('user_group') . " G
                ON G.user_id=U.id
             WHERE U.auth_type=:auth_type
          GROUP BY U.id
          ORDER BY U.name";
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }


  // Sync users from an external source.
  public function sync($verbose=false)
  {
    global $auth;

    // Make sure this is a valid use of the method
    if (($auth['type'] == 'db') || !method_exists(auth(), 'getUsers'))
    {
      return;
    }

    // Get the external users
    if (false === ($ext_users = auth()->getUsers()))
    {
      return;
    }

    // Get the existing usernames
    $usernames = $this->getUsernames();
    // Get the external usernames
    $ext_usernames = array_column($ext_users, 'username');
    // Get the existing usernames that are no longer in the external source
    $old_names = array_values(array_diff($usernames, $ext_usernames));

    // TODO  Lock table
    $this->deleteUsers($old_names, $verbose);
    $this->upsertUsers($ext_users, $verbose);
    // TODO  Unlock table
  }


  private function getUsernames()
  {
    $result = array();
    $this->rewind();

    while ($this->valid())
    {
      $result[] = $this->current()->name;
      $this->next();
    }

    return $result;
  }


  private function deleteUsers(array $usernames, $verbose=false)
  {
    global $auth;

    $n_users = count($usernames);

    if ($n_users == 0)
    {
      if ($verbose)
      {
        echo get_vocab("sync_delete_no_users") . "\n\n";
      }
      return;
    }

    if ($verbose)
    {
      echo get_vocab("sync_deleting_n_users", $n_users) . "\n";
      echo implode("\n", $usernames);
      echo "\n\n";
    }

    $q_marks = str_repeat('?,', $n_users - 1) . '?';
    $sql = "DELETE
              FROM " . _tbl(User::TABLE_NAME) . "
             WHERE name IN ($q_marks)
               AND auth_type=?";
    $sql_params = $usernames;
    array_push($sql_params, $auth['type']);
    db()->command($sql, $sql_params);
  }


  private function upsertUsers(array $external_users, $verbose=false)
  {
    global $auth;

    if ($verbose)
    {
      $added = array();
      $updated = array();
    }

    // Loop through the external users and add them or update them as necessary
    foreach ($external_users as $external_user)
    {
      // Get the user's group ids
      $external_user['group_ids'] = array();
      foreach ($external_user['groups'] as $group_name)
      {
        $group_id = array_search($group_name, $this->names['groups']);
        // If the group doesn't exist then create it
        if ($group_id === false)
        {
          $group = new Group($group_name);
          $group->save();
          $group_id = $group->id;
          // and update the group names
          $this->names['groups'][$group_id] = $group_name;
        }
        $external_user['group_ids'][] = $group_id;
      }

      // Try and get the user from the database
      $sql = "SELECT U.name, U.display_name, " .
                     db()->syntax_group_array_as_string('G.group_id') . " AS " . db()->quote('groups') . "
                FROM " . _tbl(User::TABLE_NAME) . " U
           LEFT JOIN " . _tbl('user_group') . " G
                  ON G.user_id=U.id
               WHERE U.name=:name
                 AND auth_type=:auth_type
            GROUP BY U.id
               LIMIT 1";

      $sql_params = array(
          ':name' => $external_user['username'],
          ':auth_type' => $auth['type']
        );

      $res = db()->query($sql, $sql_params);

      if ($res->count() == 0)
      {
        // It's a new user: add them to the table
        $user = new User($external_user['username']);
        $user->display_name = $external_user['display_name'];
        $user->groups = $external_user['group_ids'];
        // Save the user to the database
        $user->save();
        $added[] = $external_user['display_name'];
      }
      else
      {
        // It's an existing user: check to see whether there's been any
        // change and, if so, update the database.
        // TODO: implement local groups and check for changes
        $row = $res->next_row_keyed();
        $this->stringsToArrays($row);
        if (($external_user['display_name'] != $row['display_name']) ||
            !array_values_equal($external_user['group_ids'], $row['groups']))
        {
          $user = User::getByName($row['name'], $auth['type']);
          $user->display_name = $external_user['display_name'];
          $user->groups = $external_user['group_ids'];
          // Save the user to the database
          $user->save();
          $updated[] = $external_user['display_name'];
        }
      }
    }

    // Output a summary
    if ($verbose)
    {
      $n_added = count($added);
      if ($n_added == 0)
      {
        echo get_vocab("sync_add_no_users");
      }
      else
      {
        echo get_vocab("sync_adding_n_users", $n_added) . "\n";
        echo implode("\n", $added);
      }
      echo "\n\n";

      $n_updated = count($updated);
      if ($n_updated == 0)
      {
        echo get_vocab("sync_update_no_users");
      }
      else
      {
        echo get_vocab("sync_updating_n_users", $n_updated) . "\n";
        echo implode("\n", $updated);
      }
      echo "\n\n";
    }
  }


  // Converts the result of db()->syntax_group_array_as_string() queries
  // back into arrays.
  private function stringsToArrays(&$row)
  {
    foreach (array('groups', 'roles') as $key)
    {
      // Convert the string of ids into an array and also add an
      // array of names
      if (array_key_exists($key, $row))
      {
        $names = array();

        // If there are no groups/roles, MySQL will return NULL and PostgreSQL ''.
        if (isset($row[$key]) && ($row[$key] !== ''))
        {
          $row[$key] = explode(',', $row[$key]);
          foreach ($row[$key] as $id)
          {
            $names[] = $this->names[$key][$id];
          }
        }
        else
        {
          $row[$key] = array();
        }

        // Sort the names
        sort($names, SORT_LOCALE_STRING | SORT_FLAG_CASE);

        // Add the names to the result
        switch ($key)
        {
          case 'groups':
            $row['group_names'] = $names;
            break;
          case 'roles':
            $row['role_names'] = $names;
            break;
          default:
            throw new \Exception("Unknown key '$key'");
            break;
        }
      }
    }
  }
}
