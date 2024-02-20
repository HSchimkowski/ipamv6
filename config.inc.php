<?php
# database connection
define("DB_HOST", 'localhost');	    # Database server address
define("DB_NAME", 'ipamv6');		# Name of Database
define("DB_PREFIX", '');			# Table_Prefix (if nessecsary)
define("DB_USER", 'ipamv6');		# Database Username
define("DB_PASS", 'ipamv6');		# Database Password
define("DB_PORT", '3306'); 		    # Database port (default: 3306)

# Network style

# desc shows the most specific network for a given field
# asc  shows the "biggest" network in that area
define("NET_ORDER", "asc");         # order of the database (default: asc)
# Cookie

?>