#!/usr/bin/perl -w
my $server = shift;
my $searchroot  = shift;
my $uid = shift;
my $password = shift;
$uid = lc($uid);
$uid =~ s/ /_/g;
my $ldapsearch=qq!/usr/bin/ldapsearch -h $server  -b "$searchroot" "(uid=$uid)"  dn!;
$dn=`$ldapsearch`;
chomp $dn;
if ( $dn eq "" ){ exit 1; }
$ldapsearch=qq!/usr/bin/ldapsearch -h $server -b "$searchroot" -D "$dn" -w "$password" "(uid=$uid)" dn!;
`$ldapsearch`;
if( $? != 0 ){ exit 2; }
else{ exit 0; }

