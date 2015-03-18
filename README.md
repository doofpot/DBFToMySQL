DBF To MySQL
------------

This script imports DBASE/FoxPro files, located in a subdir into mysql. I needed to write this for reverse
engeneering a foxpro application. For OS/X and Linux, I didn't find any usefull and working tool to do this
automatically.

This script is based on: http://stackoverflow.com/questions/14270236/php-script-to-convert-dbf-files-to-mysql 

But for foxpro files the dbase library which is standard in php, didn't work, so I used https://github.com/hisamu/php-xbase

and ofcourse, I updated all the things to mysqli interface.

It's "tested" with my feed of foxpro files. It seems to work.

Known-issues
- if a date field is 0, then the the corresponding date with 0 is inserted (january first, 1970). This is a bug, but it needs 2 other lines (and it's now too late)
- the php-xbase thing is in the subdir "classes". As a ruby programmer, i didn't find how to make this separate whithin the day, I spent on solving this problem. If someone can help me with this problem.


It's now 18 march 2015 and this is my first public published script in 8 years.

License

MIT