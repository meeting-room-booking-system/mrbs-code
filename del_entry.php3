<?

include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

if(!getAuthorised(getUserName(), getUserPassword()))
{
?>
<HTML>
 <HEAD>
  <META HTTP-EQUIV="REFRESH" CONTENT="5; URL=index.php3">
  <TITLE><?echo $lang[mrbs]?></TITLE>
  <?include "config.inc"?>
  <?include "style.inc"?>
 <BODY>
  <H1><?echo $lang[accessdenied]?></H1>
  <P>
   <?echo $lang[unandpw]?>
  </P>
  <P>
   <a href=<? echo $HTTP_REFERER; ?>><? echo $lang[returnprev]; ?></a>
  </P>
</HTML>
<?
	exit;
}

if ( $id > 0 ) {
	$res = mysql_query("select create_by from mrbs_entry where id='$id'");
	$row = mysql_fetch_row($res);
	
	if(mysql_error())
	{
		echo mysql_error();
		exit;
	}
	
	if(getWritable($row[0], getUserName(), $auth[admin]))
	{
		mysql_query("DELETE FROM mrbs_entry WHERE id = $id");
		Header("Location: day.php3?day=$day&month=$month&year=$year");
	}
}

// If you got this far then we got an access denied.
?>

<HTML>
<HEAD>
<TITLE><?echo $lang[mrbs]?></TITLE>
<?include "style.inc"?>
<H1><?echo $lang[accessdenied]?></H1>
<P>
  <?echo $lang[norights]?>
</P>
<P>
  <a href=<? echo $HTTP_REFERER; ?>><? echo $lang[returnprev]; ?></a>
</P>
