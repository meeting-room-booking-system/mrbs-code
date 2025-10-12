email-parse [![Build Status](https://travis-ci.org/mmucklo/email-parse.svg?branch=master)](https://travis-ci.org/mmucklo/email-parse)
===========

Email\Parse is a multiple (and single) batch email address parser that is reasonably RFC822 / RFC2822 compliant.

It parses a list of 1 to n email addresses separated by space or comma

Installation:
-------------
Add this line to your composer.json "require" section:

### composer.json
```json
    "require": {
       ...
       "mmucklo/email-parse": "*"
```

Usage:
------

```php
use Email\Parse;

$result = Parse::getInstance()->parse("a@aaa.com b@bbb.com");
```

Notes:
======
This should be RFC 2822 compliant, although it will let a few obsolete RFC 822 addresses through such as `test"test"test@xyz.com` (note the quoted string in the middle of the address, which may be obsolete as of RFC 2822).  However it wont allow escaping outside of quotes such as `test@test@xyz.com`.  This would have to be written as `"test@test"@xyz.com`

Here are a few other examples:

```
"John Q. Public" <johnpublic@xyz.com>
this.is.an.address@xyz.com
how-about-an-ip@[10.0.10.2]
how-about-comments(this is a comment!!)@xyz.com
```

#### Function Spec ####

```php
/**
 * function parse($emails, $multiple = true, $encoding = 'UTF-8')
 * @param string $emails List of Email addresses separated by comma or space if multiple
 * @param bool $multiple (optional, default: true) Whether to parse for multiple email addresses or not
 * @param string $encoding (optional, default: 'UTF-8')The encoding if not 'UTF-8'
 * @return: see below: */

    if ($multiple):
         array('success' => boolean, // whether totally successful or not
               'reason' => string, // if unsuccessful, the reason why
               'email_addresses' =>
                    array('address' => string, // the full address (not including comments)
                        'original_address' => string, // the full address including comments
                        'simple_address' => string, // simply local_part@domain_part (e.g. someone@somewhere.com)
                         'name' => string, // the name on the email if given (e.g.: John Q. Public), including any quotes
                         'name_parsed' => string, // the name on the email if given (e.g.: John Q. Public), excluding any quotes
                        'local_part' => string, // the local part (before the '@' sign - e.g. johnpublic)
                        'local_part_parsed' => string, // the local part (before the '@' sign - e.g. johnpublic), excluding any quotes
                        'domain' => string, // the domain after the '@' if given
                         'ip' => string, // the IP after the '@' if given
                         'domain_part' => string, // either domain or IP depending on what given
                        'invalid' => boolean, // if the email is valid or not
                        'invalid_reason' => string), // if the email is invalid, the reason why
                    array( .... ) // the next email address matched
        )
    else:
        array('address' => string, // the full address including comments
            'name' => string, // the name on the email if given (e.g.: John Q. Public)
            'local_part' => string, // the local part (before the '@' sign - e.g. johnpublic)
            'domain' => string, // the domain after the '@' if given
            'ip' => string, // the IP after the '@' if given
            'invalid' => boolean, // if the email is valid or not
            'invalid_reason' => string) // if the email is invalid, the reason why
    endif;
```

Other Examples:
---------------
```php
 $email = "\"J Doe\" <johndoe@xyz.com>";
 $result = Email\Parse->getInstance()->parse($email, false);

 $result == array('address' => '"JD" <johndoe@xyz.com>',
          'original_address' => '"JD" <johndoe@xyz.com>',
          'name' => '"JD"',
          'name_parsed' => 'J Doe',
          'local_part' => 'johndoe',
          'local_part_parsed' => 'johndoe',
          'domain_part' => 'xyz.com',
          'domain' => 'xyz.com',
          'ip' => '',
          'invalid' => false,
          'invalid_reason' => '');

 $emails = "testing@[10.0.10.45] testing@xyz.com, testing-"test...2"@xyz.com (comment)";
 $result = Email\Parse->getInstance()->parse($emails);
 $result == array(
            'success' => boolean true
            'reason' => null
            'email_addresses' =>
                array(
                array(
                    'address' => 'testing@[10.0.10.45]',
                    'original_address' => 'testing@[10.0.10.45]',
                    'name' => '',
                    'name_parsed' => '',
                    'local_part' => 'testing',
                    'local_part_parsed' => 'testing',
                    'domain_part' => '10.0.10.45',
                    'domain' => '',
                    'ip' => '10.0.10.45',
                    'invalid' => false,
                    'invalid_reason' => ''),
                array(
                    'address' => 'testing@xyz.com',
                    'original_address' => 'testing@xyz.com',
                    'name' => '',
                    'name_parsed' => '',
                    'local_part' => 'testing',
                    'local_part' => 'testing',
                    'domain_part' => 'xyz.com',
                    'domain' => 'xyz.com',
                    'ip' => '',
                    'invalid' => false,
                    'invalid_reason' => '')
                array(
                    'address' => '"testing-test...2"@xyz.com',
                    'original_address' => 'testing-"test...2"@xyz.com (comment)',
                    'name' => '',
                    'name_parsed' => '',
                    'local_part' => '"testing-test2"',
                    'local_part_parsed' => 'testing-test...2',
                    'domain_part' => 'xyz.com',
                    'domain' => 'xyz.com',
                    'ip' => '',
                    'invalid' => false,
                    'invalid_reason' => '')
                )
            );
```
