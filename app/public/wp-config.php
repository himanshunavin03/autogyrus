<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '(O4!Cw_l5s(3m^,M67w>~jLp)UjyFyNGot9D~7f5j`;Z<e2]U9*A9$R=N}lLS1,7' );
define( 'SECURE_AUTH_KEY',   'p|>E!3dR?td,dA}&ehtM{|Ff}B=P|[R;=ON-p)K/Fv<0@O~Bow94W&x0:&F?CY3u' );
define( 'LOGGED_IN_KEY',     ';P=:ML~klvw~B%cy-&an9kxg>1}u6+=Z]@{Uj~3,h9BwuI64lkO*BNCciD$+8-M}' );
define( 'NONCE_KEY',         'Y?G~7bsr) WNwk-/m<QrX6Y)Z51=.`(|LNsNhS<R=/w!;KbQ*4BPPrC &Cd(7(z9' );
define( 'AUTH_SALT',         'H1^<P-*8o?6!$mszWAG6M=uZ0<@sx}~.1@BHXimf&8hOS[.`JVcklO@OpjM5cosh' );
define( 'SECURE_AUTH_SALT',  '!obadvJ7TfqJWr^rENxu/<wzL{}J%5Htu<uRG{csB*+n?Tz,;kFi8ucrh>tWF?XW' );
define( 'LOGGED_IN_SALT',    '-FB@; Y.s|(Rq?C#?Z^riR0E7ww0_cAw[zeTaXvTmiLI`e7^-H&3r.@GX{nO_T 1' );
define( 'NONCE_SALT',        'S$f=b#0lES=gS#Y0vXB)r>{O/>+,DT?n)M>1t4tXbfFJPLc%kGJKR|s4KFj,#~O)' );
define( 'WP_CACHE_KEY_SALT', 'Sv{h0]Y&gId:$ T53CC>%1u&Di)>{5+>=ex.<<5e+,a@kzA<?D-R;.sN3m(BBm*,' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
