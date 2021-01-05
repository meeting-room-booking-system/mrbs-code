<?php
namespace MRBS;


class Users extends TableIterator
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\User');
  }


  public function next()
  {
    $this->cursor++;

    if (false !== ($row = $this->res->next_row_keyed()))
    {
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
      $user = User::getByName($external_user['username'], $auth['type']);

      if (!isset($user))
      {
        // It's a new user: create one
        $user = new User($external_user['username']);
      }

      // TODO: implement local groups and check for changes
      if (!isset($user->id) || $user->hasChanged($external_user))
      {
        // Update the new/changed user
        $user->display_name = $external_user['display_name'];
        $user->email = (isset($external_user['email'])) ? $external_user['email'] : null;
        $user->groups = $external_user['groups'];
        $user->level = $external_user['level'];
        // Update the statistics.  Do this before saving the user because a save() will
        // give the user anb id.
        if ($verbose)
        {
          if (!isset($user->id))
          {
            $added[] = $user->display_name;
          }
          else
          {
            $updated[] = $user->display_name;
          }
        }
        // Save the user to the database
        $user->save();
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
