<?
// $Id$

// This script initialises the database with some random data
require_once("grab_globals.inc.php");
require "config.inc.php";
require_once("database.inc.php");
MDB::loadFile("Date");
require "$dbsys.inc";

// The sample data has an office in Tokyo. We have an array of Japanese
// Names and other names
$jpnames = array("Keiko Yanatu","Kichiemon Wagosuch","Keijiro Takeyari","Kideki Takanoda","Keiko Nikushin","Kumiko Itasikat","Kei Reifuzin","Keiju Ginpatsu","Kumiko Doitsure","Kiyohiko Sopu","Akihiko Hasikawa","Kiyoshi Boygeorg","Kideki Kenpon","Keijiro Yunotai","Kazuko Yakushid","Kenji Rekihon","Kin Hiratsuk","Keijiro Hatsushu","Aki Seijirou","Kin Zassijou","Masahaki Genshiri","Akihiko Osaka","Kimiko Syutten","Kiyohiko Kareitek","Yoshihiro Shinj","Yukio Ginka","Kiyoshi Discman","Kenji Ganjyou","Akiki Iiawase","Kideki Mufuu","Tadashi Ueha","Tadao Tenkinsa","Kiwako Chuuu","Kin Zensha","Masahaki Naitatsu","Yukio Shishior","Kiyohiko Onyoudou","Akim Sougohok","Akim Yamaguch","Kimiko Tasuka","Kenji Keibouda","Kinya Misosiru","Keiji Bumontan","Keiko Syouduka","Ake Jikoatek","Keiko Tushima","Tadeusz Siosai","Yoshihiro Chishinj","Akiyo Setsusek","Yoshihiro Kanrense");
$ennames = array("Manart Sanders","Achim Oconnor","Enea Powell","Lam Turner","Rodrigo Fisher","Stuart Edmond Walker","Bogdan Hill","Laudie Hartman","Liesl Howard","Peaches Snyder","Reiko Arnold","Jece Dean","George H Brooks","Tsjundo Campbell","Rosalvo Jackson","Christione Price","Whitman Wright","Maine Baker","Father Mathews","Daphine Taylor","Nikolaus Santos","Louise Closser Hunt","Hoke Brooks","Rondo Ford","Charles Bud Sullivan","Gian Maria Griffin","Hensy Sullivan","Angela Punch Kelley","Predrag Williams","Clarence Williams Phillips","Yacht Club Baker","Dermot Campbell","Pai Edwards","Maria Lucia Hall","Goeran Sanders","Jean-Yves Griffin","Leon Isaac Frost","Kin Andrews","Suradej Woods","Bess Christensen","Danelle Patterson","Janusz King","Kumiko Miller","Jonn Henderson","Norrie Clark","Cliff Mcdonald","Suzie Randolph","Audrie Phillips","Aldine Allison","Leung Shing Sullivan","Hitomi Gonzales","Bobbie Stone","Aliki Andrews","Susanne Cooper","Rosana Brown","Petula Simpson","Lee Taylor Mathews","Tenniel Peck","Carole Bryan","Raymundo Young","Alvarez Lisa Fisher","Marthe Hartman","Chaim Alvarez","Jennings Harrison","Karih Johnson","Tedd Rivers","Tono Barnes","Madeline Holmes","Cecile Walker","Jom Armstrong","Sydne Sullivan","Jannik Harvey","Thierry Jensen","Von Morris","Y'aiter Cook","Sheik Renal Sullivan","Charlita Griffin","Baki Hansen","Liana Campbell","Josefina Romero","Francesca Rice","Lizardo Wood","Stelio Baker","Pisamai Howard","Sinikka Powell","Karim Taylor","Dan Crawford","Emmett Pappy Morris","Cluaude Hunt","Gene Holmes","Virginia True Warner","Kynaston Ford","Marki Kelley","Dominguez Brothers Ford","Pepe Peterson","Mitch Taylor","Cory Bumper Gonzales","Irene Yah Ling Frost","Giuditta Allen","Brit Simpson");
$intext[1] = "I";
$intext[2] = "E";

mt_srand((double)microtime()*1000000);


