# Upgrade Information for MRBS

## General upgrade procedure

If you are upgrading from MRBS 1.2-pre3 or later, MRBS will automatically execute any necessary database upgrades when it is first run. It will prompt you for a database (not MRBS) username and password with rights to create and alter tables.

It is a sensible precaution to take a backup of your database before the upgrade.

1. Take a backup of your database, just in case.
2. Take a backup copy of your existing mrbs directory on your web server.
3. Upload all the files and directories, except the config file, in the web directory of the release to a new directory on your server. Copy your `config.inc.php` file from your old directory to the new one. If you are upgrading from MRBS 1.4.7 or earlier, the structure of the config file has changed and you should create a new config file based on `config.inc.php-sample`.
4. Go to MRBS in your browser. If a database upgrade is required, you will be prompted for a database (note database, not MRBS) username and password.
5. Rename your directories so that the new one becomes the working one.

MRBS database upgrades are in general not backwards compatible, ie you won't be able to run an older version of MRBS against a later version of the database. You may therefore choose to make a copy of the database for test purposes and check that the upgrade process works before performing the upgrade on your production database.

See the advice in `INSTALL` about potentially creating a fresh `config.inc.php` when you upgrade MRBS, especially for a major version change.


## Upgrading to MRBS 1.12.4

### Password reset requires `$url_base` (including scheme and host)

Password reset emails contain a link back to MRBS. Previously, if `$url_base` was not set, MRBS built this link from the HTTP `Host` header, which a client can control. The link is no longer built from request headers or server variables.

As a result:

* The "Lost password" link is only shown, and password reset is only available, if `$url_base` is set to a full URL including the scheme and host.
* If `$url_base` is not set, the feature is hidden and password reset pages redirect to the login page.

If you use password reset with the `db` authentication scheme, set `$url_base` in `config.inc.php` after upgrading, for example:

```php
$url_base = "https://example.com/mrbs";
```

### CSV exports

Text values in CSV reports that begin with `=`, `+`, `-`, `@`, a tab or a carriage return are now prefixed with a single quote (`'`), so that spreadsheet applications do not treat them as formulas. Numeric values are not changed.


## Upgrading to MRBS 1.12.3

### `ext` authentication: `$auth["params"]` and running the program

The external program used by `ext` authentication is now run without a shell, which fixes a command injection vulnerability (GHSA-m25x-gcj8-36f9). `$auth["params"]` may now be either:

* a string, which is split on whitespace, or
* an array of strings, which is used as the arguments directly.

If your configuration has quoted arguments containing spaces, change it to the array form. For example:

```php
$auth["params"] = ["/etc/htpasswd", "#USERNAME#", "#PASSWORD#"];
```

Also note that `ext` authentication is no longer supported on Windows with PHP versions affected by CVE-2024-5585.


## Upgrading from prior to MRBS 1.8.0

The following configuration settings have changed:

| Setting | Status |
|---------|--------|
| `$area_list_format` | Redundant. All area and room lists are now select elements. |
| `$display_calendar_bottom` | Redundant. The mini-calendars have moved. |
| `$max_slots` | Redundant. The code has been rewritten. |
| `$simple_trailer` | Redundant. There is no longer a trailer! |

The following `$strftime_format` settings have changed:

| Old setting | Status |
|-------------|--------|
| `'day_month'` | Redundant |
| `'dayname_cal'` | Replaced by `'minical_dayname'` |
| `'month_cal'` | Replaced by `'minical_monthname'` |
| `'monthyear'` | Replaced by `'view_month'` |


## Upgrading from prior to MRBS 1.7.1

The `$year_range` configuration setting has been abandoned. If you have it in your config file it won't do anything.


## Upgrading from prior to MRBS 1.7.0

As a security measure, custom HTML for areas and rooms has been disabled by default, since it could be used to insert malicious JavaScript. However, if you trust your admins you can re-enable it by setting the following in the config file:

```php
$auth['allow_custom_html'] = true;
```


## Upgrading from prior to MRBS 1.6.0

If you upgrade to MRBS 1.6.0 and use your old `config.inc.php` file, you must add a line near to the top of the file, just after the `<?php` tag, to make the file read:

```php
<?php
namespace MRBS;
```


## Upgrading from prior to MRBS 1.5.0

MRBS's default authentication scheme changed from `config` to `db` with the release of MRBS 1.5.0. If you had previously used the `config` scheme without specifically stating this in your `config.inc.php`, you will need to make a change to your `config.inc.php` after upgrading to MRBS 1.5.0. The change you need is:

```php
$auth["type"] = "config";
```


## Upgrading from prior to MRBS 1.4.9

MRBS now supports the `$vocab_override` config variable. See `systemdefaults.inc.php` for more details. If you have customised your version of MRBS by editing the lang files, you are advised to use `$vocab_override` instead. This will make future upgrades easier.


## Upgrading from prior to MRBS 1.4.6

If you were previously using MRBS with `$unicode_encoding` set to 0, when you upgrade to 1.4.6 you **MUST** upgrade the MySQL database from the previously used character set to Unicode. Note that it is extremely unlikely that you will need to do this as the default setting of `$unicode_encoding` is 1.

If you do need to convert text in the database you should run the `convert_db_to_utf8.php` script **BEFORE** upgrading to the latest version of MRBS. The administrator should copy the file into the web directory, run it (choosing the encoding to convert from) ONCE, and then move it back out of the web directory. We recommend you backup your database before running this script if you are at all worried. Running it more than once will make a right mess of any non-ASCII text in the database.

Additionally, this script can correct an MRBS database that used to run on an old version of MySQL (earlier than 4.1), but that now runs on a newer version of MySQL. In this case, the database contains UTF-8 text, but the tables are considered to be in some other encoding by MySQL, generally Latin-1. The `convert_db_to_utf8.php` script detects this condition, and offers the administrator the chance to correct the database 'collation'.

The following configuration variables are now deprecated. Their use is supported for the moment but you should change your config file now to use the new variables, as support for the old variables may be dropped in the future:

| Deprecated setting | Replacement |
|--------------------|-------------|
| `$mail_settings['admin_all']` | `$mail_settings['on_new']` and `$mail_settings['on_change']` |
| `$mail_settings['admin_on_delete']` | `$mail_settings['on_delete']` |
| `$dateformat` | `$strftime_format['daymonth']` |


## Upgrading from prior to MRBS 1.4.5

MRBS 1.4.5 introduces the concept of tentative bookings, or bookings that require confirmation. To avoid confusion, what were previously known as "provisional bookings" have now been renamed "bookings requiring approval" and the config variable `$provisional_enabled` has been renamed `$approval_enabled`. You should update your config file accordingly.

Please also see the note about database compatibility above.


## Upgrading from prior to MRBS 1.2-pre3

Upgrade to MRBS 1.2-pre3 first by following the upgrade instructions in that release.
