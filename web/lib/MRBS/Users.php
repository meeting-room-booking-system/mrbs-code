<?php
namespace MRBS;


class Users extends TableIterator
{

  private $role_names;


  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\User');
    $roles = new Roles();
    $this->role_names = $roles->getNames();
  }


  public function next()
  {
    $this->cursor++;

    if (false !== ($row = $this->res->next_row_keyed()))
    {
      // Convert the string of role ids into an array and also add an
      // array of role names
      $role_names = array();

      // If there are no roles, MySQL will return NULL and PostgreSQL ''.
      if (isset($row['roles']) && ($row['roles'] !== ''))
      {
        $row['roles'] = explode(',', $row['roles']);
        foreach ($row['roles'] as $role_id)
        {
          $role_names[] = $this->role_names[$role_id];
        }
      }
      else
      {
        $row['roles'] = array();
      }

      $row['role_names'] = $role_names;

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
    $sql = "SELECT U.*, " . db()->syntax_group_array_as_string('R.role_id') . " AS roles
              FROM $table_name U
         LEFT JOIN " . _tbl('user_role') . " R
                ON R.user_id=U.id
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

    // TODO Check for change in groups

    // Sort the external users by username
    $usernames = array_column($external_users, 'username');
    array_multisort($usernames, $external_users);

    // Loop through the external users and add them or update them as necessary
    foreach ($external_users as $external_user)
    {
      // Try and get the user from the database
      $sql = "SELECT name, display_name
                FROM " . _tbl(User::TABLE_NAME) . "
               WHERE name=:name
                 AND auth_type=:auth_type
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
        $user->save();
        $added[] = $external_user['display_name'];
      }
      else
      {
        // It's an existing user: check to see whether there's been any
        // change and, if so, update the database.
        $row = $res->next_row_keyed();
        if ($external_user['display_name'] != $row['display_name'])
        {
          $sql = "UPDATE " . _tbl(User::TABLE_NAME) . "
                     SET display_name=:display_name
                   WHERE name=:name";
          $sql_params = array(
              ':display_name' => $external_user['display_name'],
              ':name' => $external_user['username']
            );
          db()->command($sql, $sql_params);
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
}
