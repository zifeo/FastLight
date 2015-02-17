<?php

/* Database, currently only mysql is supported */
const __DB          = 'mysql';
const __SERVER      = 'mysql.example.ch';
const __PORT     	= '3306';
const __BASE        = 'base1';
const __USERNAME    = 'user';
const __PASSWORD    = 'pass';

/* Encryption key, 32 characters long (256 bits), to be modified before production state */
const __key			= 'd9ep6f8dfd7855f7d2c4066q791c8547a2951fd1c1655c73133r4f4df0dc4789';
const __CYPHER 		= MCRYPT_RIJNDAEL_256;
const __MODE   		= MCRYPT_MODE_CBC;

/* Default views for home, header, footer and error pages */
const HOMEPAGE      = 'home';
const HEADER        = 'header';
const FOOTER        = 'footer';
const ERROR404      = 'error';
const ERRORTITLE    = 'Resource not avaible';

/* Custom rights, to be modified before using database */
const VISITOR       = 1;
const REGISTERED    = 2;
const EDITOR        = 3;
// add here more rights
const ADMIN         = 9;
const DEFAULTRIGHTS = VISITOR;

/* General site information */
const SITENAME 		= 'Example site';
const SITEURL 		= 'http://localhost/';
const SITELANG		= 'en_EN';
const SITEVERSION	= '1.0';

/* Mail and SMTP configuration */
const MAILADDR 		= 'no-reply@example.ch';
const MAILSMTP	 	= 'mail.example.ch';
const MAILUSER 		= 'no-reply';
const MAILPASS 		= 'pass';