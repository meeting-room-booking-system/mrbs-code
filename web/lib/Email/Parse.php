<?php

namespace Email;

use Laminas\Validator\Ip;
use Psr\Log\LoggerInterface;

/**
 * Class Parse.
 */
class Parse
{
    // Constants for the state-machine of the parser
    private const STATE_TRIM = 0;
    private const STATE_QUOTE = 1;
    private const STATE_ADDRESS = 2;
    private const STATE_COMMENT = 3;
    private const STATE_NAME = 4;
    private const STATE_LOCAL_PART = 5;
    private const STATE_DOMAIN = 6;
    private const STATE_AFTER_DOMAIN = 7;
    private const STATE_SQUARE_BRACKET = 8;
    private const STATE_SKIP_AHEAD = 9;
    private const STATE_END_ADDRESS = 10;
    private const STATE_START = 11;

    /**
     * @var Parse
     */
    protected static $instance;

    /**
     * @var Ip
     */
    protected $ipValidator = null;

    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var ParseOptions
     */
    protected $options;

    /**
     * Allow Parse to be instantiated as a singleton.
     *
     * @return Parse The instance
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            return self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @param LoggerInterface|null $logger  (optional) Psr-compliant logger
     * @param array                $options array (hash) of options
     */
    public function __construct(?LoggerInterface $logger = null,
                                ?ParseOptions $options = null)
    {
        $this->logger = $logger;
        $this->options = $options ?: new ParseOptions(['%', '!']);
    }

    /**
     * Allows for post-construct injection of a logger.
     *
     * @param LoggerInterface $logger (optional) Psr-compliant logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setOptions(ParseOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @return ParseOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Abstraction to prevent logging when there's no logger.
     *
     * @param mixed  $level
     * @param string $message
     */
    protected function log($level, $message)
    {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
    }

