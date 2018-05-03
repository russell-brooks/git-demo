# Admixt Assignment

Task 1:

Write a PHP script that loops over an array and inserts 1,000,000 rows into a database table that stores name, email, phone number, zip code, and a timestamp.

Task 2: 

Write a web application that:

1. uses jQuery
2. to make an Ajax request
3. to a PHP script that  
4. performs a SQL query
5. that incorporates a left outer join (to another table you create)
6. selects data using any aggregate functions
7. and returns JSON to the jQuery front-end
8. that loads the data into an html table


## OS X Sierra Setup Notes

### prepare

    mkdir admixt
    cd admixt/

### install current php

    brew tap homebrew/homebrew-php
    brew install php72

### install composer

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"

### install faker and psych

    ./composer.phar require fzaninotto/faker
    ./composer.phar require psy/psysh:@stable

### increase php.ini memory for large array

    memory_limit = 2048M

## Run Notes
Working with large arrays requires a lot of memory, while most of the operations on the 1,000,000 record array run in < 1GB, the fake data generation takes < 2GB.  Set memory_limit to 2048MB.

Then run the php script admixt_question_1.php.

    php admixt_question_1.php

This does the following:
- Generate 1,000,000 fake records in an array
- Export 1,000,000 fake records to a .csv
- Import 1,000,000 fake records from the .csv into an array
- Insert 1,000,000 records into SQLite from the array
- Download a .csv of zip codes and other area data
- Import those records to SQLite via sqlite3
- Add columns and indexes for querying later

You should see output like:

>Generated csv of 1,000,000 fake records in 65 seconds
>>891.707088MB currently used

>Imported csv of 1,000,000 records into an array in 4 seconds
>>465.828576MB currently used

>Opened database successfully
>>465.820072MB currently used

>Table created successfully
>>465.820304MB currently used

>Imported 1,000,000 sql rows in 6 seconds
>>522.348176MB currently used

>Imported zip code data for left joins in 2 seconds
>>9.964032MB currently used

>Added columns and indexes for left joins in 2 seconds
>>9.964088MB currently used

Then run a server for php via:

    php -S localhost:8000

Go to:
http://localhost:8000/admixt_question_2.html

This pulls in the data from:
http://localhost:8000/ajax_data.php
and renders it in a sortable, filterable table.