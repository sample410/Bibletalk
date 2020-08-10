<?php
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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'biblestudy_db' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'S#3+W-l;k`hguTZ-wLknD?Z@L}fD%K8WWGl]DlOw`*f{hXO^<=Dv9?7, (8Q?Tqj' );
define( 'SECURE_AUTH_KEY',  'YN0u*Gp,l++iZG;005>9oy|/PzRsF[K<b26$T1<4kl[+six_fF9q=UYO8[,}fG+/' );
define( 'LOGGED_IN_KEY',    's2Ebjg=g9AVE~JBg8TmJXpNxa_FN0Zl;2Z+p3y3%/a%:jF6$ t8Z|V;lsZ:jH`+}' );
define( 'NONCE_KEY',        '|[/umm;Ot~lwZ>Fgc<19Z/@jqxwB@Pjg=x_E8Y~o+WR/8<)DH|).PEhfG;bh}Q<m' );
define( 'AUTH_SALT',        '?=qc`Xe0GJF?4 dXXAO~+6X.C7LyvDY!e%|!0s;gUH_LWPFSaPpTz}ZiC&bMh3r-' );
define( 'SECURE_AUTH_SALT', 'vm<#~1OE#Y=]qkFHEo7$$L,qsQaGwU<=<0o~<~E0Uz0,EGOW[54=yem~e@=R*u<n' );
define( 'LOGGED_IN_SALT',   '+8`73W5 m{:cG7 T.@NLM`!HLFa&y>p{<vrvjY[i2=dq^Y!&P.@^cV;0ni+1rz;@' );
define( 'NONCE_SALT',       'Pc@Qlqw(RA: wG<[7%(#3Rw$y]|Ja+ u~~yo+{M=PfqTNUyvv~j~ gJQf6I;/t|w' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
