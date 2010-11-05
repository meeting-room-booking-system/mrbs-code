<?php // -*-mode: PHP; coding:iso-8859-7;-*-

// $Id$

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is a Greek file.
//
//
//
//
// This file is PHP code. Treat it as such.

// The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-7";

// Used in style.inc
$vocab["mrbs"]               = "Σύστημα Κρατήσεων Αιθουσών (MRBS)";

// Used in functions.inc
$vocab["report"]             = "Αναφορά";
$vocab["admin"]              = "Διαχείριση";
$vocab["help"]               = "Βοήθεια";
$vocab["search"]             = "Αναζήτηση";
$vocab["not_php3"]           = "Προσοχή: Αυτή η σελίδα δεν δουλεύει με PHP3";

// Used in day.php
$vocab["bookingsfor"]        = "Κρατήσεις για";
$vocab["bookingsforpost"]    = ""; // Goes after the date
$vocab["areas"]              = "Περιοχές";
$vocab["daybefore"]          = "Μετάβαση στην προηγούμενη μέρα";
$vocab["dayafter"]           = "Μετάβαση στην επόμενη μέρα";
$vocab["gototoday"]          = "Μετάβαση στη σημερινή μέρα";
$vocab["goto"]               = "Μετάβαση";
$vocab["highlight_line"]     = "Highlight this line";
$vocab["click_to_reserve"]   = "Click on the cell to make a reservation.";

// Used in trailer.inc
$vocab["viewday"]            = "Προβολή ανά ημέρα";
$vocab["viewweek"]           = "Προβολή ανά εβδομάδα";
$vocab["viewmonth"]          = "Προβολή ανά μήνα";
$vocab["ppreview"]           = "Προεπισκόπηση εκτύπωσης";

// Used in edit_entry.php
$vocab["addentry"]           = "Προσθήκη εγγραφής";
$vocab["editentry"]          = "Τροποποίηση εγγραφής";
$vocab["editseries"]         = "Τροποποίηση σειράς";
$vocab["namebooker"]         = "Σύντομη περιγραφή";
$vocab["fulldescription"]    = "Πλήρης περιγραφή:<br>&nbsp;&nbsp;(Αριθμός θέσεων,<br>&nbsp;&nbsp;Εσωτερική/Εξωτερική κλπ.)";
$vocab["date"]               = "Ημερομηνία";
$vocab["start_date"]         = "Ώρα έναρξης";
$vocab["end_date"]           = "Ώρα λήξης";
$vocab["time"]               = "Ώρα";
$vocab["period"]             = "Period";
$vocab["duration"]           = "Διάρκεια";
$vocab["seconds"]            = "δευτερόλεπτα";
$vocab["minutes"]            = "λεπτά";
$vocab["hours"]              = "ώρες";
$vocab["days"]               = "ημέρες";
$vocab["weeks"]              = "εβδομάδες";
$vocab["years"]              = "χρόνια";
$vocab["periods"]            = "periods";
$vocab["all_day"]            = "Ολόκληρη μέρα";
$vocab["type"]               = "Τύπος";
$vocab["internal"]           = "Εσωτερικά";
$vocab["external"]           = "Εξωτερικά";
$vocab["save"]               = "Αποθήκευση";
$vocab["rep_type"]           = "Τύπος επανάληψης";
$vocab["rep_type_0"]         = "Τίποτα";
$vocab["rep_type_1"]         = "Ημερήσια";
$vocab["rep_type_2"]         = "Εβδομαδιαία";
$vocab["rep_type_3"]         = "Μηνιαία";
$vocab["rep_type_4"]         = "Χρόνια";
$vocab["rep_type_5"]         = "Μηνιαία, αντίστοιχη ημέρα";
$vocab["rep_type_6"]         = "n-Εβδομαδιαία";
$vocab["rep_end_date"]       = "Ημερομηνία ολοκλήρωσης επανάληψης";
$vocab["rep_rep_day"]        = "Ημέρα επανάληψης";
$vocab["rep_for_weekly"]     = "(για (n-)εβδομαδιαία)";
$vocab["rep_freq"]           = "Συχνότητα";
$vocab["rep_num_weeks"]      = "Αριθμός εβδομάδων";
$vocab["rep_for_nweekly"]    = "(για n-εβδομαδιαία)";
$vocab["ctrl_click"]         = "Χρησιμοποιήστε Control-Click για να επιλέξετε περισσότερες από μία αίθουσες";
$vocab["entryid"]            = "Αναγνωριστικός αριθμός εγγραφής ";
$vocab["repeat_id"]          = "Αναγνωριστικός αριθμός επανάληψης "; 
$vocab["you_have_not_entered"] = "Δεν εισάγατε το (τα)";
$vocab["you_have_not_selected"] = "You have not selected a";
$vocab["valid_room"]         = "room.";
$vocab["brief_description"]  = "Σύντομη Περιγραφή.";
$vocab["useful_n-weekly_value"] = "χρήσιμη n-εβδομαδιαία τιμή.";

