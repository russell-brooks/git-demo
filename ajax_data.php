<?php

$file_db = 'admixt.sq3';

$db = new SQLite3($file_db);

$statement = $db->prepare("SELECT zips.State, zips.County, sum(case when people.rowid is not null then 1 else 0 end) as 'Num People' from zips left outer join people on zips.`Zip Code` = people.zip5 GROUP BY State, County ORDER BY sum(case when people.rowid is not null then 1 else 0 end) DESC");
$result = $statement->execute();

$result_array = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
  $result_array[] = $row;
}

$prepare_array = array ("data" => $result_array);
$json_export = json_encode($prepare_array);

header('Content-type: application/json');
echo $json_export;

?>