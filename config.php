<?php

//bipe
define('URL','http://localhost/bipe/');
define('TITLE','biPE');
define('LOCALE','ca');
define('MODE','dark');
define('LOGGING',false);
define('TIMEZONE','Europe/Andorra');
define('WORKDAY_DURATION',8);
define('INTERVAL_H','0.25');
define('TIMESHEET_RESULTS_PAGE',7);

//MariaDB
define('DB_HOST','localhost');
define('DB_NAME','bipe');
define('DB_PORT',3306);
define('DB_USER','mysql');
define('DB_PASS','mysql');

//LDAP
define('LDAP_TREE','dc=laptop,dc=local');
define('LDAP_USER','cn=ldapadm,'.LDAP_TREE);
define('LDAP_HOST','localhost');
define('LDAP_PASS','testldap');
define('LDAP_AUTH_ADMIN_GROUP','tech');
define('LDAP_AUTH_ACCOUNTANT_GROUP','accounts');
define('LDAP_AUTH_EMPLOYEE_GROUP','engineers');

//PHP
define('DISPLAY_ERRORS',true);
define('ERROR_REPORTING',E_ALL);
define('TIME_LIMIT',30);

?>
