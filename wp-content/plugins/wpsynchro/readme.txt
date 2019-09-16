=== WP Synchro - Migrate WordPress database and files ===
Contributors: wpsynchro
Donate link: https://wpsynchro.com/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=donate
Tags: migrate,database,files,media,migration,synchronize,db,export,mysql,move,staging,localhost,local,transfer
Requires at least: 4.7
Tested up to: 5.2
Stable tag: 1.4.1
Requires PHP: 5.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0

Complete WordPress migration plugin, that synchronizes database and files. Save time by making it easy to migrate data between two sites

== Description ==

**Complete migration plugin for WP developers, by WP developers**

Automating the repetitive task of migrating sites, such as keeping a local development site synchronized with a production site or a staging site in sync with a production site.

 *  **Save time by automating migrations** - Setup once, run many times
 *  **Improve your service and quality to your customer** by working on the best possible data on dev/staging environments
 *  **Simple and convenient maintenance of your environments** being super fast and easy to use

**WP Synchro FREE gives you:**

*   Pull/push database from one site to another
*   Search/replace in database data (supports serialized data)
*   Select the database tables you want to move
*   High security - No other sites and servers are involved and all data is encrypted on transfer
*   Setup once - Run multiple times - Perfect for development/staging/production environments

**In addition to this, the PRO version gives you:**

*   File synchronization (such as media, plugins, themes or custom files/dirs)
*   Only synchronize the difference in files, making it super fast
*   Customize the exact synchronization you need - Down to a single file
*   Database backup before migration
*   WP CLI command to schedule synchronizations via cron or other trigger
*   Pretty much the ultimate tool for doing WordPress migrations
*   14 day trial is waiting for you to get started at [WPSynchro.com](https://wpsynchro.com/ "WP Synchro PRO")

**Typical use for WP Synchro:**

 *  Developing websites on local server and wanting to push a website to a live server or staging server
 *  Get a copy of a working production site, with both database and files, to a staging or local site for debugging or development with real data
 *  Generally moving WordPress sites from one place to another, even on a firewalled local network

**WP Synchro PRO version:**

Pro version gives you more features, such as synchronizing files, database backup, WP CLI command and much faster support.
Check out how to get PRO version at [WPSynchro.com](https://wpsynchro.com/ "WP Synchro PRO")
We have a 14 day trial waiting for you and 30 day money back guarantee. So why not try the PRO version?

== Installation ==

**Here is how you get started:**

1. Upload the plugin files to the `/wp-content/plugins/wpsynchro` directory, or install the plugin through the WordPress plugins screen directly
1. Make sure to install the plugin on all the WordPress installations (it is needed on both ends of the synchronizing)
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Choose if data can be overwritten or be downloaded from installation in menu WP Synchro->Setup
1. Add your first installation from WP Synchro overview page and configure it 
1. Run the synchronization 
1. Enjoy
1. Rerun the same migration again next time it is needed and enjoy how easy that was

== Frequently Asked Questions ==

= Do you offer support? =

Yes we do, for both free and PRO version. But PRO version users always get priority support, so support requests for the free version will normally take some time.
Check out how to get PRO version at [WPSynchro.com](https://wpsynchro.com/ "WP Synchro site")

You can contact us at <support@wpsynchro.com> for support. Also check out the "Support" menu in WP Synchro, that provides information needed for the support request.

= Does WP Synchro do database merge? =

No. We do not merge data in database. We only migrate the data and overwrite the current.
So it is more like a copy of a site, instead of a merge.

= Where can i contact you with new ideas and bugs? =

If you have an idea for improving WP Synchro or found a bug in WP Synchro, we would love to hear from you on:
<support@wpsynchro.com>

= What is WP Synchro tested on? =

Currently we do automated testing on more than 150 configurations with different WordPress/PHP/Database versions.

WP Synchro is tested on :
 * MySQL 5.5 up to MySQL 8.0 and MariaDB from 5.5 to 10.3.
 * PHP 5.6 up to latest version
 * WordPress from 4.7 to latest version.

= Do you support multisite? =

Well, not really at the moment. 
We have not done much testing on multisite yet, so use it is at own risk.
It is currently planned for next release to support it.

== Screenshots ==

1. Shows the overview of plugin, where you start and delete the synchronization jobs
2. Shows the add/edit screen, where you setup a synchronization job
3. Shows the setup of the plugin
4. WP Synchro doing a database migration

== Changelog ==

= 1.4.1 =
 * Highlight: Maintenance release with bugfixes
 * Improvement: When REST service calls fail, make sure to log more debug logging for easier troubleshooting
 * Bugfix: Proper error is not thrown when failing file reads because of permissions
 * Bugfix: Improvement of IP detection function, that in some cases did not return correct IP
 * Bugfix: In some cases, an endless loop happened when file was remove mid-synchronization
 * Bugfix: WP CLI had its time limit removed, as it is not relevant in CLI environment

= 1.4.0 =
 * Highlight: Big improvement to the compatibility with different hosting setups - Everything now runs chunked in 30 seconds or less, which prevents timeouts on some hosting
 * Bugfix: SAVEQUERIES constant now properly detected
 * Bugfix: Proper handling of unlimited memory limit and max execution time in PHP
 * Bugfix: Prevent security token timeout on slow systems
 * Bugfix: Database backup mistakenly went into endless loop, when having no tables to backup

= 1.3.2 =
 * Improvement: Make database table prefix migration a option instead of forced - Warning will be issued if it is disabled and prefixes are different
 * Improvement: Database table prefix migration will now also change prefix in data in the usermeta and options table
 * Improvement: Added timeout check to healthcheck, to tell people when they have a misconfiguration in their hosting setup 
 
= 1.3.1 =
 * Hotfix: Error in frontend timer causing error in synchronization even if synchronization running fine

= 1.3.0 =
 * Highlight: WP CLI command to run synchronizations via cron or other external trigger (see submenu "Schedule" on installation in overview screen)
 * Highlight: Support migration between installations using different database table prefixes - Will automatically change it
 * Highlight: Major improvement to the data transport - All data will now be compressed and encrypted, regardless of using HTTPS or not
 * Improvement: Handling all the timing in a central way, to optimize timers and decrease risk of hitting PHP max_execution_time limits
 * Improvement: Adding a "Duplicate" option to the overview, so its quick and easy so setup new installations
 * Improvement: Downloading log files is now downloaded as zip file, because logs can get big
 * Improvement: Better handling of problems with uppercase database table names, that is not supported on all databases
 * Bugfix: Trying to write a filename with a unsupported filename will now generate warning instead of error


** Only showing the last few releases - See rest of changelog in changelog.txt ** 