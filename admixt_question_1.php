<?php

#how many records should we work with
$num_records = 1000000;
$num_records_display = number_format($num_records, 0, '', ',');

#define some filenames
$file_db = 'admixt.sq3';
$file_csv = 'admixt_people.csv';
$file_zip = 'us_postal_codes.csv';
$file_sql = 'import_zip.sql';

#remove output from previous runs
if (is_file($file_csv)) unlink($file_csv);
if (is_file($file_db)) unlink($file_db);
if (is_file($file_zip)) unlink($file_zip);
if (is_file($file_sql)) unlink($file_sql);

#generate 1,000,000 fake records in a .csv
$start = microtime(true);
require_once './vendor/fzaninotto/faker/src/autoload.php';
$faker = new Faker\Generator();
$faker->addProvider(new Faker\Provider\en_US\Person($faker));
$faker->addProvider(new Faker\Provider\en_US\Address($faker));
$faker->addProvider(new Faker\Provider\en_US\PhoneNumber($faker));
$faker->addProvider(new Faker\Provider\Internet($faker));
$faker->addProvider(new Faker\Provider\DateTime($faker));
$faker->seed(20180302);

$export_stack = array();
for ($x = 0; $x <= $num_records; $x++) {
  $export_stack[$x] = array($faker->name, $faker->email, $faker->phoneNumber, $faker->postcode, $faker->iso8601);
} 

$fp = fopen($file_csv, 'w');
foreach ($export_stack as $fields) {
  fputcsv($fp, $fields);
}

echo "Generated csv of $num_records_display fake records in " . number_format(microtime(true) - $start) . " seconds\n";
echo memory_get_usage()/1000/1000 . "MB currently used\n\n";

fclose($fp);
unset($export_stack);

#import 1,000,000 records from csv to an array
$start = microtime(true);
$import_stack = array();

$row = 0;
$fp = fopen($file_csv, 'r');
while (($data = fgetcsv($fp, 1000, ',')) !== FALSE) {
  $import_stack[$row] = SplFixedArray::fromArray(array($data[0], $data[1], $data[2], $data[3], $data[4]));
  $row++;
}

echo "Imported csv of $num_records_display records into an array in " . number_format(microtime(true) - $start) . " seconds\n";
echo memory_get_usage()/1000/1000 . "MB currently used\n\n";

fclose($fp);

#create a sqlite database for this project
$db = new SQLite3($file_db);
if (isset($db)) echo "Opened database successfully\n";
echo memory_get_usage()/1000/1000 . "MB currently used\n\n";

#create the table
$statement = $db->prepare('CREATE TABLE people (name text, email text, phone_number text, zip_code text,  timestamp text)');
$result = $statement->execute();
if (isset($result)) echo "Table created successfully\n";
echo memory_get_usage()/1000/1000 . "MB currently used\n\n";


#insert the 1,000,000 array rows into the database
$start = microtime(true);
foreach (array_chunk($import_stack, 10000) as $import_chunk){
  $sql_transaction = "BEGIN TRANSACTION;\n";
  foreach ($import_chunk as $fields) {
    $sql_transaction .= "INSERT INTO people (name, email, phone_number, zip_code, timestamp) VALUES ('" . SQLite3::escapeString($fields[0]) ."','".SQLite3::escapeString($fields[1])."','".SQLite3::escapeString($fields[2])."','".SQLite3::escapeString($fields[3])."','".SQLite3::escapeString($fields[4])."');\n";
  }
  $sql_transaction .= "COMMIT;\n";
  $db->exec($sql_transaction);
}

echo "Imported $num_records_display sql rows in " . number_format(microtime(true) - $start) . " seconds\n";
echo memory_get_usage()/1000/1000 . "MB currently used\n\n";
unset($import_stack);

$db->close();

#go ahead and grab some data for the left join in excercise 2
$start = microtime(true);

file_put_contents($file_zip, fopen('https://www.aggdata.com/download_sample.php?file=us_postal_codes.csv', 'r'));

$fp = fopen($file_sql,'w');
$v = "CREATE TABLE zip_codes ('Zip Code' text,'Place Name' text,'State' text,'State Abbreviation' text,'County' text,'Latitude' real,'Longitude' real);\n";
$v .= ".separator \",\"\n.import ".$file_zip." zips\n.exit";
fwrite($fp,$v);
fclose($fp);

shell_exec("sqlite3 ".$file_db."<".$file_sql);

echo "Imported zip code data for left joins in " . number_format(microtime(true) - $start) . " seconds\n";
echo memory_get_usage()/1000/1000 . "MB currently used\n\n";

#add columns and indexes to speed up joins on zip code
$start = microtime(true);

$db = new SQLite3($file_db);

#add an index to make left outer joins from people to zips a bit faster
$statement = $db->prepare("CREATE INDEX idx_zips_zip_code ON zips (`Zip Code`);");
$result = $statement->execute();

#we can left outer join from people to zips fine, but going the other way to 1,000,000 rows on a substr() is terribly slow
#sqlite doesn't support substr indexes, so create a new column, populate it and index it
$statement = $db->prepare("ALTER TABLE people ADD COLUMN zip5 varchar(5);");
$result = $statement->execute();

$statement = $db->prepare("UPDATE people SET zip5 = substr(people.zip_code,0,6);");
$result = $statement->execute();

$statement = $db->prepare("CREATE INDEX idx_people_zip5 ON people (`zip5`);");
$result = $statement->execute();

echo "Added columns and indexes for left joins in " . number_format(microtime(true) - $start) . " seconds\n";
echo memory_get_usage()/1000/1000 . "MB currently used\n\n";

?>