#!/usr/bin/perl

# Authentication script to use with MRBS's "ext" authentication
# scheme. config.inc.php should include something like:
#
# $auth["realm"]  = "MRBS";
# $auth["type"]   = "ext";
# $auth["prog"]   = "../crypt_passwd.pl";
# $auth["params"] = "/etc/httpd/mrbs_passwd #USERNAME# #PASSWORD#";
#
# The script takes 3 pararameters:
#
# PASSWDFILE USERNAME PASSWORD
#
# Where:
#
# PASSWDFILE - Filename of password file, which is the form
#              <username>:<crypted password>
#              [See crypt_passwd.example for an example]
#              You should make sure this is readable by the
#              user that PHP (most likely the web server)
#              runs as.
# USERNAME   - Username to check
# PASSWORD   - Password to check against crypted password in
#              password file
#
# Returns 0 on success, 1 on failure

use strict;
use warnings;

my $passwd_filename = shift || die "No passwd filename supplied\n";
my $username = shift || die "No username supplied\n";
my $password = shift || die "No password supplied\n";

my $retcode = 1;

open PASSWD,'<',$passwd_filename;

while (<PASSWD>)
{
  if (m/^([^:]+):(.*)$/)
  {
    my $user = $1;
    my $crypt = $2;

    if ($user eq $username)
    {
      if (crypt($password, $crypt) eq $crypt)
      {
        $retcode = 0;
	last;
      }
      else
      {
        last;
      }
    }
  }
}
close PASSWD;

exit $retcode;
