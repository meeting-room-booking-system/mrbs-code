<?

# $Id$

# This script initialises the database with some random data
require_once "grab_globals.inc.php";
include "config.inc.php";
include "$dbsys.inc";

# The sample data has an office in Tokyo. We have an array of Japanese
# Names and other names
$jpnames = array("Keiko Yanatu","Kichiemon Wagosuch","Keijiro Takeyari","Kideki Takanoda","Keiko Nikushin","Kumiko Itasikat","Kei Reifuzin","Keiju Ginpatsu","Kumiko Doitsure","Kiyohiko Sopu","Akihiko Hasikawa","Kiyoshi Boygeorg","Kideki Kenpon","Keijiro Yunotai","Kazuko Yakushid","Kenji Rekihon","Kin Hiratsuk","Keijiro Hatsushu","Aki Seijirou","Kin Zassijou","Masahaki Genshiri","Akihiko Osaka","Kimiko Syutten","Kiyohiko Kareitek","Yoshihiro Shinj","Yukio Ginka","Kiyoshi Discman","Kenji Ganjyou","Akiki Iiawase","Kideki Mufuu","Tadashi Ueha","Tadao Tenkinsa","Kiwako Chuuu","Kin Zensha","Masahaki Naitatsu","Yukio Shishior","Kiyohiko Onyoudou","Akim Sougohok","Akim Yamaguch","Kimiko Tasuka","Kenji Keibouda","Kinya Misosiru","Keiji Bumontan","Keiko Syouduka","Ake Jikoatek","Keiko Tushima","Tadeusz Siosai","Yoshihiro Chishinj","Akiyo Setsusek","Yoshihiro Kanrense");
$ennames = array("Manart Sanders","Achim Oconnor","Enea Powell","Lam Turner","Rodrigo Fisher","Stuart Edmond Walker","Bogdan Hill","Laudie Hartman","Liesl Howard","Peaches Snyder","Reiko Arnold","Jece Dean","George H Brooks","Tsjundo Campbell","Rosalvo Jackson","Christione Price","Whitman Wright","Maine Baker","Father Mathews","Daphine Taylor","Nikolaus Santos","Louise Closser Hunt","Hoke Brooks","Rondo Ford","Charles Bud Sullivan","Gian Maria Griffin","Hensy Sullivan","Angela Punch Kelley","Predrag Williams","Clarence Williams Phillips","Yacht Club Baker","Dermot Campbell","Pai Edwards","Maria Lucia Hall","Goeran Sanders","Jean-Yves Griffin","Leon Isaac Frost","Kin Andrews","Suradej Woods","Bess Christensen","Danelle Patterson","Janusz King","Kumiko Miller","Jonn Henderson","Norrie Clark","Cliff Mcdonald","Suzie Randolph","Audrie Phillips","Aldine Allison","Leung Shing Sullivan","Hitomi Gonzales","Bobbie Stone","Aliki Andrews","Susanne Cooper","Rosana Brown","Petula Simpson","Lee Taylor Mathews","Tenniel Peck","Carole Bryan","Raymundo Young","Alvarez Lisa Fisher","Marthe Hartman","Chaim Alvarez","Jennings Harrison","Karih Johnson","Tedd Rivers","Tono Barnes","Madeline Holmes","Cecile Walker","Jom Armstrong","Sydne Sullivan","Jannik Harvey","Thierry Jensen","Von Morris","Y'aiter Cook","Sheik Renal Sullivan","Charlita Griffin","Baki Hansen","Liana Campbell","Josefina Romero","Francesca Rice","Lizardo Wood","Stelio Baker","Pisamai Howard","Sinikka Powell","Karim Taylor","Dan Crawford","Emmett Pappy Morris","Cluaude Hunt","Gene Holmes","Virginia True Warner","Kynaston Ford","Marki Kelley","Dominguez Brothers Ford","Pepe Peterson","Mitch Taylor","Cory Bumper Gonzales","Irene Yah Ling Frost","Giuditta Allen","Brit Simpson");
$intext[1] = "I";
$intext[2] = "E";

mt_srand((double)microtime()*1000000);


#Lets do stuff for days 5 days in the past to 55 days in the future

for ($day = date("d") - 5; $day < date("d")+55; $day++) {
 $month = date("m");
 $year = date("Y");

 $dayt = date("D",mktime(0,0,0,$month,$day,$year));
 if ($dayt <> "Sat" and $dayt <> "Sun") {

  $sql = "select id from mrbs_area";
  $area_res = sql_query($sql);
  for ($i = 0; (list($area) = sql_row($area_res, $i)); $i++) {
	  # We know the area we want to add appointments in
	  $sql = "select id from mrbs_room where area_id = $area";
	  $room_res = sql_query($sql);
	  if (!$room_res) echo sql_error();
	  for ($j = 0; (list($room) = sql_row($room_res, $j)); $j++) {
		  # Now we know room and area
		  # We have to add some appointments to the day
		  # four in each room seems good enough
		  for ($a = 1; $a < 5; $a++) {
			  # Pick a random hour 8-5
			  $starthour = mt_rand(7,16);
			  $length = mt_rand(1,5) * 30;
			  $starttime = mktime($starthour, 0, 0, $month, $day, $year);
			  $endtime   = mktime($starthour, $length, 0, $month, $day, $year);

			  #Check that this isnt going to overlap
			  $sql = "select count(*) from mrbs_entry where room_id=$room and ((start_time between $starttime and $endtime) or (end_time between $starttime and $endtime) or (start_time = $starttime and end_time = $endtime))";
			  $counte = sql_query1($sql);
			  if ($counte == 0) {
				  #There are no overlaps
				  if ($area == 4) {
					  $name = $jpnames[mt_rand(1,count($jpnames)-1)];
				  } else {
					  $name = $ennames[mt_rand(1,count($ennames)-1)];
				  }
				  $type = $intext[mt_rand(1,2)];
				  $sql = "insert into mrbs_entry (room_id, create_by, start_time, end_time, type, name, description) values ($room, '$REMOTE_ADDR', $starttime, $endtime,'$type','$name','A meeting')";
				  sql_command($sql);
			  }
			  echo "$area - $room ($starthour,$length), $type<br>";
		  }
	  }
  }
 }
}

?>