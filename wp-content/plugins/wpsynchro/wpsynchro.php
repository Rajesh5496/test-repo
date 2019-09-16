<?php
/*
  Plugin Name: WP Synchro
  Plugin URI: https://wpsynchro.com/home
  Description: Complete migration plugin for WordPress - Synchronization of database and files made easy.
  Version: 1.4.1
  Author: WPSynchro
  Author URI: https://wpsynchro.com
  Domain Path: /languages/
  Text Domain: wpsynchro
  License: GPLv3
  License URI: http://www.gnu.org/licenses/gpl-3.0
 */

/**
 * 	Copyright (C) 2018 WPSynchro (email: support@wpsynchro.com)
 *
 * 	This program is free software; you can redistribute it and/or
 * 	modify it under the terms of the GNU General Public License
 * 	as published by the Free Software Foundation; either version 2
 * 	of the License, or (at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program; if not, write to the Free Software
 * 	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

define('WPSYNCHRO_VERSION', '1.4.1');
define('WPSYNCHRO_DB_VERSION', '4');
define('WPSYNCHRO_NEWEST_MU_COMPATIBILITY_VERSION', '1.0.1'); // MU plugin version

/**
 *  On plugin activation
 *  @since 1.0.0
 */
function wpsynchroActivation()
{
    require_once("includes/class-admin-activation.php");
    \WPSynchro\AdminActivation::activation();
}
register_activation_hook(__FILE__, 'wpsynchroActivation');

/**
 *  On plugin deactivation
 *  @since 1.0.0
 */
function wpsynchroDeactivation()
{
    require_once("includes/class-admin-deactivation.php");
    \WPSynchro\AdminDeactivation::deactivation();
    
}
register_deactivation_hook(__FILE__, 'wpsynchroDeactivation');

/**
 *  Load WP Synchro plugin
 *  @since 1.0.0
 */
require_once('includes/class-wpsynchro.php');

/**
 *  Run WP Synchro
 *  @since 1.0.0
 */
function wpsynchroRun()
{
    $wpsynchro = new WPSynchro\WPSynchro();
    $wpsynchro->run();
}
wpsynchroRun();
