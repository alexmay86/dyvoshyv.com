<?php
define( 'WP_CACHE', true ); // Added by WP Rocket


/** Enable W3 Total Cache */


/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dyvoshyv_loc' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'XsQFz!kedZN%BK7ve46gA(BDnF/8}FWl{e3<4Fg EQX`Jg(MY3nDf<T4J31HB]9?' );
define( 'SECURE_AUTH_KEY',  '=EMI4]<,f@I Lox`:)^tXhInIA2wkA2JGx<5y$VN-u5e_(?blu4E(&6Llk2ug?wZ' );
define( 'LOGGED_IN_KEY',    '6vpSfmNztVFu${Q2k+%t|&hy^qaKA0=YK=q{;d<dHQG?S6>;;jG?SXO|$^iaO +B' );
define( 'NONCE_KEY',        'iMN?lqPrOyXyB?x4xK^r&k&Z_PJWyi+Jq:<lFY^CRM]RmENOm08.i;%|=&sG[T20' );
define( 'AUTH_SALT',        'K,TN$vxY~1>qQaZ0fJzMHP9Th~.-TGzP1J&biabj]Q2hkCVh%SErFKc!v8kq_fM}' );
define( 'SECURE_AUTH_SALT', 'S7|%+!,zdGd;;FrY0xLH9@T&_lB/:RD%d]p_lS$bBz3E(?+2*@~HB(d-v/J aemQ' );
define( 'LOGGED_IN_SALT',   'ifyX9ucLsu*V;p?V=Rzy*i.R e-W)bk6%u@^~hyf#$E}ef1pDX_j Hm},|(VR8SV' );
define( 'NONCE_SALT',       'MOi K$8L>HK0Prmm*YD&gKA1uGAa{xxCnqRu);6PxgO taNc;!E9wQzb@4va?. -' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);


/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
