#!/usr/bin/perl -w

# $Id$

my $server = shift;
my $searchroot  = shift;
my $uid = shift;
my $password = shift;

# If you want all login names to be converted to lower case
# and no spaces, uncomment the next 2 lines.  Personally,
# I don't think that's wise.
#$uid = lc($uid);
#$uid =~ s/ /_/g;

# This line enforces that login names are entered in
# lower case with no spaces.  This insures that the
# create_by entries will be consistent.
# Otherwise, create_by entries could contain
# "Mark Belanger", "mark_belanger", and "Mark_Belanger" which
# would cause problems with a user being able to alter/delete
# their own meetings.
if(($uid =~ m/[A-Z]/) || ($uid =~ /\s/)){ exit 1;}

my $ldapsearch=qq!/usr/bin/ldapsearch -h $server  -b "$searchroot" "(uid=$uid)"  dn!;
$dn=`$ldapsearch`;
chomp $dn;
if ( $dn eq "" ){ exit 1; }
$ldapsearch=qq!/usr/bin/ldapsearch -h $server -b "$searchroot" -D "$dn" -w "$password" "(uid=$uid)" dn!;
`$ldapsearch`;
if( $? != 0 ){ exit 2; }
else{ exit 0; }

