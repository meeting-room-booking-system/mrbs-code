#!/usr/bin/perl -w
  
# $Id$

$server = shift;
$dn = shift;
$password = shift;

use Net::LDAP qw(LDAP_SUCCESS);

$ldap = Net::LDAP->new($server) or exit 1;

$msg = $ldap->bind(dn => $dn, password => $password);

exit $msg->code;
