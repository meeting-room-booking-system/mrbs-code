<?

// db settings are in config.inc

// Establish a database connection.
$c = mysql_pconnect ( $mysql_host, $mysql_login, $mysql_password );
if ( ! $c ) {
  echo "Error connecting to database!<P>";
  exit;
}

if ( ! mysql_select_db ( $mysql_database ) ) {
  echo "Error selecting \"$mysql_database\" database!<P>" . mysql_error ();
  exit;
}


?>
