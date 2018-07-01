<?php

namespace MRBS;

// Use our own PHP session handling by storing sessions in the database.   This has three advantages:
//    (a) it's more secure, especially on shared servers
//    (b) it avoids problems with ordinary sessions not working because the PHP session save
//        directory is not writable
//    (c) it's more resilient in clustered environments

class SessionHandlerDb implements \SessionHandlerInterface
{
  
  public function open($save_path , $session_name)
  {
    return true;
  }

  
  public function close()
  {
    return true;
  }

  
  public function read($session_id)
  {
    global $tbl_sessions;
    
    $sql = "SELECT data
              FROM $tbl_sessions
             WHERE id=:id
             LIMIT 1";
             
    $result = db()->query1($sql, array(':id' => $session_id));
    
    return ($result === -1) ? '' : $result;
  }

  
  public function write($session_id , $session_data)
  {
    global $tbl_sessions;
    
    $sql = "SELECT COUNT(*) FROM $tbl_sessions WHERE id=:id LIMIT 1";
    $rows = db()->query1($sql, array(':id' => $session_id));
    
    if ($rows > 0)
    {
      $sql = "UPDATE $tbl_sessions
                 SET data=:data, access=:access
               WHERE id=:id";
    }
    else
    {
      // The id didn't exist so we have to INSERT it (we couldn't use
      // REPLACE INTO because we have to cater for both MySQL and PostgreSQL)
      $sql = "INSERT INTO $tbl_sessions
                          (id, data, access)
                   VALUES (:id, :data, :access)";
    }
                 
    $sql_params = array(':id' => $session_id,
                        ':data' => $session_data,
                        ':access' => time());
    
    db()->command($sql, $sql_params);
    
    return true;
  }

  
  public function destroy($session_id)
  {
    global $tbl_sessions;
    
    $sql = "DELETE FROM $tbl_sessions WHERE id=:id";
    $rows = $rows = db()->command($sql, array(':id' => $session_id));
    return ($rows === 1);
  }

  
  public function gc($maxlifetime)
  {
    global $tbl_sessions;
    
    $sql = "DELETE FROM $tbl_sessions WHERE access<:old"; 
    db()->command($sql, array(':old' => time() - $maxlifetime));  
    return true;  // An exception will be thrown on error
  }
}