// Used in view_entry.php
$vocab["description"]        = "Περιγραφή";
$vocab["room"]               = "Αίθουσα";
$vocab["createdby"]          = "Δημιουργήθηκε από";
$vocab["lastupdate"]         = "Τελευταία ενημέρωση";
$vocab["deleteentry"]        = "Διαγραφή εγγραφής";
$vocab["deleteseries"]       = "Διαγραφή σειράς επανάληψης";
$vocab["confirmdel"]         = "Είστε βέβαιοι\\nότι θέλετε να\\nδιαγράψετε αυτή την εγγραφή;\\n\\n";
$vocab["returnprev"]         = "Επιστροφή στην προηγούμενη σελίδα";
$vocab["invalid_entry_id"]   = "Λάθος αναγνωριστικός αριθμός αίτησης.";
$vocab["invalid_series_id"]  = "Invalid series id.";

// Used in edit_entry_handler.php
$vocab["error"]              = "Σφάλμα";
$vocab["sched_conflict"]     = "Αντικρουόμενος Προγραμματισμός";
$vocab["conflict"]           = "Η νέα κράτηση αντικρούει με τις ακόλουθες εγγραφές";
$vocab["too_may_entrys"]     = "Οι επιλογές θα δημιουργήσουν υπερβολικό αριθμό εγγραφών.<br>Παρακαλώ χρησιμοποιείστε διαφορετικές επιλογές!";
$vocab["returncal"]          = "Επιστροφή σε προβολή ημερολογίου";
$vocab["failed_to_acquire"]  = "Αποτυχία εξασφάλισης αποκλειστικής πρόσβασης στην βάση δεδομένων"; 

// Authentication stuff
$vocab["accessdenied"]       = "Απαγορεύεται η πρόσβαση";
$vocab["norights"]           = "Δεν έχετε δικαιώματα πρόσβασης για να τροποποιήσετε αυτό το αντικείμενο.";
$vocab["please_login"]       = "Παρακαλώ κάνετε εισαγωγή (log in)";
$vocab["users.name"]          = "Όνομα Χρήστη";
$vocab["users.password"]      = "Κωδικός Πρόσβασης";
$vocab["users.level"]         = "Rights";
$vocab["unknown_user"]       = "Αγνωστος χρήστης";
$vocab["you_are"]            = "Είστε";
$vocab["login"]              = "Εισαγωγή (Log in)";
$vocab["logoff"]             = "Έξοδος (Log Off)";

// Authentication database
$vocab["user_list"]          = "User list";
$vocab["edit_user"]          = "Edit user";
$vocab["delete_user"]        = "Delete this user";
//$vocab["users.name"]         = Use the same as above, for consistency.
//$vocab["users.password"]     = Use the same as above, for consistency.
$vocab["users.email"]         = "Email address";
$vocab["password_twice"]     = "If you wish to change the password, please type the new password twice";
$vocab["passwords_not_eq"]   = "Error: The passwords do not match.";
$vocab["add_new_user"]       = "Add a new user";
$vocab["action"]             = "Action";
$vocab["user"]               = "User";
$vocab["administrator"]      = "Administrator";
$vocab["unknown"]            = "Unknown";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Click to display all my upcoming entries";

// Used in search.php
$vocab["invalid_search"]     = "Κενό ή λανθασμένο κείμενο αναζήτησης.";
$vocab["search_results"]     = "Αποτελέσματα αναζήτησης για";
$vocab["nothing_found"]      = "Δεν βρέθηκαν εγγραφές που να ταιριάζουν.";
$vocab["records"]            = "Καταχώρηση ";
$vocab["through"]            = " έως ";
$vocab["of"]                 = " από ";
$vocab["previous"]           = "Προηγούμενη";
$vocab["next"]               = "Επόμενη";
$vocab["entry"]              = "Αίτηση";
$vocab["view"]               = "Προβολή";
$vocab["advanced_search"]    = "Προηγμένη αναζήτηση";
$vocab["search_button"]      = "Αναζήτηση";
$vocab["search_for"]         = "Αναζήτηση για";
$vocab["from"]               = "Από";

