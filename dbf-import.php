<?php

@include "config.php";
@include "classes/XBase/Table.php";
@include "classes/XBase/Column.php";
@include "classes/XBase/Record.php";

use XBase\Table;

$files = scandir($xbase_dir) or die ("Error! Could not open directory '$xbase_dir'.");
$conn = new mysqli($db_host, $db_uname, $db_passwd, $db_name) or die ("Error connecting to mysql $mysqli->connect_error");

foreach ($files as $file) 
{
  // print_r ($file1);
  switch ($file) {
  case (preg_match("/dbf$/i", $file) ? true : false):
  	print_r("DBF: $file\n");
  	dbftomysql($file);
  	break;
  default:
  	print_r("Other file: $file\n");
  }

}

function dbftomysql($file) {
	// Path to dbase file
	global $xbase_dir;
	global $conn;
	$db_path = sprintf("%s/%s",$xbase_dir,$file);
	// Open dbase file
	$table = new Table($db_path);
	$tbl = substr($file,0,strlen($file)-4);
	print_r ("$tbl");
	$line = array();

	foreach ($table->getColumns() as $column) {
		print_r("\t$column->name ($column->type / $column->length)\n");
		// print_r("$column->type\n");

		switch($column->type)
		{
		case 'C':	// Character field
			$line[]= "`$column->name` VARCHAR($column->length)";
			break;
		case 'F':	// Floating Point
			$line[]= "`$column->name` FLOAT";
			break;
		case 'N':	// Numeric
			$line[]= "`$column->name` INT";
			break;
		case 'L':	// Logical - ? Y y N n T t F f (? when not initialized).
			$line[]= "`$column->name` TINYINT";
			break;
		case 'D':	// Date
			$line[]= "`$column->name` DATE";
			break;
		case 'T':	// DateTime
			$line[]= "`$column->name` DATETIME";
			break;
		case 'M':	// Memo type field
			$line[]= "`$column->name` TEXT";
			break;
		}
	}

	$str = implode(",",$line);
	// print_r ("$str\n");

	$sql = "CREATE TABLE `$tbl` ( $str );";
	// print_r ("$sql\n");

	if ($conn->query("$sql") === TRUE) {
		echo "Table $tbl successfully created";
	}

	$table->close();

	// import_dbf($db_path, $tbl);
}

function import_dbf($db_path, $tbl) {
	global $conn;
	// print_r ("$db_path\n");
	$table = new Table($db_path);

	print_r ("$table->recordCount\n");
	print_r (sizeof($table->columns));

	// print_r("$table->getRecordCount()");
	// print_r("$table->getColumnCount()");
	// return;
	// if (!$dbf = dbase_open ($dbf_file, 0)){ die("Could not open $dbf_file for import."); }
	// $num_rec = dbase_numrecords($dbf);
	// $num_fields = dbase_numfields($dbf);
	// $table->getColumns() as $column;
	while ($record=$table->nextRecord()) {
		$fields = array();
		$line = array();
		foreach ($record->getColumns() as $column) {
			// print_r("\t\t$column->name\t");
			// $res=$record->getObject($column);
			// print_r ("$res\n");
			$fields[]=$column->name;
			print_r("$column->name\n");
			switch($column->type) {
				case 'C':	// Character field
				case 'M':	// Memo type field
					$line[]= sprintf("'%s'", $record->getObject($column) );
					break;
				case 'F':	// Floating Point
					$line[]=sprintf("%7.2f", $record->getObject($column) );
					break;
				case 'N':	// Numeric
					$line[]=sprintf("%d", $record->getObject($column) );
					break;
				case 'L':	// Logical - ? Y y N n T t F f (? when not initialized).
					$line[] = ($record->getBoolean($column) ? 1 : 0); 
					break;
				case 'T':	// DateTime
				case 'D':	// Date
					$line[]= sprintf("'%s'", strftime("%Y-%m-%d %H:%M", $record->getObject($column) ) );
					break;
			}			
		}

		$val = implode(",",$line);
		$col = implode(",",$fields);

		$sql = "insert into `$tbl` ($col) values ($val)\n";
		print_r ("$sql");
		if ($conn->query("$sql") === TRUE) {
			echo "Record $sql successfully inserted\n";
		}
	}
	return;

	$fields = array();
	$fields=$table->getColumns();
	print_r ("$fields\n");
	for ($i=1; $i<=sizeof($table->columns); $i++) {
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
			print (mysql_error() . " SQL: $q \n");
			print (substr_count($q, ',') + 1) . " Fields total. ";
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
				print "$i column: {$columns[$i]} data: $pq\n";
				$i++;
			}
			die();
		}
	}
}


?>