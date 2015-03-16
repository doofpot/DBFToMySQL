<?php

$tbl = "agri";
$db_uname = 'root';
$db_passwd = '';
$db = 'ls_admin';
$conn = mysql_pconnect('localhost',$db_uname, $db_passwd);

$dir = 'data1_0303';

$files1 = scandir($dir)
or die ("Error! Could not open directory '$dir'.");

foreach ($files1 as $file1) 
{
  // print_r ($file1);
  switch ($file1) {
  case (preg_match("/dbf$/", $file1) ? true : false):
  	print_r("DBF: $file1\n");
  	dbftomysql($file1);
  	break;
  default:
  	print_r("Other file: $file1\n");
  }

}

function dbftomysql($file1) {
	// Path to dbase file
	global $dir;
	print_r ("$dir\n");

	$db_path = sprintf("%s/%s",$dir,$file1);
	print_r ("$db_path\n");
	// Open dbase file
	$dbh = dbase_open($db_path, 0);
	 // || die ("Sorry, can't open '$db_path'.");

	// Get column information
	$column_info = dbase_get_header_info($dbh);

	// Display information
	print_r($column_info);

	return;

	$line = array();

	foreach($column_info as $col)
	{
		switch($col['type'])
		{
		case 'character':
			$line[]= "`$col[name]` VARCHAR( $col[length] )";
			break;
		case 'number':
			$line[]= "`$col[name]` FLOAT";
			break;
		case 'boolean':
			$line[]= "`$col[name]` BOOL";
			break;
		case 'date':
			$line[]= "`$col[name]` DATE";
			break;
		case 'memo':
			$line[]= "`$col[name]` TEXT";
			break;
		}
	}

	$str = implode(",",$line);
	$sql = "CREATE TABLE `$tbl` ( $str );";

	mysql_select_db($db,$conn);

	mysql_query($sql,$conn);
	set_time_limit(0); // I added unlimited time limit here, because the records I imported were in the hundreds of thousands.

	// This is part 2 of the code

	import_dbf($db, $tbl, $db_path);
}

function import_dbf($db, $table, $dbf_file)
{
global $conn;
if (!$dbf = dbase_open ($dbf_file, 0)){ die("Could not open $dbf_file for import."); }
$num_rec = dbase_numrecords($dbf);
$num_fields = dbase_numfields($dbf);
$fields = array();

for ($i=1; $i<=$num_rec; $i++){
$row = @dbase_get_record_with_names($dbf,$i);
$q = "insert into $db.$table values (";
foreach ($row as $key => $val){
if ($key == 'deleted'){ continue; }
$q .= "'" . addslashes(trim($val)) . "',"; // Code modified to trim out whitespaces
}
if (isset($extra_col_val)){ $q .= "'$extra_col_val',"; }
$q = substr($q, 0, -1);
$q .= ')';
//if the query failed - go ahead and print a bunch of debug info
if (!$result = mysql_query($q, $conn)){
print (mysql_error() . " SQL: $q
\n");
print (substr_count($q, ',') + 1) . " Fields total.

";
$problem_q = explode(',', $q);
$q1 = "desc $db.$table";
$result1 = mysql_query($q1, $conn);
$columns = array();
$i = 1;
while ($row1 = mysql_fetch_assoc($result1)){
$columns[$i] = $row1['Field'];
$i++;
}
$i = 1;
foreach ($problem_q as $pq){
print "$i column: {$columns[$i]} data: $pq
\n";
$i++;
}
die();
}
}
}


?>