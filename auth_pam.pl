#!/usr/bin/sperl5.6.0

# auth_pam.pl
# uses Authen::PAM to validate a user - password pair
# usage: auth_pam.pl [user] [password]
# exit 0 on success, otherwise 1
# script has to be SUID and use sperl if run as an unprivileged use
# handle with care ...
# Michael Redinger

use Authen::PAM;

exit 1 unless ( $ARGV[0] && $ARGV[1] );
my $service = "passwd";
my $username = $ARGV[0];
my $password = $ARGV[1];

sub my_conv_func {
	my @res;
	while ( @_ ) {
		my $code = shift;
		my $msg = shift;
		my $ans = "";

		$ans = $username if ($code == PAM_PROMPT_ECHO_ON() );
		$ans = $password if ($code == PAM_PROMPT_ECHO_OFF() );

		push @res, (PAM_SUCCESS(),$ans);
	}
	push @res, PAM_SUCCESS();
	return @res;
}

ref(my $pamh = new Authen::PAM($service, $username, \&my_conv_func)) ||
	die "Error code $pamh during PAM init!";

my $ret=$pamh->pam_authenticate;

exit 1 if ( $ret != 0 );

