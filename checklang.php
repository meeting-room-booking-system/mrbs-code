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

# Checklang 2001-01-28 ljb - Check MRBS language files for completeness.
# This is a rather straightforward job. For each language file, report
# on any missing or untranslated strings with respect to the reference
# file.
# Parameter lang=xx can be supplied, to just check that language; by
# default all languages are checked.

# Language file prefix. This can include a path, e.g. "../mrbs/lang."
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
  $dh = opendir(".");
  while ($filename = readdir($dh))
	  if (ereg("^lang\\.(.*)", $filename, $name) && $name[1] != $ref_lang)
	  $check[] = $name[1];
  closedir($dh);
}

include "$langs$ref_lang";
$ref = $vocab;

reset($check);
while (list(,$l) = each($check))
{
	unset($lang);
	include "$langs$l";
	echo "<h2>Language: $l</h2>\n";
	echo "<p><pre>\n";
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

		} elseif ($vocab[$key] == $ref[$key] && $ref[$key] != "")
		{
			$status = "Untranslated";
			$nunxlate++;
		}
		if ($status != "")
		{
			echo '$vocab["' . htmlspecialchars($key) . '"]  =  "'
				. htmlspecialchars($ref[$key]) . "\"; # $status\n";
		}
	}
	echo "<pre><p>\n";
	echo "<p>Total entries in reference language file: $ntotal\n";
	echo "<br>For language file $l: ";
	if ($nmissing + $nunxlate == 0) echo "no missing or untranslated entries.\n";
	else echo "missing: $nmissing, untranslated: $nunxlate.\n";
}

?>
</body>
</html>
