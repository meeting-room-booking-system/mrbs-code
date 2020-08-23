<!DOCTYPE html>
<html lang="en">
<head>
  <title>Language File Checker</title>
  <style>
    form {
      padding-left: 2em;
    }

    form div {
      display: flex;
      align-items: flex-start;
      margin: 0.8em 0;
    }

    label,
    input[type="checkbox"] {
      margin-right: 1em;
    }

    table {
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid black;
      padding: 0.2em 0.5em;
      text-align: left;
    }

    select {
      vertical-align: bottom;
    }
  </style>
</head>
<body>
<h1>Language File Checker</h1>
<p>
  This will report missing or untranslated strings in the language files.
</p>

<?php

// NOTE: You need to change this if you run checklang.php from anywhere but
// the MRBS 'web' directory
$path_to_mrbs = ".";

unset($lang);
$lang = array();

if (!empty($_GET))
{
  $lang = $_GET['lang'];
  $update = empty($_GET['update']) ? false : true;
}

// Language file prefix
$langs = "lang/lang.";

// Reference language:
$ref_lang = "en";

// Make a list of language files to check. This is similar to glob() in
// PEAR File/Find.
$dh = opendir($path_to_mrbs.'/lang');
while (($filename = readdir($dh)) !== false)
{
  $files[] = $filename;
}
closedir($dh);

sort($files);

?>

<form method="get" action="checklang.php">
  <div>
  <label for="languages">Select one or more languages:</label>
<select id="languages" multiple="multiple" size="5" name="lang[]">
<?php
foreach ($files as $filename)
{
  if (preg_match('/^lang\.(.*)/', $filename, $name) && $name[1] != $ref_lang)
  {
    if (preg_match('/~|\.bak|\.swp\$/', $name[1]))
    {
      continue;
    }
    print "<option";
    if (array_search($name[1], $lang) !== FALSE)
    {
      print " selected=\"selected\"";
    }
    print ">$name[1]</option>\n";
  }
}

?>
</select>
  </div>

<div>
<input id="update" type="checkbox" name="update">
<label for="update">Update file(s) with new token lines (web server user requires write permission
  on files and directory)</label>
</div>

<div>
<input type="submit" name="submit" value="Go">
</div>

</form>

<?php
include "$path_to_mrbs/$langs$ref_lang";
$ref = $vocab;

foreach ($lang as $l)
{
  unset($vocab);
  include "$path_to_mrbs/$langs$l";
  if ($update)
  {
    $ref_lines = array();
    $in = fopen("$path_to_mrbs/$langs$ref_lang", "r")
      or die("Failed to open $path_to_mrbs/$langs$ref_lang for reading\n");
    while (!feof($in))
    {
      $line = fgets($in);
      if (preg_match('/^\$vocab\["([^"]+)"\]/', $line, $matches))
      {
// DEBUG        print "MATCH $matches[1]<br>\n";
        $ref_lines[$matches[1]] = $line;
      }
    }
    fclose($in);
    $in = fopen("$path_to_mrbs/$langs$l", "r") or
      die("Failed to open $path_to_mrbs/$langs$l for reading");
    $out = fopen("$path_to_mrbs/$langs$l.new", "w") or
      die("Failed to open $path_to_mrbs/$langs$l.new for writing");

    $seen = array();
    $added = array();
// DEBUG    print "<table>\n";
    while (!feof($in))
    {
      $line = fgets($in);
      $token_match = "";
      if (preg_match('/^\$vocab\["([^"]+)"\]/', $line, $matches))
      {
// DEBUG        print "<tr><td>$matches[1]</td><td>".key($ref_lines);
        $token_match = $matches[1];

        if (!array_key_exists($token_match, $ref_lines))
        {
          fwrite($out, "// REMOVED - ".$line);
          continue;
        }
        while (($token_match != key($ref_lines)) &&
               (!array_key_exists(key($ref_lines), $vocab)))
        {
          if (array_key_exists(key($ref_lines), $seen))
          {
            break;
          }
          $seen[key($ref_lines)] = 1;
          fwrite($out, current($ref_lines));
          $added[] = htmlspecialchars(key($ref_lines));
          $ret = next($ref_lines);
// DEBUG          print " ".key($ref_lines);
          if (!$ret)
          {
            break;
          }
        }
        next($ref_lines);
      }
      $seen[$token_match] = 1;
      fwrite($out, $line);
// DEBUG      print "</td></tr>\n";
    }
    fclose($in);
    fclose($out);
// DEBUG    print "</table>\n";

    if (count($added))
    {
      print "Added the following tokens:\n<ul>\n<li>".
        implode("</li>\n<li>",$added)."</li>\n</ul>\n";
      rename("$path_to_mrbs/$langs$l", "$path_to_mrbs/$langs$l.old") or
        die("Failed to rename $path_to_mrbs/$langs$l to $path_to_mrbs/$langs$l.old");
      rename("$path_to_mrbs/$langs$l.new", "$path_to_mrbs/$langs$l") or
        die("Failed to rename $path_to_mrbs/$langs$l.new to $path_to_mrbs/$langs$l");

      // Re-read the updated file
      unset($vocab);
      include "$path_to_mrbs/$langs$l";
    }
    else
    {
      print "No token lines added.";
      unlink("$path_to_mrbs/$langs$l.new") or
        print "<span style=\"color: red; font-weight: bold\">
               Failed to delete $path_to_mrbs/$langs".htmlspecialchars($l).".new</span>.\n";
    }
  }
?>
<h2>Language: <?php echo htmlspecialchars($l) ?></h2>
<table>
  <tr>
    <th>Problem</th>
    <th>Key</th>
    <th>Value</th>
  </tr>
<?php
  $ntotal = 0;
  $nmissing = 0;
  $nunxlate = 0;

  foreach ($ref as $key => $val)
  {
    $ntotal++;
    $status = "";
    if (!isset($vocab[$key]))
    {
      $nmissing++;
      $status = "Missing";

    } else if (($key != "charset") &&
               ($vocab[$key] == $ref[$key]) &&
               ($ref[$key] != "") &&
               (!preg_match('/^mail_/', $key)))
    {
      $status = "Untranslated";
      $nunxlate++;
    }
    if ($status != "")
    {
      echo "  <tr><td>$status</td><td>" .
        htmlspecialchars($key) . "</td><td>" .
        htmlspecialchars($ref[$key]) . "</td></tr>\n";
    }
  }

  echo "</table>\n";
  echo "<p>Total entries in reference language file: $ntotal\n";
  echo "<br>For language file $l: ";
  if ($nmissing + $nunxlate == 0)
  {
    echo "no missing or untranslated entries.\n";
  }
  else
  {
    echo "missing: $nmissing, untranslated: $nunxlate.\n";
  }
  print "<hr>\n";
}

?>
</body>
</html>
