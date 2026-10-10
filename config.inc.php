<?php
# Local overrides (not under version control), e.g. real database credentials:
#   define("DB_PASS", 'secret');
if (file_exists(__DIR__ . '/config.local.php'))
	require_once __DIR__ . '/config.local.php';

# database connection (environment variables take precedence over the defaults)
function ipamv6_config($name, $default)
{
	if (!defined($name))
		define($name, getenv($name) !== false ? getenv($name) : $default);
}

ipamv6_config("DB_HOST", 'localhost');	# Database server address
ipamv6_config("DB_NAME", 'ipamv6');		# Name of Database
ipamv6_config("DB_PREFIX", '');			# Table_Prefix (if nessecsary)
ipamv6_config("DB_USER", 'ipamv6');		# Database Username
ipamv6_config("DB_PASS", 'ipamv6');		# Database Password
ipamv6_config("DB_PORT", '3306');		# Database port (default: 3306)

# Network style

# desc shows the most specific network for a given field
# asc  shows the "biggest" network in that area
ipamv6_config("NET_ORDER", "asc");		# order of the database (default: asc)