// Used in report.php
$vocab["report_on"]          = "Αναφορά για Συναντήσεις";
$vocab["report_start"]       = "Ημερομηνία έναρξης αναφοράς";
$vocab["report_end"]         = "Ημερομηνία λήξης αναφοράς";
$vocab["match_area"]         = "Ταίριασμα περιοχής";
$vocab["match_room"]         = "Ταίριασμα αίθουσας";
$vocab["match_type"]         = "Match type";
$vocab["ctrl_click_type"]    = "Use Control-Click to select more than one type";
$vocab["match_entry"]        = "Ταίριασμα σύντομης περιγραφής";
$vocab["match_descr"]        = "Ταίριασμα αναλυτικής περιγραφής";
$vocab["include"]            = "Να συμπεριληφθούν";
$vocab["report_only"]        = "Αναφορά μόνο";
$vocab["summary_only"]       = "Περίληψη μόνο";
$vocab["report_and_summary"] = "Αναφορά και περίληψη";
$vocab["summarize_by"]       = "Σύνοψη κατά";
$vocab["sum_by_descrip"]     = "Σύντομη περιγραφή";
$vocab["sum_by_creator"]     = "Δημιουργός";
$vocab["entry_found"]        = "καταχώρηση βρέθηκε";
$vocab["entries_found"]      = "καταχωρήσεις βρέθηκαν";
$vocab["summary_header"]     = "Περίληψη ωρών εγγραφών";
$vocab["summary_header_per"] = "Summary of (Entries) Periods";
$vocab["total"]              = "Σύνολο";
$vocab["submitquery"]        = "Εκτέλεση αναφοράς";
$vocab["sort_rep"]           = "Sort Report by";
$vocab["sort_rep_time"]      = "Start Date/Time";
$vocab["rep_dsp"]            = "Display in report";
$vocab["rep_dsp_dur"]        = "Duration";
$vocab["rep_dsp_end"]        = "End Time";

// Used in week.php
$vocab["weekbefore"]         = "Μετάβαση στην προηγούμενη εβδομάδα";
$vocab["weekafter"]          = "Μετάβαση στην επόμενη εβδομάδα";
$vocab["gotothisweek"]       = "Μετάβαση στην τρέχουσα εβδομάδα";

// Used in month.php
$vocab["monthbefore"]        = "Μετάβαση στον προηγούμενο μήνα";
$vocab["monthafter"]         = "Μετάβαση στον επόμενο μήνα";
$vocab["gotothismonth"]      = "Μετάβαση στον τρέχοντα μήνα";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Δεν έχουν οριστεί αίθουσες για αυτή την περιοχή";

// Used in admin.php
$vocab["edit"]               = "Τροποποίηση";
$vocab["delete"]             = "Διαγραφή";
$vocab["rooms"]              = "Αίθουσες";
$vocab["in"]                 = "στο";
$vocab["noareas"]            = "Καμία περιοχή";
$vocab["addarea"]            = "Προσθήκη περιοχής";
$vocab["name"]               = "Όνομα";
$vocab["noarea"]             = "Δεν έχει επιλεχθεί περιοχή";
$vocab["browserlang"]        = "Ο φυλλομετρητής σας χρησιμοποιεί";
$vocab["addroom"]            = "Προσθήκη αίθουσας";
$vocab["capacity"]           = "Χωρητικότητα";
$vocab["norooms"]            = "Καμιά αίθουσα.";
$vocab["administration"]     = "Διαχείριση";

// Used in edit_area_room.php
$vocab["editarea"]           = "Τροποποίηση περιοχής";
$vocab["change"]             = "Αλλαγή";
$vocab["backadmin"]          = "Επιστροφή στην διαχείριση";
$vocab["editroomarea"]       = "Τροποποίηση περιγραφής περιοχής ή αίθουσας";
$vocab["editroom"]           = "Τροποποίηση αίθουσας";
$vocab["update_room_failed"] = "Η ενημέρωση της αίθουσας απέτυχε: ";
$vocab["error_room"]         = "Σφάλμα: Η αίθουσα ";
$vocab["not_found"]          = " δεν βρέθηκε";
$vocab["update_area_failed"] = "Η ενημέρωση της πειοχής απέτυχε: ";
$vocab["error_area"]         = "Σφάλμα: Η περιοχή ";
$vocab["room_admin_email"]   = "Room admin email";
$vocab["area_admin_email"]   = "Area admin email";
$vocab["invalid_email"]      = "Invalid email!";

// Used in del.php
$vocab["deletefollowing"]    = "Η ενέργεια αυτή θα διαγράψει τις ακόλουθες κρατήσεις";
$vocab["sure"]               = "Είστε σίγουροι;";
$vocab["YES"]                = "ΝΑΙ";
$vocab["NO"]                 = "ΟΧΙ";
$vocab["delarea"]            = "Πρέπει να διαγράψετε όλες τις αίθουσες σε αυτή τη περιοχή για να μπορέσετε να την διαγράψετε<p>";

// Used in help.php
$vocab["about_mrbs"]         = "Σχετικά με το MRBS";
$vocab["database"]           = "Βάση δεδομένων";
$vocab["system"]             = "Σύστημα";
$vocab["please_contact"]     = "Παρακαλώ επικοινωνήστε με ";
$vocab["for_any_questions"]  = "για όσες ερωτήσεις δεν απαντώνται εδώ.";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Κρίσιμο σφάλμα: Αποτυχία σύνδεσης στη βάση δεδομένων";

?>
