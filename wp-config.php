<?php
define( 'WP_CACHE', true ) ;

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u538445002_uneru' );

/** MySQL database username */
define( 'DB_USER', 'u538445002_ujupe' );

/** MySQL database password */
define( 'DB_PASSWORD', 'aZuTyRemuX' );

/** MySQL hostname */
define( 'DB_HOST', 'mysql' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          ')-X4gb-+Y-l+6V;=ANK3>.#8@nbpp810k Ql1AG@X5R!0kkIx*Jvqq;gJvmGQ~QF' );
define( 'SECURE_AUTH_KEY',   '?yM/4)Q[ns=< (3^h;%),s zG5KwEs6Vq{Im`jh+TN{|SrtDT:dEphy~3)+f4T9^' );
define( 'LOGGED_IN_KEY',     'jDUr_7p:|B1SOoe<}T.TlD2FcGL|s_PeqourkgOky{3V]T&H+C R-r/3r]Nl4Qox' );
define( 'NONCE_KEY',         'pkdr3 [I9}N&y#!btWZaF5u2E|,L+YudOd>*,ozSN,HBXTu$0I$E#_*RXS4|5#<v' );
define( 'AUTH_SALT',         'j%l2[yZj$=8@lS;r]dn_qW}6*U/<]+YmBPk_Sz.=K]88JoRF6cQ#5g;VoW-SIK,h' );
define( 'SECURE_AUTH_SALT',  '7_:7QHuuq9{nwobhxJ[C,PD:3XE[ZRrP]HRc9Ae@N`~{[;vYqb7=>$a*ak;HhMzx' );
define( 'LOGGED_IN_SALT',    'lnw_<&M/:C1sQ`#mK~FE%pTY+E~Sp@&MY,BW&]_<7,*%(fDOfgWl&}W&4J@v>?e7' );
define( 'NONCE_SALT',        '_oJ(.O]KQ0^N)9@HCqJftW[xgLuwjOnHXJuDz@9vjEIIYU $`juS9U;/YC%bkIMt' );
define( 'WP_CACHE_KEY_SALT', 'R_bS_Ve=7>?*)|R[#zE`1-{C7<a+T>vzC{[P`}-a*0f)Izu;?/Iw_0YeUU;drEOw' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';




/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