    /**
     * Parses a list of 1 to n email addresses separated by space or comma.
     *
     *  This should be RFC 2822 compliant, although it will let a few obsolete
     *  RFC 822 addresses through such as test"test"test@xyz.com (note
     *  the quoted string in the middle of the address, which may be obsolete
     *  as of RFC 2822).  However it wont allow escaping outside of quotes
     *  such as test\@test@xyz.com.  This would have to be written
     *  as "test\@test"@xyz.com
     *
     *  Here are a few other examples:
     *
     *  "John Q. Public" <johnpublic@xyz.com>
     *  this.is.an.address@xyz.com
     *  how-about-an-ip@[10.0.10.2]
     *  how-about-comments(this is a comment!!)@xyz.com
     *
     * @param string $emails   List of Email addresses separated by comma or space if multiple
     * @param bool   $multiple (optional, default: true) Whether to parse for multiple email addresses or not
     * @param string $encoding (optional, default: 'UTF-8') The encoding if not 'UTF-8'
     *
     * @return array if ($multiple):
     *               array('success' => boolean, // whether totally successful or not
     *               'reason' => string, // if unsuccessful, the reason why
     *               'email_addresses' =>
     *               array('address' => string, // the full address (not including comments)
     *               'original_address' => string, // the full address including comments
     *               'simple_address' => string, // simply local_part@domain_part (e.g. someone@somewhere.com)
     *               'name' => string, // the name on the email if given (e.g.: John Q. Public), including any quotes
     *               'name_parsed' => string, // the name on the email if given (e.g.: John Q. Public), excluding any quotes
     *               'local_part' => string, // the local part (before the '@' sign - e.g. johnpublic)
     *               'local_part_parsed' => string, // the local part (before the '@' sign - e.g. johnpublic), excluding any quotes
     *               'domain' => string, // the domain after the '@' if given
     *               'ip' => string, // the IP after the '@' if given
     *               'domain_part' => string, // either domain or IP depending on what given
     *               'invalid' => boolean, // if the email is valid or not
     *               'invalid_reason' => string), // if the email is invalid, the reason why
     *               array( .... ) // the next email address matched
     *               )
     *               else:
     *               array('address' => string, // the full address including comments
     *               'name' => string, // the name on the email if given (e.g.: John Q. Public)
     *               'local_part' => string, // the local part (before the '@' sign - e.g. johnpublic)
     *               'domain' => string, // the domain after the '@' if given
     *               'ip' => string, // the IP after the '@' if given
     *               'invalid' => boolean, // if the email is valid or not
     *               'invalid_reason' => string) // if the email is invalid, the reason why
     *               endif;
     *
     * EXAMPLES:
     * $email = "\"J Doe\" <johndoe@xyz.com>";
     * $result = Email\Parse->getInstance()->parse($email, false);
     *
     * $result == array('address' => '"JD" <johndoe@xyz.com>',
     *          'original_address' => '"JD" <johndoe@xyz.com>',
     *          'name' => '"JD"',
     *          'name_parsed' => 'J Doe',
     *          'local_part' => 'johndoe',
     *          'local_part_parsed' => 'johndoe',
     *          'domain_part' => 'xyz.com',
     *          'domain' => 'xyz.com',
     *          'ip' => '',
     *          'invalid' => false,
     *          'invalid_reason' => '');
     *
     * $emails = "testing@[10.0.10.45] testing@xyz.com, testing-"test...2"@xyz.com (comment)";
     * $result = Email\Parse->getInstance()->parse($emails);
     * $result == array(
     *            'success' => boolean true
     *            'reason' => null
     *            'email_addresses' =>
     *                array(
     *                array(
     *                    'address' => 'testing@[10.0.10.45]',
     *                    'original_address' => 'testing@[10.0.10.45]',
     *                    'name' => '',
     *                    'name_parsed' => '',
     *                    'local_part' => 'testing',
     *                    'local_part_parsed' => 'testing',
     *                    'domain_part' => '10.0.10.45',
     *                    'domain' => '',
     *                    'ip' => '10.0.10.45',
     *                    'invalid' => false,
     *                    'invalid_reason' => ''),
     *                array(
     *                    'address' => 'testing@xyz.com',
     *                    'original_address' => 'testing@xyz.com',
     *                    'name' => '',
     *                    'name_parsed' => '',
     *                    'local_part' => 'testing',
     *                    'local_part' => 'testing',
     *                    'domain_part' => 'xyz.com',
     *                    'domain' => 'xyz.com',
     *                    'ip' => '',
     *                    'invalid' => false,
     *                    'invalid_reason' => '')
     *                array(
     *                    'address' => '"testing-test...2"@xyz.com',
     *                    'original_address' => 'testing-"test...2"@xyz.com (comment)',
     *                    'name' => '',
     *                    'name_parsed' => '',
     *                    'local_part' => '"testing-test2"',
     *                    'local_part_parsed' => 'testing-test...2',
     *                    'domain_part' => 'xyz.com',
     *                    'domain' => 'xyz.com',
     *                    'ip' => '',
     *                    'invalid' => false,
     *                    'invalid_reason' => '')
     *                )
     *            );
     */
    public function parse($emails, $multiple = true, $encoding = 'UTF-8')
    {
        $emailAddresses = [];

        // Variables to be used during email address collection
        $emailAddress = $this->buildEmailAddressArray();

        $success = true;
        $reason = null;

        // Current state of the parser
        $state = self::STATE_TRIM;

        // Current sub state (this is for when we get to the xyz@somewhere.com email address itself)
        $subState = self::STATE_START;
        $commentNestLevel = 0;

        $len = mb_strlen($emails, $encoding);
        if (0 == $len) {
            $success = false;
            $reason = 'No emails passed in';
        }
        $curChar = null;
        for ($i = 0; $i < $len; ++$i) {
            $prevChar = $curChar; // Previous Charater
            $curChar = mb_substr($emails, $i, 1, $encoding); // Current Character
            switch ($state) {
                case self::STATE_SKIP_AHEAD:
                    // Skip ahead is set when a bad email address is encountered
                    //  It's supposed to skip to the next delimiter and continue parsing from there
                    if ($multiple &&
                        (' ' == $curChar ||
                        "\r" == $curChar ||
                        "\n" == $curChar ||
                        "\t" == $curChar ||
                         ',' == $curChar)) {
                        $state = self::STATE_END_ADDRESS;
                    } else {
                        $emailAddress['original_address'] .= $curChar;
                    }

                    break;
                    /* @noinspection PhpMissingBreakStatementInspection */
                case self::STATE_TRIM:
                    if (' ' == $curChar ||
                        "\r" == $curChar ||
                        "\n" == $curChar ||
                        "\t" == $curChar) {
                        break;
                    } else {
                        $state = self::STATE_ADDRESS;
                        if ('"' == $curChar) {
                            $emailAddress['original_address'] .= $curChar;
                            $state = self::STATE_QUOTE;
                            break;
                        } elseif ('(' == $curChar) {
                            $emailAddress['original_address'] .= $curChar;
                            $state = self::STATE_COMMENT;
                            break;
                        }
                        // Fall through to next case self::STATE_ADDRESS on purpose here
                    }
                    // Fall through
                    // no break
                case self::STATE_ADDRESS:
                    if (',' != $curChar || !$multiple) {
                        $emailAddress['original_address'] .= $curChar;
                    }

                    if ('(' == $curChar) {
                        // Handle comment
                        $state = self::STATE_COMMENT;
                        $commentNestLevel = 1;
                        break;
                    } elseif (',' == $curChar) {
                        // Handle Comma
                        if ($multiple && (self::STATE_DOMAIN == $subState || self::STATE_AFTER_DOMAIN == $subState)) {
                            // If we're already in the domain part, this should be the end of the address
                            $state = self::STATE_END_ADDRESS;
                            break;
                        } else {
                            $emailAddress['invalid'] = true;
                            if ($multiple || ($i + 5) >= $len) {
                                $emailAddress['invalid_reason'] = 'Misplaced Comma or missing "@" symbol';
                            } else {
                                $emailAddress['invalid_reason'] = 'Comma not permitted - only one email address allowed';
                            }
                        }
                    } elseif (' ' == $curChar ||
                          "\t" == $curChar || "\r" == $curChar ||
                          "\n" == $curChar) {
                        // Handle Whitespace

                        // Look ahead for comments after the address
                        $foundComment = false;
                        for ($j = ($i + 1); $j < $len; ++$j) {
                            $lookAheadChar = mb_substr($emails, $j, 1, $encoding);
                            if ('(' == $lookAheadChar) {
                                $foundComment = true;
                                break;
                            } elseif (' ' != $lookAheadChar &&
                                "\t" != $lookAheadChar &&
                                "\r" != $lookAheadChar &&
                                "\n" != $lookAheadChar) {
                                break;
                            }
                        }
                        // Check if there's a comment found ahead
                        if ($foundComment) {
                            if (self::STATE_DOMAIN == $subState) {
                                $subState = self::STATE_AFTER_DOMAIN;
                            } elseif (self::STATE_LOCAL_PART == $subState) {
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = 'Email Address contains whitespace';
                            }
                        } elseif (self::STATE_DOMAIN == $subState || self::STATE_AFTER_DOMAIN == $subState) {
                            // If we're already in the domain part, this should be the end of the whole address
                            $state = self::STATE_END_ADDRESS;
                            break;
                        } else {
                            if (self::STATE_LOCAL_PART == $subState) {
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = 'Email address contains whitespace';
                            } else {
                                // If the previous section was a quoted string, then use that for the name
                                $this->handleQuote($emailAddress);
                                $emailAddress['name_parsed'] .= $curChar;
                            }
                        }
                    } elseif ('<' == $curChar) {
                        // Start of the local part
                        if (self::STATE_LOCAL_PART == $subState || self::STATE_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'Email address contains multiple opening "<" (either a typo or multiple emails that need to be separated by a comma or space)';
                        } else {
                            // Here should be the start of the local part for sure everything else then is part of the name
                            $subState = self::STATE_LOCAL_PART;
                            $emailAddress['special_char_in_substate'] = null;
                            $this->handleQuote($emailAddress);
                        }
                    } elseif ('>' == $curChar) {
                        // should be end of domain part
                        if (self::STATE_DOMAIN != $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Did not find domain name before a closing '>'";
                        } else {
                            $subState = self::STATE_AFTER_DOMAIN;
                        }
                    } elseif ('"' == $curChar) {
                        // If we hit a quote - change to the quote state, unless it's in the domain, in which case it's error
                        if (self::STATE_DOMAIN == $subState || self::STATE_AFTER_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'Quote \'"\' found where it shouldn\'t be';
                        } else {
                            $state = self::STATE_QUOTE;
                        }
                    } elseif ('@' == $curChar) {
                        // Handle '@' sign
                        if (self::STATE_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Multiple at '@' symbols in email address";
                        } elseif (self::STATE_AFTER_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Stray at '@' symbol found after domain name";
                        } elseif (null !== $emailAddress['special_char_in_substate']) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Invalid character found in email address local part: '{$emailAddress['special_char_in_substate']}'";
                        } else {
                            $subState = self::STATE_DOMAIN;
                            if ($emailAddress['address_temp'] && $emailAddress['quote_temp']) {
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = 'Something went wrong during parsing.';
                                $this->log('error', "Email\\Parse->parse - Something went wrong during parsing:\n\$i: {$i}\n\$emailAddress['address_temp']: {$emailAddress['address_temp']}\n\$emailAddress['quote_temp']: {$emailAddress['quote_temp']}\nEmails: {$emails}\n\$curChar: {$curChar}");
                            } elseif ($emailAddress['quote_temp']) {
                                $emailAddress['local_part_parsed'] = $emailAddress['quote_temp'];
                                $emailAddress['quote_temp'] = '';
                                $emailAddress['local_part_quoted'] = true;
                            } elseif ($emailAddress['address_temp']) {
                                $emailAddress['local_part_parsed'] = $emailAddress['address_temp'];
                                $emailAddress['address_temp'] = '';
                                $emailAddress['local_part_quoted'] = $emailAddress['address_temp_quoted'];
                                $emailAddress['address_temp_quoted'] = false;
                                $emailAddress['address_temp_period'] = 0;
                            }
                        }
                    } elseif ('[' == $curChar) {
                        // Setup square bracket special handling if appropriate
                        if (self::STATE_DOMAIN != $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Invalid character '[' in email address";
                        }
                        $state = self::STATE_SQUARE_BRACKET;
                    } elseif ('.' == $curChar) {
                        // Handle periods specially
                        if ('.' == $prevChar) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Email address should not contain two dots '.' in a row";
                        } elseif (self::STATE_LOCAL_PART == $subState) {
                            if (!$emailAddress['local_part_parsed']) {
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = "Email address can not start with '.'";
                            } else {
                                $emailAddress['local_part_parsed'] .= $curChar;
                            }
                        } elseif (self::STATE_DOMAIN == $subState) {
                            $emailAddress['domain'] .= $curChar;
                        } elseif (self::STATE_AFTER_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Stray period '.' found after domain of email address";
                        } elseif (self::STATE_START == $subState) {
                            if ($emailAddress['quote_temp']) {
                                $emailAddress['address_temp'] .= $emailAddress['quote_temp'];
                                $emailAddress['address_temp_quoted'] = true;
                                $emailAddress['quote_temp'] = '';
                            }
                            $emailAddress['address_temp'] .= $curChar;
                            ++$emailAddress['address_temp_period'];
                        } else {
                            // Strict RFC 2822 - require all periods to be quoted in other parts of the string
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'Stray period found in email address.  If the period is part of a person\'s name, it must appear in double quotes - e.g. "John Q. Public". Otherwise, an email address shouldn\'t begin with a period.';
                        }
                    } elseif (preg_match('/[A-Za-z0-9_\-!#$%&\'*+\/=?^`{|}~]/', $curChar)) {
                        // see RFC 2822

                        if (isset($this->options->getBannedChars()[$curChar])) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "This character is not allowed in email addresses submitted (please put in quotes if needed): '{$curChar}'";
                        } elseif (('/' == $curChar || '|' == $curChar) &&
                        !$emailAddress['local_part_parsed'] && !$emailAddress['address_temp'] && !$emailAddress['quote_temp']) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "This character is not allowed in the beginning of an email addresses (please put in quotes if needed): '{$curChar}'";
                        } elseif (self::STATE_LOCAL_PART == $subState) {
                            // Legitimate character - Determine where to append based on the current 'substate'

                            if ($emailAddress['quote_temp']) {
                                $emailAddress['local_part_parsed'] .= $emailAddress['quote_temp'];
                                $emailAddress['quote_temp'] = '';
                                $emailAddress['local_part_quoted'] = true;
                            }
                            $emailAddress['local_part_parsed'] .= $curChar;
                        } elseif (self::STATE_NAME == $subState) {
                            if ($emailAddress['quote_temp']) {
                                $emailAddress['name_parsed'] .= $emailAddress['quote_temp'];
                                $emailAddress['quote_temp'] = '';
                                $emailAddress['name_quoted'] = true;
                            }
                            $emailAddress['name_parsed'] .= $curChar;
                        } elseif (self::STATE_DOMAIN == $subState) {
                            $emailAddress['domain'] .= $curChar;
                        } else {
                            if ($emailAddress['quote_temp']) {
                                $emailAddress['address_temp'] .= $emailAddress['quote_temp'];
                                $emailAddress['address_temp_quoted'] = true;
                                $emailAddress['quote_temp'] = '';
                            }
                            $emailAddress['address_temp'] .= $curChar;
                        }
                    } else {
                        if (self::STATE_DOMAIN == $subState) {
                            try {
                                // Test by trying to encode the current character into Punycode
                                // Punycode should match the traditional domain name subset of characters
                                if (preg_match('/[a-z0-9\-]/', idn_to_ascii($curChar))) {
                                    $emailAddress['domain'] .= $curChar;
                                } else {
                                    $emailAddress['invalid'] = true;
                                }
                            } catch (\Exception $e) {
                                $this->log('warning', "Email\\Parse->parse - exception trying to convert character '{$curChar}' to punycode\n\$emailAddress['original_address']: {$emailAddress['original_address']}\n\$emails: {$emails}");
                                $emailAddress['invalid'] = true;
                            }
                            if ($emailAddress['invalid']) {
                                $emailAddress['invalid_reason'] = "Invalid character found in domain of email address (please put in quotes if needed): '{$curChar}'";
                            }
                        } elseif (self::STATE_START === $subState) {
                            if ($emailAddress['quote_temp']) {
                                $emailAddress['address_temp'] .= $emailAddress['quote_temp'];
                                $emailAddress['address_temp_quoted'] = true;
                                $emailAddress['quote_temp'] = '';
                            }
                            $emailAddress['special_char_in_substate'] = $curChar;
                            $emailAddress['address_temp'] .= $curChar;
                        } elseif (self::STATE_NAME === $subState) {
                            if ($emailAddress['quote_temp']) {
                                $emailAddress['name_parsed'] .= $emailAddress['quote_temp'];
                                $emailAddress['quote_temp'] = '';
                                $emailAddress['name_quoted'] = true;
                            }
                            $emailAddress['special_char_in_substate'] = $curChar;
                            $emailAddress['name_parsed'] .= $curChar;
                        } else {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Invalid character found in email address (please put in quotes if needed): '{$curChar}'";
                        }
                    }
                    break;
                case self::STATE_SQUARE_BRACKET:
                    // Handle square bracketed IP addresses such as [10.0.10.2]
                    $emailAddress['original_address'] .= $curChar;
                    if (']' == $curChar) {
                        $subState = self::STATE_AFTER_DOMAIN;
                        $state = self::STATE_ADDRESS;
                    } elseif (preg_match('/[0-9\.]/', $curChar)) {
                        $emailAddress['ip'] .= $curChar;
                    } else {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = "Invalid Character '{$curChar}' in what seemed to be an IP Address";
                    }
                    break;
                case self::STATE_QUOTE:
                    // Handle quoted strings
                    $emailAddress['original_address'] .= $curChar;
                    if ('"' == $curChar) {
                        $backslashCount = 0;
                        for ($j = $i; $j >= 0; --$j) {
                            if ('\\' == mb_substr($emails, $j, 1, $encoding)) {
                                ++$backslashCount;
                            } else {
                                break;
                            }
                        }
                        if ($backslashCount && 1 == $backslashCount % 2) {
                            // This is a quoted quote
                            $emailAddress['quote_temp'] .= $curChar;
                        } else {
                            $state = self::STATE_ADDRESS;
                        }
                    } else {
                        $emailAddress['quote_temp'] .= $curChar;
                    }
                    break;
                case self::STATE_COMMENT:
                    // Handle comments and nesting thereof
                    $emailAddress['original_address'] .= $curChar;
                    if (')' == $curChar) {
                        --$commentNestLevel;
                        if ($commentNestLevel <= 0) {
                            $state = self::STATE_ADDRESS;
                        }
                    } elseif ('(' == $curChar) {
                        ++$commentNestLevel;
                    }
                    break;
                default:
                    // Shouldn't ever get here - what is $state?
                    $emailAddress['original_address'] .= $curChar;
                    $emailAddress['invalid'] = true;
                    $emailAddress['invalid_reason'] = 'Error during parsing';
                    $this->log('error', "Email\\Parse->parse - error during parsing - \$state: {$state}\n\$subState: {$subState}\$i: {$i}\n\$curChar: {$curChar}");
                    break;
            }

            // if there's a $emailAddress['original_address'] and the state is set to STATE_END_ADDRESS
            if (self::STATE_END_ADDRESS == $state && strlen($emailAddress['original_address']) > 0) {
                $invalid = $this->addAddress(
                    $emailAddresses,
                    $emailAddress,
                    $encoding,
                    $i
                );

                if ($invalid) {
                    if (!$success) {
                        $reason = 'Invalid email addresses';
                    } else {
                        $reason = 'Invalid email address';
                        $success = false;
                    }
                }

                // Reset all local variables used during parsing
                $emailAddress = $this->buildEmailAddressArray();
                $subState = self::STATE_START;
                $state = self::STATE_TRIM;
            }

            if ($emailAddress['invalid']) {
                $this->log('debug', "Email\\Parse->parse - invalid - {$emailAddress['invalid_reason']}\n\$emailAddress['original_address'] {$emailAddress['original_address']}\n\$emails: {$emails}");
                $state = self::STATE_SKIP_AHEAD;
            }
        }

        // Catch all the various fall-though places
        if (!$emailAddress['invalid'] && $emailAddress['quote_temp'] && self::STATE_QUOTE == $state) {
            $emailAddress['invalid'] = true;
            $emailAddress['invalid_reason'] = 'No ending quote: \'"\'';
        }
        if (!$emailAddress['invalid'] && $emailAddress['quote_temp'] && self::STATE_COMMENT == $state) {
            $emailAddress['invalid'] = true;
            $emailAddress['invalid_reason'] = 'No closing parenthesis: \')\'';
        }
        if (!$emailAddress['invalid'] && $emailAddress['quote_temp'] && self::STATE_SQUARE_BRACKET == $state) {
            $emailAddress['invalid'] = true;
            $emailAddress['invalid_reason'] = 'No closing square bracket: \']\'';
        }
        if (!$emailAddress['invalid'] && $emailAddress['address_temp'] || $emailAddress['quote_temp']) {
            $this->log('error', "Email\\Parse->parse - corruption during parsing - leftovers:\n\$i: {$i}\n\$emailAddress['address_temp']: {$emailAddress['address_temp']}\n\$emailAddress['quote_temp']: {$emailAddress['quote_temp']}\nEmails: {$emails}");
            $emailAddress['invalid'] = true;
            $emailAddress['invalid_reason'] = 'Incomplete address';
            if (!$success) {
                $reason = 'Invalid email addresses';
            } else {
                $reason = 'Invalid email address';
                $success = false;
            }
        }

        // Did we find no email addresses at all?
        if (!$emailAddress['invalid'] && !count($emailAddresses) && (!$emailAddress['original_address'] || !$emailAddress['local_part_parsed'])) {
            $success = false;
            $reason = 'No email addresses found';
            if (!$multiple) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'No email address found';
                $this->addAddress(
                    $emailAddresses,
                    $emailAddress,
                    $encoding,
                    $i
                );
            }
        } elseif ($emailAddress['original_address']) {
            $invalid = $this->addAddress(
                $emailAddresses,
                $emailAddress,
                $encoding,
                $i
            );
            if ($invalid) {
                if (!$success) {
                    $reason = 'Invalid email addresses';
                } else {
                    $reason = 'Invalid email address';
                    $success = false;
                }
            }
        }
        if ($multiple) {
            return ['success' => $success, 'reason' => $reason, 'email_addresses' => $emailAddresses];
        } else {
            return $emailAddresses[0];
        }
    }

    /**
     * Handles the case of a quoted name.
     */
    private function handleQuote(array &$emailAddress)
    {
        if ($emailAddress['quote_temp']) {
            $emailAddress['name_parsed'] .= $emailAddress['quote_temp'];
            $emailAddress['name_quoted'] = true;
            $emailAddress['quote_temp'] = '';
        } elseif ($emailAddress['address_temp']) {
            $emailAddress['name_parsed'] .= $emailAddress['address_temp'];
            $emailAddress['name_quoted'] = $emailAddress['address_temp_quoted'];
            $emailAddress['address_temp_quoted'] = false;
            $emailAddress['address_temp'] = '';
            if ($emailAddress['address_temp_period'] > 0) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Periods within the name of an email address must appear in quotes, such as "John Q. Public" <john@qpublic.com>';
            }
        }
    }

    /**
     * Helper function for creating a blank email address array used by Email\Parse->parse.
     */
    private function buildEmailAddressArray()
    {
        $emailAddress = ['original_address' => '',
                        'name_parsed' => '',
                        'local_part_parsed' => '',
                        'domain' => '',
                        'ip' => '',
                        'invalid' => false,
                        'invalid_reason' => null,
                        'local_part_quoted' => false,
                        'name_quoted' => false,
                        'address_temp_quoted' => false,
                        'quote_temp' => '',
                        'address_temp' => '',
                        'address_temp_period' => 0,
                        'special_char_in_substate' => null,
                        ];

        return $emailAddress;
    }

    /**
     * Does a bunch of additional validation on the email address parts contained in $emailAddress
     *  Then adds it to $emailAdddresses.
     *
     * @return mixed
     */
    private function addAddress(
        &$emailAddresses,
        &$emailAddress,
        $encoding,
        $i
    ) {
        if (!$emailAddress['invalid']) {
            if ($emailAddress['address_temp'] || $emailAddress['quote_temp']) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Incomplete address';
                $this->log('error', "Email\\Parse->addAddress - corruption during parsing - leftovers:\n\$i: {$i}\n\$emailAddress['address_temp'] : {$emailAddress['address_temp']}\n\$emailAddress['quote_temp']: {$emailAddress['quote_temp']}\n");
            } elseif ($emailAddress['ip'] && $emailAddress['domain']) {
                // Error - this should never occur
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Confusion during parsing';
                $this->log('error', "Email\\Parse->addAddress - both an IP address '{$emailAddress['ip']}' and a domain '{$emailAddress['domain']}' found for the email address '{$emailAddress['original_address']}'\n");
            } elseif ($emailAddress['ip'] || ($emailAddress['domain'] && preg_match('/\d+\.\d+\.\d+\.\d+/', $emailAddress['domain']))) {
                // also test if the current domain looks like an IP address

                if ($emailAddress['domain']) {
                    // Likely an IP address if we get here

                    $emailAddress['ip'] = $emailAddress['domain'];
                    $emailAddress['domain'] = null;
                }
                if (!$this->ipValidator) {
                    $this->ipValidator = new Ip();
                }
                try {
                    if (!$this->ipValidator->isValid($emailAddress['ip'])) {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = 'IP address invalid: \''.$emailAddress['ip'].'\' does not appear to be a valid IP address';
                    } elseif (preg_match('/192\.168\.\d+\.\d+/', $emailAddress['ip']) ||
                        preg_match('/172\.(1[6-9]|2[0-9]|3[0-2])\.\d+\.\d+/', $emailAddress['ip']) ||
                        preg_match('/10\.\d+\.\d+\.\d+/', $emailAddress['ip'])) {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = 'IP address invalid (private): '.$emailAddress['ip'];
                    } elseif (preg_match('/169\.254\.\d+\.\d+/', $emailAddress['ip'])) {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = 'IP address invalid (APIPA): '.$emailAddress['ip'];
                    }
                } catch (\Exception $e) {
                    $emailAddress['invalid'] = true;
                    $emailAddress['invalid_reason'] = 'IP address invalid: '.$emailAddress['ip'];
                }
            } elseif ($emailAddress['domain']) {
                // Check for IDNA
                if (max(array_keys(count_chars($emailAddress['domain'], 1))) > 127) {
                    try {
                        $emailAddress['domain'] = idn_to_ascii($emailAddress['domain']);
                    } catch (\Exception $e) {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = "Can't convert domain {$emailAddress['domain']} to punycode";
                    }
                }

                $result = $this->validateDomainName($emailAddress['domain']);
                if (!$result['valid']) {
                    $emailAddress['invalid'] = true;
                    $emailAddress['invalid_reason'] = isset($result['reason']) ? 'Domain invalid: '.$result['reason'] : 'Domain invalid for some unknown reason';
                }
            }
        }

        // Prepare some of the fields needed
        $emailAddress['name_parsed'] = rtrim($emailAddress['name_parsed']);
        $emailAddress['original_address'] = rtrim($emailAddress['original_address']);
        $name = $emailAddress['name_quoted'] ? "\"{$emailAddress['name_parsed']}\"" : $emailAddress['name_parsed'];
        $localPart = $emailAddress['local_part_quoted'] ? "\"{$emailAddress['local_part_parsed']}\"" : $emailAddress['local_part_parsed'];
        $domainPart = $emailAddress['ip'] ? '['.$emailAddress['ip'].']' : $emailAddress['domain'];

        if (!$emailAddress['invalid']) {
            if (0 == mb_strlen($domainPart, $encoding)) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Email address needs a domain after the \'@\'';
            } elseif (mb_strlen($localPart, $encoding) > 63) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Email address before the \'@\' can not be greater than 63 characters';
            } elseif ((mb_strlen($localPart, $encoding) + mb_strlen($domainPart, $encoding) + 1) > 254) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Email addresses can not be greater than 254 characters';
            }
        }

        // Build the email address hash
        $emailAddrDef = ['address' => '',
                        'simple_address' => '',
                        'original_address' => rtrim($emailAddress['original_address']),
                        'name' => $name,
                        'name_parsed' => $emailAddress['name_parsed'],
                        'local_part' => $localPart,
                        'local_part_parsed' => $emailAddress['local_part_parsed'],
                        'domain_part' => $domainPart,
                        'domain' => $emailAddress['domain'],
                        'ip' => $emailAddress['ip'],
                        'invalid' => $emailAddress['invalid'],
                        'invalid_reason' => $emailAddress['invalid_reason'], ];

        // Build the proper address by hand (has comments stripped out and should have quotes in the proper places)
        if (!$emailAddrDef['invalid']) {
            $emailAddrDef['simple_address'] = "{$emailAddrDef['local_part']}@{$emailAddrDef['domain_part']}";
            $properAddress = $emailAddrDef['name'] ? "{$emailAddrDef['name']} <{$emailAddrDef['local_part']}@{$emailAddrDef['domain_part']}>" : $emailAddrDef['simple_address'];
            $emailAddrDef['address'] = $properAddress;
        }

        $emailAddresses[] = $emailAddrDef;

        return $emailAddrDef['invalid'];
    }

    /**
     * Determines whether the domain name is valid.
     *
     * @param string $domain   The domain name to validate
     * @param string $encoding The encoding of the string (if not UTF-8)
     *
     * @return array array('valid' => boolean: whether valid or not,
     *               'reason' => string: if not valid, the reason why);
     */
    protected function validateDomainName($domain, $encoding = 'UTF-8')
    {
        if (mb_strlen($domain, $encoding) > 255) {
            return ['valid' => false, 'reason' => 'Domain name too long'];
        } else {
            $origEncoding = mb_regex_encoding();
            mb_regex_encoding($encoding);
            $parts = mb_split('\\.', $domain);
            mb_regex_encoding($origEncoding);
            foreach ($parts as $part) {
                if (mb_strlen($part, $encoding) > 63) {
                    return ['valid' => false, 'reason' => "Domain name part '{$part}' too long"];
                }
                if (!preg_match('/^[a-zA-Z0-9\-]+$/', $part)) {
                    return ['valid' => false, 'reason' => "Domain name '{$domain}' can only contain letters a through z, numbers 0 through 9 and hyphen.  The part '{$part}' contains characters outside of that range."];
                }
                if ('-' == mb_substr($part, 0, 1, $encoding) || '-' == mb_substr($part, mb_strlen($part) - 1, 1, $encoding)) {
                    return ['valid' => false, 'reason' => "Parts of the domain name '{$domain}' can not start or end with '-'.  This part does: {$part}"];
                }
            }
        }

        // @TODO - possibly check DNS / MX records for domain to make sure it exists?
        return ['valid' => true];
    }
}
