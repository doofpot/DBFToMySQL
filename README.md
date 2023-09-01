This repository is not maintained. Use it for your own ideas. I don't maintain it any more, because I don't 
need it. We migrated all our stuff to MySQL and MSSQL.

DBF To MySQL 
========================================================================================================================


This script imports DBASE/FoxPro files, located in a subdir, into a MySQL database. It can be used for reverse
engeneering of FoxPro applications. For OS/X and Linux, I didn't find any usefull and working tool to do this
automatically.

This script is based on: 

* Commits added by https://github.com/jorgecasas/DBFToMySQL
* http://stackoverflow.com/questions/14270236/php-script-to-convert-dbf-files-to-mysql
* http://www.ostalks.com/2009/12/31/import-dbf-files-to-mysql-using-php/


Installation and use
------------------------------------------------------------------------------------------------------------------------

* Install php-xbase and this library from Github in the same directory. The dbase extension for PHP don't read MEMO data (or do it badly), so php-xbase is needed.

```bash
git clone https://github.com/hisamu/php-xbase.git
git clone https://github.com/doofpot/DBFToMySQL.git

```
* Configure MySQL info and paths to directory where DBF/PFT files are located in configuration file **config.php**

* Use it:

```php
php dbf-import.php
```

Known-issues
------------------------------------------------------------------------------------------------------------------------

- if a date field is 0, then the the corresponding date with 0 is inserted (january first, 1970).
- the php-xbase thing is in the subdir "classes". As a ruby programmer, i didn't find how to make this separate whithin the day, I spent on solving this problem. If someone can help me with this problem.

License
------------------------------------------------------------------------------------------------------------------------

MIT
