<?php
namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

print "<h2>pgsql</h2>";

$db_obj = DBFactory::create('pgsql', 'localhost', 'mrbs', 'mrbs-password', 'mrbs');
print "<p>".$db_obj->query1("SELECT VERSION()")."</p>";
print "<p>".$db_obj->version()."</p>";

print "<pre>".print_r($db_obj->field_info("mrbs_entry"), 1)."</pre>";

print "<h2>default db</h2>";

print "<p>".DB::default_db()->version()."</p>";

print "<p>".DB::default_db()->query1("SELECT 1+4")."</p>";

print "<pre>".print_r(DB::default_db()->field_info("mrbs_entry"), 1)."</pre>";