// Lets do stuff for days 5 days in the past to 55 days in the future

$sql_1 = "SELECT id FROM mrbs_area";
$prepared_query_1 = $mdb->prepareQuery($sql_1);
$sql_2 = "SELECT id FROM mrbs_room WHERE area_id = ?";
$prepared_query_2 = $mdb->prepareQuery($sql_2);
$sql_3 = "SELECT count(*) FROM mrbs_entry WHERE room_id = ?
         AND ((start_time between ? and ?)
         OR (end_time between ? and ?)
         OR (start_time = ? and end_time = ?))";
$prepared_query_3 = $mdb->prepareQuery($sql_3);
$sql_4 = "INSERT INTO mrbs_entry (id, room_id, create_by, start_time,
		 end_time, type, name, description, timestamp)
         VALUES (?, ?, ? , ?, ?, ?, ?, ?, ?)";
$prepared_query_4 = $mdb->prepareQuery($sql_4);
for ($day = date("d") - 5; $day < date("d")+55; $day++)
{
	$month = date("m");
 	$year = date("Y");

	$dayt = date("D",mktime(0,0,0,$month,$day,$year));
	if ($dayt <> "Sat" and $dayt <> "Sun")
    {

        $area_res = $mdb->executeQuery($prepared_query_1, 'integer');
  		while (list($area) = $mdb->fetchInto($area_res))
		{
	  		// We know the area we want to add appointments in
            $mdb->setParamInteger($prepared_query_2, 1, $area);
            $room_res = $mdb->executeQuery($prepared_query_2, 'integer');
      		if (MDB::isError($room_res))
      		{
            	echo $room_res->getMessage() . "<BR>";
                echo $room_res->getUserInfo();
            }
            while (list($room) = $mdb->fetchInto($room_res))
            {
		  		// Now we know room and area
				// We have to add some appointments to the day
		  		// four in each room seems good enough
		  		for ($a = 1; $a < 5; $a++)
                {
			  		// Pick a random hour 8-5
			  		$starthour = mt_rand(7,16);
			  		$length = mt_rand(1,5) * 30;
			  		$starttime = mktime($starthour, 0, 0, $month, $day, $year);
			  		$endtime   = mktime($starthour, $length, 0, $month, $day, $year);

			  		//Check that this isnt going to overlap
                    $sql_3_data = array($room, $starttime, $endtime, $starttime,
                    				    $endtime, $starttime, $endtime);
					$param_types = array('integer', 'integer', 'integer', 'integer',
                    	           'integer', 'integer', 'integer');
                    $res2 = $mdb->execute($prepared_query_3, 'integer',
                    						$sql_3_data, $param_types);
                    $counte = $mdb->fetchOne($res2);
                    if (0 == $counte)
                    {
				  		//There are no overlaps
				  		if (4 == $area)
                        {
					  		$name = $jpnames[mt_rand(1,count($jpnames)-1)];
				  		}
                        else
                        {
					  		$name = $ennames[mt_rand(1,count($ennames)-1)];
				  		}
				  		$type = $intext[mt_rand(1,2)];
                  		$id = $mdb->nextId('mrbs_entry_id');
                        $sql_4_data = array($id, $room, $REMOTE_ADDR, $starttime,
                        					$endtime, $type, $name, 'A meeting',
                                            MDB_Date::mdbNow());
                        $param_types = array('integer', 'integer', 'text', 'integer',
                        					 'integer', 'text', 'text', 'text', 'timestamp');
                        $res = $mdb->execute($prepared_query_4, NULL, $sql_4_data,
                        					 $param_types);
                        if (MDB::isError($res))
              			{
                			echo $res->getMessage() . "<BR>";
                			die($res->getUserInfo());
            			}
        			}
                    echo "$area - $room ($starthour,$length), $type<br>";
		  		}
	  		}
  		}
 	}
}

$mdb->freePreparedQuery($prepared_query_4);
$mdb->freePreparedQuery($prepared_query_3);
$mdb->freePreparedQuery($prepared_query_2);
$mdb->freePreparedQuery($prepared_query_1);
$mdb->freeResult($room_res);
$mdb->freeResult($area_res);
$mdb->disconnect();
?>