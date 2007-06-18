<html>
<head><title>Language File Checker</title></head>
<body>
<h1>Language File Checker</h1>
<p>
This will report missing or untranslated strings in the language files.
Access this script with a parameter lang=xx to see the results for
language xx only (for example,
http://localhost/mrbs/checklang.php?lang=fr).
If you do not supply a lang=xx parameter, all languages will be checked.

<?php

# NOTE: You need to change this if you run checklang.php from anywhere but
# the MRBS 'web' directory
$path_to_mrbs = ".";

include "$path_to_mrbs/config.inc.php";

# Checklang 2001-01-28 ljb - Check MRBS language files for completeness.
# This is a rather straightforward job. For each language file, report
# on any missing or untranslated strings with respect to the reference
# file.
# Parameter lang=xx can be supplied, to just check that language; by
# default all languages are checked.

unset($lang);

if (!empty($_GET))
{
	$lang = $_GET['lang'];
}
else if (!empty($HTTP_GET_VARS))
{
	$lang = $HTTP_GET_VARS['lang'];
}

# Language file prefix
$langs = "lang.";

# Reference language:
$ref_lang = "en";

if (isset($lang))
{
	$check[0] = $lang;
	unset($lang);
}
else {
  # Make a list of language files to check. This is similar to glob() in
  # PEAR File/Find.
  $dh = opendir($path_to_mrbs);
  while (($filename = readdir($dh)) !== false)
  {
    $files[] = $filename;
  }
  closedir($dh);
  
  sort($files);
  
  foreach ($files as $filename)
  {
    if (ereg("^lang\\.(.*)", $filename, $name) && $name[1] != $ref_lang)
    {
      $check[] = $name[1];
    }
  }
}

include "$path_to_mrbs/$langs$ref_lang";
$ref = $vocab;

reset($check);
while (list(,$l) = each($check))
{
	unset($vocab);
	include "$path_to_mrbs/$langs$l";
?>
<h2>Language: <?php echo $l ?></h2>
<table border=1>
  <tr>
    <th>Problem</th>
    <th>Key</th>
    <th>Value</th>
  </tr>
<?php
	$ntotal = 0;
	$nmissing = 0;
	$nunxlate = 0;
	reset($ref);
	while (list($key, $val) = each($ref))
	{
		$ntotal++;
		$status = "";
		if (!isset($vocab[$key]))
		{
			$nmissing++;
			$status = "Missing";

		} elseif (($key != "charset") &&
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
	if ($nmissing + $nunxlate == 0) echo "no missing or untranslated entries.\n";
	else echo "missing: $nmissing, untranslated: $nunxlate.\n";
}

?>
</body>
</html>
