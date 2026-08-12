<?php

namespace Email;

use Email\ParseErrorCode as Err;
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
     * Absorbs the obsolete source-route prefix inside angle-addr
     * (RFC 5322 §4.4 obs-route: `"<" obs-domain-list ":" addr-spec ">"`).
     * Consumes characters from the leading `@` up to the `:` terminator,
     * then resumes normal addr-spec parsing.
     */
    private const STATE_OBS_ROUTE = 12;

    /**
     * @var ?Parse
     */
    protected static ?Parse $instance = null;

    /**
     * @var ?LoggerInterface
     */
    protected ?LoggerInterface $logger = null;

    /**
     * @var ParseOptions
     */
    protected ParseOptions $options;

    /**
     * Allow Parse to be instantiated as a singleton.
     *
     * @return Parse The instance
     */
    public static function getInstance(): Parse
    {
        if (!self::$instance) {
            return self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @param LoggerInterface|null $logger  PSR-3 compliant logger
     * @param ParseOptions|null    $options Parser configuration options
     */
    public function __construct(
        ?LoggerInterface $logger = null,
        ?ParseOptions $options = null
    ) {
        $this->logger = $logger;
        $this->options = $options ?: new ParseOptions(['%', '!']);
    }

    /**
     * Allows for post-construct injection of a logger.
     *
     * @param LoggerInterface $logger PSR-3 compliant logger
     */
    public function setLogger(LoggerInterface $logger): Parse
    {
        $this->logger = $logger;

        return $this;
    }

    public function setOptions(ParseOptions $options): Parse
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return ParseOptions
     */
    public function getOptions(): ParseOptions
    {
        return $this->options;
    }

    /**
     * Abstraction to prevent logging when there's no logger.
     *
     * @param mixed  $level
     * @param string $message
     */
    protected function log(mixed $level, string $message): void
    {
        $this->logger?->log($level, $message);
    }

    /**
     * Validates IP address with global range check.
     *
     * For PHP 8.2+, uses FILTER_FLAG_GLOBAL_RANGE constant.
     * For PHP 8.1, manually checks if IP is in global range.
     *
     * @param string $ip The IP address to validate
     * @param int $ipType FILTER_FLAG_IPV4 or FILTER_FLAG_IPV6
     * @return bool True if IP is valid and in global range, false otherwise
     */
    private function validateIpGlobalRange(string $ip, int $ipType): bool
    {
        // PHP 8.2+ exposes FILTER_FLAG_GLOBAL_RANGE. Look it up via constant() so
        // static analyzers running against a PHP 8.1 baseline do not flag it as
        // an undefined reference.
        if (defined('FILTER_FLAG_GLOBAL_RANGE')) {
            /** @var int $globalRangeFlag */
            $globalRangeFlag = constant('FILTER_FLAG_GLOBAL_RANGE');

            return filter_var($ip, FILTER_VALIDATE_IP, $ipType | $globalRangeFlag) !== false;
        }

        // PHP 8.1: Manually check for private/reserved ranges
        if (preg_match("/^::ffff:(\d+\.\d+.\d+.\d+)$/i", $ip, $matches)) {
            $ip = $matches[1];
            // FILTER_FLAG_NO_RES_RANGE does not cover all IETF-assigned special-purpose ranges.
            // Explicitly reject IETF Protocol Assignments (RFC 5736: 192.0.0.0/24) and
            // documentation TEST-NET ranges (RFC 5737: 192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24).
            if (str_starts_with($ip, "192.0.0.") || str_starts_with($ip, "192.0.2.") || str_starts_with($ip, "198.51.100.") || str_starts_with($ip, "203.0.113.")) {
                return false;
            }
            $ipType = FILTER_FLAG_IPV4;
        }

        // Check if it's NOT in private or reserved ranges
        return filter_var($ip, FILTER_VALIDATE_IP, $ipType | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Parses a list of 1 to n email addresses separated by space or comma.
     *
     * Compliance level is controlled by the ParseOptions passed to the constructor:
     *   - ParseOptions::rfc5321()  — RFC 5321 Mailbox (strict ASCII, SMTP-compatible)
     *   - ParseOptions::rfc6531()  — RFC 6531/6532 (full UTF-8, NFC normalization)
     *   - ParseOptions::rfc5322()  — RFC 5322 addr-spec with obs-local-part (recommended default)
     *   - ParseOptions::rfc2822()  — RFC 2822 maximum compatibility
     *   - new ParseOptions()       — Legacy v2.x behavior
     *
     * Quoted strings in the middle of an address (e.g. test"test"test@xyz.com)
     * are tolerated by the parser but obs-local-part is only accepted when
     * ParseOptions::$allowObsLocalPart is true (RFC 5322 §4.4).
     * Backslash-escaping outside of quotes (test\@test@xyz.com) is not supported;
     * write it as "test\@test"@xyz.com instead (RFC 5322 §3.2.4).
     *
     * Here are a few other examples:
     *
     *  "John Q. Public" <johnpublic@xyz.com>
     *  this.is.an.address@xyz.com
     *  how-about-an-ip@[8.8.8.8]
     *  how-about-comments(this is a comment!!)@xyz.com
     *
     * @param string $emails   List of email addresses separated by comma or space if multiple
     * @param bool   $multiple (optional, default: true) Whether to parse for multiple email addresses or not
     * @param string $encoding (optional, default: 'UTF-8') Character encoding of the $emails input string
     *
     * @return array if ($multiple):
     *               array('success' => boolean, // true only if all addresses are valid
     *               'reason' => string|null, // failure summary if any address is invalid, null otherwise
     *               'email_addresses' =>
     *               array('address' => string, // canonical address, comments stripped
     *               'original_address' => string, // raw address as given, comments included
     *               'simple_address' => string, // local-part@domain-part (e.g. someone@somewhere.com)
     *               'name' => string, // display name including quotes (e.g.: "John Q. Public")
     *               'name_parsed' => string, // display name without quotes (e.g.: John Q. Public)
     *               'local_part' => string, // local-part including quotes if quoted (e.g. "john")
     *               'local_part_parsed' => string, // local-part without quotes (e.g. john)
     *               'domain' => string, // domain after '@' (may be Unicode/U-label)
     *               'domain_ascii' => string|null, // punycode A-label domain when includeDomainAscii=true
     *               'ip' => string, // IP address if domain-literal used (e.g. 8.8.8.8)
     *               'domain_part' => string, // domain or [IP] as it appears after '@'
     *               'invalid' => boolean, // true if the address failed validation
     *               'invalid_reason' => string|null, // reason for failure, null if valid
     *               'invalid_reason_code' => ParseErrorCode|null, // structured error code, null if valid
     *               'comments' => array), // extracted RFC 5322 comments (e.g. ['note'])
     *               array( .... ) // the next email address matched
     *               )
     *               else:
     *               array('address' => string, 'original_address' => string,
     *               'simple_address' => string, 'name' => string, 'name_parsed' => string,
     *               'local_part' => string, 'local_part_parsed' => string,
     *               'domain' => string, 'domain_ascii' => string|null,
     *               'ip' => string, 'domain_part' => string,
     *               'invalid' => boolean, 'invalid_reason' => string|null,
     *               'invalid_reason_code' => ParseErrorCode|null, 'comments' => array)
     *               endif;
     */
    /**
     * Parse a single email address and return a typed value object.
     *
     * Recommended over {@see parse()} when you want IDE autocomplete and
     * static-analysis friendly access to the parsed fields.
     */
    public function parseSingle(string $email, string $encoding = 'UTF-8'): ParsedEmailAddress
    {
        return ParsedEmailAddress::fromArray($this->parse($email, false, $encoding));
    }

    /**
     * Parse a list of email addresses and return a typed result.
     *
     * Recommended over {@see parse()} in multi-address mode for the same reasons as
     * {@see parseSingle()}. Separator handling and per-address rules are configured
     * via {@see ParseOptions}.
     */
    public function parseMultiple(string $emails, string $encoding = 'UTF-8'): ParseResult
    {
        /** @var array{success: bool, reason: ?string, email_addresses: array<int, array<string, mixed>>} $raw */
        $raw = $this->parse($emails, true, $encoding);

        return ParseResult::fromArray($raw);
    }

    /**
     * Lazily parse a batch of email address strings, yielding one
     * {@see ParsedEmailAddress} per matched address.
     *
     * Use this when processing large batches (e.g. a CSV of mailing-list
     * addresses) where holding every parsed result in memory is undesirable.
     * Each item in `$input` is parsed with multi-address separator handling,
     * so a single item may contain several comma- or whitespace-separated
     * addresses.
     *
     *   foreach ($parser->parseStream($csvRows) as $addr) {
     *       if ($addr->invalid) continue;
     *       $repo->upsert($addr->simpleAddress);
     *   }
     *
     * @param  iterable<string> $input    Each item is an address string (optionally multi-address).
     * @param  string           $encoding Character encoding of the input strings.
     * @return \Generator<ParsedEmailAddress>
     */
    public function parseStream(iterable $input, string $encoding = 'UTF-8'): \Generator
    {
        foreach ($input as $emails) {
            $result = $this->parse((string) $emails, true, $encoding);
            foreach ($result['email_addresses'] as $address) {
                yield ParsedEmailAddress::fromArray($address);
            }
        }
    }

    public function parse(string $emails, bool $multiple = true, string $encoding = 'UTF-8'): array
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

        // Split once into an array of characters rather than calling
        // mb_substr($emails, $i, 1) on every iteration. For multi-byte encodings
        // each mb_substr rescans from the start of the string (O(n) per call, so
        // O(n^2) over the loop); mb_str_split does it in a single O(n) pass.
        $chars = mb_str_split($emails, 1, $encoding);
        $len = count($chars);
        if (0 == $len) {
            $success = false;
            $reason = 'No emails passed in';
        }
        // Hoist the immutable separator/banned-char config out of the per-character loop.
        $separators = $this->options->getSeparators();
        $bannedChars = $this->options->getBannedChars();
        $useWhitespaceAsSeparator = $this->options->getUseWhitespaceAsSeparator();
        // Whitespace treated as insignificant (folding/separators; trimmable). In
        // single-address mode CR and LF are excluded — a lone addr-spec has no line
        // endings — unless trimSingleAddressWhitespace opts back into liberal trimming.
        $allowedWhitespace = $this->options->getAllowedWhitespace();
        if (!$multiple && !$this->options->trimSingleAddressWhitespace) {
            unset($allowedWhitespace["\r"], $allowedWhitespace["\n"]);
        }
        $curChar = null;
        for ($i = 0; $i < $len; ++$i) {
            $prevChar = $curChar; // Previous Character
            $curChar = $chars[$i]; // Current Character
            switch ($state) {
                case self::STATE_SKIP_AHEAD:
                    // Skip ahead is set when a bad email address is encountered
                    //  It's supposed to skip to the next delimiter and continue parsing from there
                    $isWhitespaceSeparator = $useWhitespaceAsSeparator && isset($allowedWhitespace[$curChar]);

                    if ($multiple && ($isWhitespaceSeparator || isset($separators[$curChar]))) {
                        $state = self::STATE_END_ADDRESS;
                    } else {
                        $emailAddress['original_address'] .= $curChar;
                    }

                    break;
                    /* @noinspection PhpMissingBreakStatementInspection — STATE_TRIM falls through to STATE_ADDRESS */
                case self::STATE_TRIM:
                    if (isset($allowedWhitespace[$curChar])) {
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
                            // A leading comment opens at nest level 1 (matches the
                            // STATE_ADDRESS entry); without this an unbalanced nested
                            // comment like "((x)" would appear closed after one ")".
                            $commentNestLevel = 1;

                            break;
                        }
                        // Non-whitespace, non-special char: fall through to STATE_ADDRESS processing
                    }
                    // no break
                case self::STATE_ADDRESS:
                    if (!isset($separators[$curChar]) || !$multiple) {
                        $emailAddress['original_address'] .= $curChar;
                    }

                    if ($emailAddress['after_closing_quote']) {
                        $emailAddress['after_closing_quote'] = false;
                        // RFC 5322 §3.2.4: a quoted-string is a whole word. Only a dot
                        // (obs word.word), '@', angle brackets, CFWS, or a separator may
                        // follow it — atext or a second quote directly abutting it is invalid.
                        if ('"' === $curChar || $curChar > "\x7f" || preg_match('/[A-Za-z0-9_\-!#$%&\'*+\/=?^`{|}~]/', $curChar)) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'A quoted string in the local part must be followed by a dot, "@", or the end — text or a second quote cannot immediately follow it';
                            $emailAddress['invalid_reason_code'] = Err::AtextAfterQuotedString;
                        }
                    }

                    if ('(' == $curChar) {
                        // Handle comment
                        $state = self::STATE_COMMENT;
                        $commentNestLevel = 1;

                        break;
                    } elseif (isset($separators[$curChar])) {
                        // Handle separator (comma, semicolon, etc.)
                        if ($multiple && (self::STATE_DOMAIN == $subState || self::STATE_AFTER_DOMAIN == $subState)) {
                            // If we're already in the domain part, this should be the end of the address
                            $state = self::STATE_END_ADDRESS;

                            break;
                        } else {
                            $emailAddress['invalid'] = true;
                            if ($multiple || ($i + 5) >= $len) {
                                $emailAddress['invalid_reason'] = 'Misplaced separator or missing "@" symbol';
                                $emailAddress['invalid_reason_code'] = Err::MisplacedSeparator;
                            } else {
                                $emailAddress['invalid_reason'] = 'Separator not permitted - only one email address allowed';
                                $emailAddress['invalid_reason_code'] = Err::SeparatorNotPermitted;
                            }
                        }
                    } elseif (isset($allowedWhitespace[$curChar])) {
                        // RFC 5322 §3.2.2 CFWS — folding whitespace. Look ahead past the
                        // WSP run to find the next significant character; that character
                        // determines which kind of CFWS this is and whether it can be
                        // silently absorbed or if it marks an end-of-address / error.
                        $foundComment = false;
                        $lookAheadChar = null;
                        for ($j = ($i + 1); $j < $len; ++$j) {
                            $c = $chars[$j];
                            if ('(' === $c) {
                                $foundComment = true;

                                break;
                            }
                            if (' ' !== $c && "\t" !== $c && "\r" !== $c && "\n" !== $c) {
                                $lookAheadChar = $c;

                                break;
                            }
                        }

                        // CFWS absorption: whitespace is legal per RFC 5322 §3.2.3 at
                        // dot-atom boundaries ("[CFWS] dot-atom-text [CFWS]") and per
                        // §4.4 obs-angle-addr around the angle brackets. Detect the
                        // position from subState + lookahead rather than emitting a
                        // WhitespaceInAddress error.
                        $cfwsAbsorbed = false;
                        if (!$foundComment && $lookAheadChar !== null) {
                            if (self::STATE_LOCAL_PART === $subState) {
                                if ('@' === $lookAheadChar) {
                                    // Trailing CFWS of the local-part dot-atom: "local @domain".
                                    $cfwsAbsorbed = true;
                                } elseif (
                                    $emailAddress['in_angle_addr']
                                    && $emailAddress['local_part_parsed'] === ''
                                    && $emailAddress['address_temp'] === ''
                                    && $emailAddress['quote_temp'] === ''
                                ) {
                                    // Leading CFWS inside angle-addr: "<  local@domain>".
                                    $cfwsAbsorbed = true;
                                }
                            } elseif (self::STATE_DOMAIN === $subState) {
                                if ($emailAddress['domain'] === '' && $emailAddress['ip'] === '') {
                                    // Leading CFWS of the domain dot-atom: "local@ domain".
                                    $cfwsAbsorbed = true;
                                }
                            } elseif (
                                self::STATE_START === $subState
                                && '@' === $lookAheadChar
                                && $emailAddress['address_temp'] !== ''
                            ) {
                                // Top-level addr-spec with no angle-addr: "local @domain".
                                // The accumulated address_temp IS the local-part; absorb the
                                // whitespace as trailing CFWS before the `@`.
                                $cfwsAbsorbed = true;
                            }
                        }

                        if ($cfwsAbsorbed) {
                            // Silently skip the whitespace character; state unchanged.
                        } elseif ($foundComment) {
                            if (self::STATE_DOMAIN == $subState) {
                                $subState = self::STATE_AFTER_DOMAIN;
                            } elseif (self::STATE_LOCAL_PART == $subState) {
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = 'Email address contains whitespace';
                                $emailAddress['invalid_reason_code'] = Err::WhitespaceInAddress;
                            }
                        } elseif (
                            $emailAddress['in_angle_addr']
                            && self::STATE_DOMAIN == $subState
                            && $lookAheadChar === '>'
                        ) {
                            // Trailing CFWS inside angle-addr before `>`: "<local@domain >".
                            // Absorb and transition as if we saw `>` next.
                            $subState = self::STATE_AFTER_DOMAIN;
                        } elseif ($useWhitespaceAsSeparator &&
                                  (self::STATE_DOMAIN == $subState || self::STATE_AFTER_DOMAIN == $subState)) {
                            // Already past `@` and whitespace-as-separator: end address.
                            $state = self::STATE_END_ADDRESS;

                            break;
                        } else {
                            if (self::STATE_LOCAL_PART == $subState) {
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = 'Email address contains whitespace';
                                $emailAddress['invalid_reason_code'] = Err::WhitespaceInAddress;
                            } else {
                                // Display-name phrase: absorb into name_parsed.
                                $this->handleQuote($emailAddress);
                                $emailAddress['name_parsed'] .= $curChar;
                            }
                        }
                    } elseif ('<' == $curChar) {
                        // Start of the local part
                        if (self::STATE_LOCAL_PART == $subState || self::STATE_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'Email address contains multiple opening "<" (either a typo or multiple emails that need to be separated by a comma or space)';
                            $emailAddress['invalid_reason_code'] = Err::MultipleOpeningAngle;
                        } else {
                            // Here should be the start of the local part for sure everything else then is part of the name
                            $subState = self::STATE_LOCAL_PART;
                            $emailAddress['special_char_in_substate'] = null;
                            $emailAddress['in_angle_addr'] = true;
                            // Any quote before `<` was the display name, not the local part;
                            // clear the quoted flag the closing-quote handler set so the real
                            // local-part inside the angle-addr starts unquoted.
                            $emailAddress['local_part_quoted'] = false;
                            $this->handleQuote($emailAddress);
                        }
                    } elseif ('>' == $curChar) {
                        // should be end of domain part
                        if (self::STATE_DOMAIN != $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Did not find domain name before a closing '>'";
                            $emailAddress['invalid_reason_code'] = Err::MissingDomainBeforeClosingAngle;
                        } else {
                            $subState = self::STATE_AFTER_DOMAIN;
                            $emailAddress['in_angle_addr'] = false;
                        }
                    } elseif ('"' == $curChar) {
                        // If we hit a quote - change to the quote state, unless it's in the domain, in which case it's error
                        if (self::STATE_DOMAIN == $subState || self::STATE_AFTER_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'Quote \'"\' found where it shouldn\'t be';
                            $emailAddress['invalid_reason_code'] = Err::MisplacedQuote;
                        } else {
                            $state = self::STATE_QUOTE;
                        }
                    } elseif ('@' == $curChar) {
                        // Handle '@' sign
                        if (self::STATE_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Multiple at '@' symbols in email address";
                            $emailAddress['invalid_reason_code'] = Err::MultipleAtSymbols;
                        } elseif (self::STATE_AFTER_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Stray at '@' symbol found after domain name";
                            $emailAddress['invalid_reason_code'] = Err::StrayAtAfterDomain;
                        } elseif (null !== $emailAddress['special_char_in_substate']) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Invalid character found in email address local part: '{$emailAddress['special_char_in_substate']}'";
                            $emailAddress['invalid_reason_code'] = Err::InvalidCharacterInLocalPart;
                        } elseif (
                            $this->options->allowObsRoute
                            && $emailAddress['in_angle_addr']
                            && $emailAddress['obs_route'] === ''
                            && $emailAddress['local_part_parsed'] === ''
                            && $emailAddress['quote_temp'] === ''
                            && $emailAddress['address_temp'] === ''
                        ) {
                            // RFC 5322 §4.4 obs-route: first `@` seen inside `<...>` with no
                            // preceding local-part starts the source-route prefix. Consume
                            // the remainder until `:` via STATE_OBS_ROUTE, then resume
                            // addr-spec parsing with local-part reset.
                            $state = self::STATE_OBS_ROUTE;
                            $emailAddress['obs_route'] = '@';
                        } else {
                            $subState = self::STATE_DOMAIN;
                            if ($emailAddress['address_temp'] && $emailAddress['quote_temp']) {
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = 'Something went wrong during parsing.';
                                $emailAddress['invalid_reason_code'] = Err::ParserConfusion;
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
                            $emailAddress['invalid_reason_code'] = Err::InvalidOpeningBracket;
                        }
                        $state = self::STATE_SQUARE_BRACKET;
                    } elseif ('.' == $curChar) {
                        // Handle periods specially
                        if ('.' == $prevChar && !$this->options->allowObsLocalPart) {
                            // Consecutive dots only allowed when obs-local-part is enabled
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Email address should not contain two dots '.' in a row";
                            $emailAddress['invalid_reason_code'] = Err::ConsecutiveDots;
                        } elseif (self::STATE_LOCAL_PART == $subState) {
                            if (!$emailAddress['local_part_parsed'] && !$this->options->allowObsLocalPart) {
                                // Leading dots only allowed when obs-local-part is enabled
                                $emailAddress['invalid'] = true;
                                $emailAddress['invalid_reason'] = "Email address can not start with '.'";
                                $emailAddress['invalid_reason_code'] = Err::LeadingDot;
                            } else {
                                $emailAddress['local_part_parsed'] .= $curChar;
                            }
                        } elseif (self::STATE_DOMAIN == $subState) {
                            $emailAddress['domain'] .= $curChar;
                        } elseif (self::STATE_AFTER_DOMAIN == $subState) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "Stray period '.' found after domain of email address";
                            $emailAddress['invalid_reason_code'] = Err::StrayPeriodAfterDomain;
                        } elseif (self::STATE_START == $subState) {
                            if ($emailAddress['quote_temp']) {
                                $emailAddress['address_temp'] .= $emailAddress['quote_temp'];
                                $emailAddress['address_temp_quoted'] = true;
                                $emailAddress['quote_temp'] = '';
                            }
                            $emailAddress['address_temp'] .= $curChar;
                            ++$emailAddress['address_temp_period'];
                        } else {
                            // RFC 5322 §3.4: a period is not an atext character and is not
                            // valid in an unquoted display name or at the start of an address.
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'Stray period found in email address.  If the period is part of a person\'s name, it must appear in double quotes - e.g. "John Q. Public". Otherwise, an email address shouldn\'t begin with a period.';
                            $emailAddress['invalid_reason_code'] = Err::StrayPeriod;
                        }
                    } elseif (preg_match('/[A-Za-z0-9_\-!#$%&\'*+\/=?^`{|}~]/', $curChar)) {
                        // RFC 5322 §3.2.3: atext characters — valid in unquoted local-parts and display names

                        if (isset($bannedChars[$curChar])) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "This character is not allowed in email addresses submitted (please put in quotes if needed): '{$curChar}'";
                            $emailAddress['invalid_reason_code'] = Err::CharacterNotAllowed;
                        } elseif (('/' == $curChar || '|' == $curChar) &&
                        !$emailAddress['local_part_parsed'] && !$emailAddress['address_temp'] && !$emailAddress['quote_temp'] && !$emailAddress['name_parsed']) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = "This character is not allowed at the beginning of an email address (please put in quotes if needed): '{$curChar}'";
                            $emailAddress['invalid_reason_code'] = Err::InvalidCharacterAtStart;
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
                            if ($this->isUtf8Char($curChar)) {
                                $emailAddress['domain'] .= $curChar;
                            } else {
                                try {
                                    // Test by trying to encode the current character into Punycode
                                    // Punycode should match the traditional domain name subset of characters
                                    $punycoded = idn_to_ascii($curChar);
                                    if ($punycoded !== false && preg_match('/[a-z0-9\-]/', $punycoded)) {
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
                                    $emailAddress['invalid_reason_code'] = Err::InvalidCharacterInDomain;
                                }
                            }
                        } elseif (self::STATE_START === $subState || self::STATE_LOCAL_PART === $subState) {
                            // Handle non-atext characters in both STATE_START and STATE_LOCAL_PART consistently
                            if ($subState === self::STATE_START && $emailAddress['quote_temp']) {
                                $emailAddress['address_temp'] .= $emailAddress['quote_temp'];
                                $emailAddress['address_temp_quoted'] = true;
                                $emailAddress['quote_temp'] = '';
                            } elseif ($subState === self::STATE_LOCAL_PART && $emailAddress['quote_temp']) {
                                $emailAddress['local_part_parsed'] .= $emailAddress['quote_temp'];
                                $emailAddress['quote_temp'] = '';
                                $emailAddress['local_part_quoted'] = true;
                            }

                            $isUtf8 = $this->isUtf8Char($curChar);

                            if ($isUtf8 && $this->options->allowUtf8LocalPart) {
                                // UTF-8 character allowed
                                if ($subState === self::STATE_START) {
                                    $emailAddress['address_temp'] .= $curChar;
                                } else {
                                    $emailAddress['local_part_parsed'] .= $curChar;
                                }
                            } elseif ($isUtf8) {
                                // UTF-8 present but not allowed by rules — collect and reject in validateLocalPart()
                                if ($subState === self::STATE_START) {
                                    $emailAddress['address_temp'] .= $curChar;
                                    // ??= preserves the first invalid character seen; later chars must not overwrite it
                                    $emailAddress['special_char_in_substate'] ??= $curChar;
                                } else {
                                    $emailAddress['invalid'] = true;
                                    $emailAddress['invalid_reason'] = "Invalid character found in email address local part: '{$curChar}'";
                                    $emailAddress['invalid_reason_code'] = Err::InvalidCharacterInLocalPart;
                                }
                            } else {
                                // Non-UTF-8, non-atext character
                                if ($subState === self::STATE_START) {
                                    // ??= preserves the first invalid character seen; later chars must not overwrite it
                                    $emailAddress['special_char_in_substate'] ??= $curChar;
                                    $emailAddress['address_temp'] .= $curChar;
                                } else {
                                    $emailAddress['invalid'] = true;
                                    $emailAddress['invalid_reason'] = "Invalid character found in email address local part: '{$curChar}'";
                                    $emailAddress['invalid_reason_code'] = Err::InvalidCharacterInLocalPart;
                                }
                            }
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
                            $emailAddress['invalid_reason_code'] = Err::InvalidCharacterInAddress;
                        }
                    }

                    break;
                case self::STATE_SQUARE_BRACKET:
                    // Handle square bracketed IP addresses such as [10.0.10.2]
                    $emailAddress['original_address'] .= $curChar;
                    if (']' == $curChar) {
                        $subState = self::STATE_AFTER_DOMAIN;
                        $state = self::STATE_ADDRESS;
                    } else {
                        $emailAddress['ip'] .= $curChar;
                    }

                    break;
                case self::STATE_OBS_ROUTE:
                    // RFC 5322 §4.4 obs-route absorption — consume the
                    // `@host1,@host2:` source-route prefix inside angle-addr.
                    // On `:` terminator, resume normal addr-spec parsing with
                    // local-part state cleared. An unterminated obs-route
                    // (end of input or `>` before `:`) is an invalid address.
                    $emailAddress['original_address'] .= $curChar;
                    if (':' == $curChar) {
                        $state = self::STATE_ADDRESS;
                        $subState = self::STATE_LOCAL_PART;
                    } elseif ('>' == $curChar) {
                        // `<@host>` without a colon — incomplete obs-route.
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = 'Incomplete obs-route: missing colon before closing angle-bracket';
                        $emailAddress['invalid_reason_code'] = Err::IncompleteAddress;
                        $emailAddress['in_angle_addr'] = false;
                        $state = self::STATE_ADDRESS;
                        $subState = self::STATE_AFTER_DOMAIN;
                    } else {
                        $emailAddress['obs_route'] .= $curChar;
                    }

                    break;
                case self::STATE_QUOTE:
                    // Handle quoted strings
                    $emailAddress['original_address'] .= $curChar;
                    if ('"' == $curChar) {
                        // RFC 5322 §3.2.4 / RFC 5321 §4.1.2: detect escaped quote by counting
                        // consecutive backslashes immediately before this position. An odd count
                        // means the quote is escaped (e.g. \" or \\\"); even count (incl. zero)
                        // means it is the real closing delimiter.
                        $backslashCount = 0;
                        for ($j = $i - 1; $j >= 0; --$j) {
                            if ('\\' == $chars[$j]) {
                                ++$backslashCount;
                            } else {
                                break;
                            }
                        }
                        if ($backslashCount && 1 == $backslashCount % 2) {
                            // Odd number of backslashes = this quote is escaped
                            $emailAddress['quote_temp'] .= $curChar;
                        } else {
                            // Even backslashes (or zero) = this is the real closing quote.
                            // Record that a quote was seen so an *empty* quoted local-part
                            // (`""@domain`) is still recognised as quoted — quote_temp is
                            // empty in that case, so the '@' handler below can't tell. A
                            // display-name quote self-corrects: the real local-part resets
                            // this flag from address_temp_quoted when '@' is reached.
                            $state = self::STATE_ADDRESS;
                            $emailAddress['local_part_quoted'] = true;
                            $emailAddress['after_closing_quote'] = true;
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
                            // End of comment - save it
                            if ($emailAddress['comment_temp']) {
                                $emailAddress['comments'][] = $emailAddress['comment_temp'];
                                $emailAddress['comment_temp'] = '';
                            }
                            $state = self::STATE_ADDRESS;
                        } else {
                            // Nested comment closing parenthesis
                            $emailAddress['comment_temp'] .= $curChar;
                        }
                    } elseif ('(' == $curChar) {
                        ++$commentNestLevel;
                        if ($commentNestLevel > 1) {
                            // Nested comment opening parenthesis
                            $emailAddress['comment_temp'] .= $curChar;
                        }
                    } else {
                        // Regular comment character
                        $emailAddress['comment_temp'] .= $curChar;
                    }

                    break;
                default:
                    // Shouldn't ever get here - what is $state?
                    $emailAddress['original_address'] .= $curChar;
                    $emailAddress['invalid'] = true;
                    $emailAddress['invalid_reason'] = 'Error during parsing';
                    $emailAddress['invalid_reason_code'] = Err::ParseError;
                    $this->log('error', "Email\\Parse->parse - error during parsing - \$state: {$state}\n\$subState: {$subState}\n\$i: {$i}\n\$curChar: {$curChar}");

                    break;
            }

            // if there's a $emailAddress['original_address'] and the state is set to STATE_END_ADDRESS
            if (self::STATE_END_ADDRESS == $state && strlen($emailAddress['original_address']) > 0) {
                $invalid = $this->addAddress(
                    $emailAddresses,
                    $emailAddress,
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

        // End-of-input reached still inside a delimiter (quote, comment, domain
        // literal, or obs-route) — the construct was never closed. Keyed on the
        // parser state rather than quote_temp, since bracket/comment content is
        // buffered elsewhere (a closed delimiter always returns to STATE_ADDRESS).
        if (!$emailAddress['invalid'] && in_array($state, [self::STATE_QUOTE, self::STATE_COMMENT, self::STATE_SQUARE_BRACKET, self::STATE_OBS_ROUTE], true)) {
            $emailAddress['invalid'] = true;
            [$emailAddress['invalid_reason'], $emailAddress['invalid_reason_code']] = match ($state) {
                self::STATE_QUOTE => ['No ending quote: \'"\'', Err::UnterminatedQuote],
                self::STATE_COMMENT => ['No closing parenthesis: \')\'', Err::UnterminatedComment],
                self::STATE_SQUARE_BRACKET => ['No closing square bracket: \']\'', Err::UnterminatedSquareBracket],
                self::STATE_OBS_ROUTE => ['Incomplete obs-route: missing colon before end of input', Err::IncompleteAddress],
            };
        }
        if (!$emailAddress['invalid'] && ($emailAddress['address_temp'] || $emailAddress['quote_temp'])) {
            $this->log('error', "Email\\Parse->parse - corruption during parsing - leftovers:\n\$i: {$i}\n\$emailAddress['address_temp']: {$emailAddress['address_temp']}\n\$emailAddress['quote_temp']: {$emailAddress['quote_temp']}\nEmails: {$emails}");
            $emailAddress['invalid'] = true;
            $emailAddress['invalid_reason'] = 'Incomplete address';
            $emailAddress['invalid_reason_code'] = Err::IncompleteAddress;
            if (!$success) {
                $reason = 'Invalid email addresses';
            } else {
                $reason = 'Invalid email address';
                $success = false;
            }
        }

        // Did we find no email addresses at all? An empty local-part only counts as
        // "no address" when it is unquoted; `""@domain` is a legitimately-empty quoted
        // local-part whose acceptance is decided later by rejectEmptyQuotedLocalPart.
        if (!$emailAddress['invalid'] && !count($emailAddresses) && (!$emailAddress['original_address'] || (!$emailAddress['local_part_parsed'] && !$emailAddress['local_part_quoted']))) {
            $success = false;
            $reason = 'No email addresses found';
            if (!$multiple) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'No email address found';
                $emailAddress['invalid_reason_code'] = Err::IncompleteAddress;
                $this->addAddress(
                    $emailAddresses,
                    $emailAddress,
                    $i
                );
            }
        } elseif ($emailAddress['original_address']) {
            $invalid = $this->addAddress(
                $emailAddresses,
                $emailAddress,
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
     * Resolves a pending quoted or temp buffer into the display name.
     *
     * Called when a display name is followed by an angle-addr (<local@domain>).
     * Periods in an unquoted name are invalid per RFC 5322 §3.4 — the display
     * name must be a phrase, and a period is not an atext character.
     */
    private function handleQuote(array &$emailAddress): void
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
                $emailAddress['invalid_reason'] = 'Periods within the display name of an email address must appear in quotes, such as "John Q. Public" <john@qpublic.com> according to RFC 5322';
                $emailAddress['invalid_reason_code'] = Err::UnquotedPeriodInDisplayName;
            }
        }
    }

    /**
     * Returns a fresh email address accumulator array with all fields zeroed.
     * @return array
     */
    private function buildEmailAddressArray(): array
    {
        return [
            'original_address' => '',
            'name_parsed' => '',
            'local_part_parsed' => '',
            'domain' => '',
            'domain_ascii' => null,
            'ip' => '',
            'invalid' => false,
            'invalid_reason' => null,
            'invalid_reason_code' => null,
            'local_part_quoted' => false,
            'name_quoted' => false,
            'address_temp_quoted' => false,
            // True for exactly the character after a closing quote, so atext / a
            // second quote directly abutting a quoted-string can be rejected.
            'after_closing_quote' => false,
            'quote_temp' => '',
            'address_temp' => '',
            'address_temp_period' => 0,
            'special_char_in_substate' => null,
            'comment_temp' => '',
            'comments' => [],
            // True while the parser is inside angle-addr (between `<` and `>`).
            // Used to gate obs-route detection per RFC 5322 §4.4.
            'in_angle_addr' => false,
            // Accumulates the obs-route prefix (everything between `<` and the
            // terminating `:`) when ParseOptions::$allowObsRoute is true.
            // Empty string when no obs-route was seen.
            'obs_route' => '',
        ];
    }

    /**
     * Validates the accumulated email address parts and appends the result to $emailAddresses.
     *
     * Runs post-parse validation: IP address range checks, domain punycode conversion,
     * domain name format validation (RFC 5321 §4.1.2, RFC 1035 §2.3.4), local-part
     * content validation, FQDN requirement, and length limits (RFC 5321 §4.5.3.1).
     *
     * @return bool True if the address was invalid, false if it was valid
     */
    private function addAddress(
        &$emailAddresses,
        &$emailAddress,
        $i
    ): bool {
        if (!$emailAddress['invalid']) {
            if (isset($emailAddress['domain']) &&
                (filter_var($emailAddress['domain'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ||
                str_starts_with($emailAddress['domain'], 'IPv6:') ||
                preg_match('/^\d+\.\d+\.\d+\.\d+$/', $emailAddress['domain']))) {
                $emailAddress['ip'] = $emailAddress['domain'];
                $emailAddress['domain'] = '';
            }
            if ($emailAddress['address_temp'] || $emailAddress['quote_temp']) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Incomplete address';
                $emailAddress['invalid_reason_code'] = Err::IncompleteAddress;
                $this->log('error', "Email\\Parse->addAddress - corruption during parsing - leftovers:\n\$i: {$i}\n\$emailAddress['address_temp'] : {$emailAddress['address_temp']}\n\$emailAddress['quote_temp']: {$emailAddress['quote_temp']}\n");
            } elseif ($emailAddress['ip'] && $emailAddress['domain']) {
                // Error - this should never occur
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Confusion during parsing';
                $emailAddress['invalid_reason_code'] = Err::ParserConfusion;
                $this->log('error', "Email\\Parse->addAddress - both an IP address '{$emailAddress['ip']}' and a domain '{$emailAddress['domain']}' found for the email address '{$emailAddress['original_address']}'\n");
            } elseif ($emailAddress['ip']) {
                if (filter_var($emailAddress['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                    if ($this->options->validateIpGlobalRange && !$this->validateIpGlobalRange($emailAddress['ip'], FILTER_FLAG_IPV4)) {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = 'IP address invalid: \'' . $emailAddress['ip'] . '\' does not appear to be a valid IP address in the global range';
                        $emailAddress['invalid_reason_code'] = Err::IpNotInGlobalRange;
                    }
                } elseif (str_starts_with($emailAddress['ip'], 'IPv6:')) {
                    $tempIp = str_replace('IPv6:', '', $emailAddress['ip']);
                    if (filter_var($tempIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                        if ($this->options->validateIpGlobalRange && !$this->validateIpGlobalRange($tempIp, FILTER_FLAG_IPV6)) {
                            $emailAddress['invalid'] = true;
                            $emailAddress['invalid_reason'] = 'IP address invalid: \'' . $emailAddress['ip'] . '\' does not appear to be a valid IPv6 address in the global range';
                            $emailAddress['invalid_reason_code'] = Err::Ipv6NotInGlobalRange;
                        }
                    } else {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = 'IP address invalid: \'' . $emailAddress['ip'] . '\' does not appear to be a valid IP address';
                        $emailAddress['invalid_reason_code'] = Err::InvalidIpAddress;
                    }
                } else {
                    $emailAddress['invalid'] = true;
                    $emailAddress['invalid_reason'] = 'IP address invalid: \'' . $emailAddress['ip'] . '\' does not appear to be a valid IP address';
                    $emailAddress['invalid_reason_code'] = Err::InvalidIpAddress;
                }
            } elseif ($emailAddress['domain']) {
                // Strip optional FQDN root-label dot (RFC 5321 §2.3.5 allows "example.com.")
                if (str_ends_with($emailAddress['domain'], '.')) {
                    $emailAddress['domain'] = substr($emailAddress['domain'], 0, -1);
                }

                // NFC-normalize internationalized domain before punycode conversion
                // RFC 6531 §3.3 / RFC 5891 §5.2: U-labels must be in NFC before IDNA processing
                if ($this->options->applyNfcNormalization && $emailAddress['domain'] !== '') {
                    $nfc = $this->normalizeUtf8($emailAddress['domain']);
                    if ($nfc !== false) {
                        $emailAddress['domain'] = $nfc;
                    }
                }

                $domainAscii = $this->normalizeDomainAscii($emailAddress['domain']);
                if ($domainAscii === null) {
                    $emailAddress['invalid'] = true;
                    $emailAddress['invalid_reason'] = "Can't convert domain {$emailAddress['domain']} to punycode";
                    $emailAddress['invalid_reason_code'] = Err::PunycodeConversionFailed;
                } else {
                    if ($domainAscii !== $emailAddress['domain']) {
                        $emailAddress['domain_ascii'] = $domainAscii;
                    }
                    $result = $this->validateDomainName($domainAscii);
                    if (!$result['valid']) {
                        $emailAddress['invalid'] = true;
                        $emailAddress['invalid_reason'] = isset($result['reason']) ? 'Domain invalid: '.$result['reason'] : 'Domain invalid for some unknown reason';
                        $emailAddress['invalid_reason_code'] = $result['code'] ?? Err::DomainInvalid;
                    }
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
            if (0 == strlen($domainPart)) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Email address needs a domain after the \'@\'';
                $emailAddress['invalid_reason_code'] = Err::MissingDomain;
            }
        }

        // RFC 5322 §3.2.5 phrase validation for unquoted display names.
        // A phrase is 1*word where each word is an atom (atext + CFWS) or quoted-string.
        // Quoted display names are already phrase-valid; an unquoted name must contain
        // only atext characters and whitespace. The parser's state machine already
        // catches unquoted periods (UnquotedPeriodInDisplayName); this check adds
        // rejection of non-atext bytes such as stray UTF-8 in an unquoted name.
        if (!$emailAddress['invalid']
            && $this->options->validateDisplayNamePhrase
            && !$emailAddress['name_quoted']
            && $emailAddress['name_parsed'] !== ''
            && !preg_match('#^[A-Za-z0-9!\#$%&\'*+\-/=?^_`{|}~ \t]+$#', $emailAddress['name_parsed'])
        ) {
            $emailAddress['invalid'] = true;
            $emailAddress['invalid_reason'] = "Display name '{$emailAddress['name_parsed']}' must be a quoted-string or atext-only phrase per RFC 5322 §3.2.5";
            $emailAddress['invalid_reason_code'] = Err::InvalidDisplayNamePhrase;
        }

        // Unified local-part validation
        if (!$emailAddress['invalid']) {
            $result = $this->validateLocalPart($emailAddress);
            if (!$result['valid']) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = $result['reason'];
                $emailAddress['invalid_reason_code'] = $result['code'] ?? null;
            } elseif ($result['normalized'] !== null) {
                // Apply NFC normalization result to the parsed local-part and re-derive display form
                $emailAddress['local_part_parsed'] = $result['normalized'];
                $localPart = $emailAddress['local_part_quoted']
                    ? "\"{$emailAddress['local_part_parsed']}\""
                    : $emailAddress['local_part_parsed'];
            }

            // Optional caller-supplied local-part normalizer — invoked after structural
            // validation so the callback only sees addresses that already conform to
            // the configured ParseOptions rules. Typical uses: Gmail dot-insensitivity
            // (`john.doe` → `johndoe`), plus-addressing (`user+tag` → `user`), or any
            // domain-specific canonicalization. The returned string replaces
            // local_part_parsed and the display form is re-derived; `original_address`
            // still preserves the verbatim input.
            if (!$emailAddress['invalid'] && $this->options->localPartNormalizer !== null) {
                $normalizer = $this->options->localPartNormalizer;
                $normalized = $normalizer($emailAddress['local_part_parsed'], $emailAddress['domain']);
                if ($normalized !== $emailAddress['local_part_parsed']) {
                    $emailAddress['local_part_parsed'] = $normalized;
                    $localPart = $emailAddress['local_part_quoted']
                        ? "\"{$emailAddress['local_part_parsed']}\""
                        : $emailAddress['local_part_parsed'];
                }
            }
        }

        // FQDN check
        if (!$emailAddress['invalid'] && $this->options->requireFqdn && $emailAddress['domain']) {
            $dotPos = strpos($emailAddress['domain'], '.');
            if ($dotPos === false || $dotPos === 0 || $dotPos === strlen($emailAddress['domain']) - 1) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = 'Domain must be a fully-qualified domain name';
                $emailAddress['invalid_reason_code'] = Err::FqdnRequired;
            }
        }

        // RFC 5321 §4.5.3.1: all limits are in octets (bytes), not characters.
        // For quoted local-parts the wire form adds 2 DQUOTE bytes to the length.
        if (!$emailAddress['invalid'] && $this->options->enforceLengthLimits) {
            $limits = $this->options->getLengthLimits();
            // RFC 5321 §4.5.3.1.1: local-part max 64 octets (wire form includes DQUOTE for quoted strings)
            $localPartWireLen = $emailAddress['local_part_quoted']
                ? strlen($emailAddress['local_part_parsed']) + 2
                : strlen($emailAddress['local_part_parsed']);

            if ($localPartWireLen > $limits->maxLocalPartLength) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = "Email address before the '@' can not be greater than {$limits->maxLocalPartLength} octets per RFC 5321";
                $emailAddress['invalid_reason_code'] = Err::LocalPartTooLong;
            } elseif (($localPartWireLen + 1 + strlen($domainPart)) > $limits->maxTotalLength) {
                $emailAddress['invalid'] = true;
                $emailAddress['invalid_reason'] = "Email addresses can not be greater than {$limits->maxTotalLength} octets per RFC 3696 EID 1690";
                $emailAddress['invalid_reason_code'] = Err::TotalLengthExceeded;
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
                        'domain_ascii' => $this->options->includeDomainAscii ? ($emailAddress['domain_ascii'] ?? null) : null,
                        'ip' => $emailAddress['ip'],
                        'invalid' => $emailAddress['invalid'],
                        'invalid_reason' => $emailAddress['invalid_reason'],
                        'invalid_reason_code' => $emailAddress['invalid_reason_code'],
                        'comments' => $emailAddress['comments'],
                        'obs_route' => $emailAddress['obs_route'] !== '' ? $emailAddress['obs_route'] : null, ];

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
     * Returns true if the character is a non-ASCII byte (multi-byte UTF-8 code point).
     * The first byte of any multi-byte UTF-8 sequence is always >= 0x80.
     */
    protected function isUtf8Char(string $char): bool
    {
        return ord($char[0]) > 127;
    }

    /**
     * Unified local-part validation based on ParseOptions rule properties.
     *
     * @param array $emailAddress The email address array from the parser
     * @return array{valid: bool, reason: ?string, code: ?ParseErrorCode, normalized: ?string}
     */
    protected function validateLocalPart(array $emailAddress): array
    {
        $opts = $this->options;
        $localPart = $emailAddress['local_part_parsed'];
        $quoted = $emailAddress['local_part_quoted'];

        // RFC 6531 §3.3 / RFC 6532 §3.2: gate UTF-8 presence before other checks
        // (allowUtf8LocalPart is false in rfc5321() and rfc5322() presets)
        $hasUtf8 = (bool) preg_match('/[^\x00-\x7F]/', $localPart);
        if ($hasUtf8 && !$opts->allowUtf8LocalPart) {
            return ['valid' => false, 'reason' => 'UTF-8 characters not allowed in local part', 'code' => Err::Utf8NotAllowedInLocalPart, 'normalized' => null];
        }

        // Quoted-string content validation (RFC 5321 §4.1.2 qtextSMTP, RFC 5322 §3.2.4 qtext)
        if ($quoted) {
            if ($opts->rejectEmptyQuotedLocalPart && $localPart === '') {
                return ['valid' => false, 'reason' => 'Empty quoted local part not allowed', 'code' => Err::EmptyQuotedLocalPart, 'normalized' => null];
            }

            if ($opts->validateQuotedContent) {
                $len = strlen($localPart);
                for ($i = 0; $i < $len; $i++) {
                    $byte = ord($localPart[$i]);

                    if ($localPart[$i] === '\\') {
                        // quoted-pair: must be followed by a valid character
                        if ($i + 1 >= $len) {
                            return ['valid' => false, 'reason' => 'Trailing backslash in quoted string', 'code' => Err::TrailingBackslashInQuotedString, 'normalized' => null];
                        }
                        $nextByte = ord($localPart[$i + 1]);
                        // RFC 5321 §4.1.2 quoted-pairSMTP: backslash followed by %d32-126
                        if ($nextByte < 32 || $nextByte > 126) {
                            return ['valid' => false, 'reason' => 'Invalid escaped character in quoted string', 'code' => Err::InvalidEscapedCharInQuotedString, 'normalized' => null];
                        }
                        // Skip both the backslash and its escape target; they form one
                        // quoted-pair and must not be re-checked against qtextSMTP below
                        // (which would otherwise reject the backslash as byte 92).
                        ++$i;

                        continue;
                    }

                    // UTF-8 multibyte in quoted string (internationalized)
                    if ($opts->allowUtf8LocalPart && $byte > 127) {
                        continue;
                    }

                    // qtextSMTP: %d32-33 / %d35-91 / %d93-126
                    // Reject: NUL, C0 controls, DQUOTE(%d34), backslash(%d92), DEL(%d127+)
                    if ($byte <= 31 || $byte == 34 || $byte == 92 || $byte >= 127) {
                        return ['valid' => false, 'reason' => 'Invalid character in quoted string: byte ' . $byte, 'code' => Err::InvalidCharInQuotedString, 'normalized' => null];
                    }
                }

                // C1 control check for internationalized quoted content
                if ($opts->rejectC1Controls && preg_match('/[\x{0080}-\x{009F}]/u', $localPart)) {
                    return ['valid' => false, 'reason' => 'C1 control character in quoted string', 'code' => Err::C1ControlInQuotedString, 'normalized' => null];
                }
            }

            return ['valid' => true, 'reason' => null, 'code' => null, 'normalized' => null];
        }

        // Unquoted local part validation

        // RFC 5321 §4.1.2: atext and qtextSMTP both exclude C0 control characters.
        // RFC 6530 §10.1: C1 control characters (U+0080-U+009F) are also prohibited
        // in internationalized email addresses (they are valid UTF-8 but meaningless).
        if ($opts->rejectC0Controls && preg_match('/[\x00-\x1F]/', $localPart)) {
            return ['valid' => false, 'reason' => 'C0 control character in local part', 'code' => Err::C0ControlInLocalPart, 'normalized' => null];
        }
        if ($opts->rejectC1Controls && preg_match('/[\x{0080}-\x{009F}]/u', $localPart)) {
            return ['valid' => false, 'reason' => 'C1 control character in local part', 'code' => Err::C1ControlInLocalPart, 'normalized' => null];
        }

        // NFC normalization: apply and return normalized form for caller to store
        $normalizedLocalPart = null;
        if ($opts->applyNfcNormalization) {
            $nfc = $this->normalizeUtf8($localPart);
            if ($nfc === false) {
                return ['valid' => false, 'reason' => 'Local part cannot be NFC normalized', 'code' => Err::LocalPartCannotBeNormalized, 'normalized' => null];
            }
            if ($nfc !== $localPart) {
                $normalizedLocalPart = $nfc;
                $localPart = $nfc;
            }
        }

        // UTF-8 encoding validation
        if ($hasUtf8 && !mb_check_encoding($localPart, 'UTF-8')) {
            return ['valid' => false, 'reason' => 'Invalid UTF-8 encoding in local part', 'code' => Err::InvalidUtf8Encoding, 'normalized' => null];
        }

        // Build the validation pattern for unquoted local-parts.
        // atext (RFC 5322 §3.2.3): A-Z a-z 0-9 ! # $ % & ' * + - / = ? ^ _ ` { | } ~
        // RFC 6531 §3.3 extends atext with Unicode letters and digits (\p{L}\p{N}).
        if ($opts->allowUtf8LocalPart) {
            $dotAtomPattern = "/^[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~\\p{L}\\p{N}]+(?:\\.[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~\\p{L}\\p{N}]+)*$/u";
        } else {
            $dotAtomPattern = "/^[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~]+(?:\\.[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~]+)*$/";
        }

        if ($opts->allowObsLocalPart) {
            // obs-local-part (RFC 5322 §4.4): dots permitted anywhere — leading, trailing, consecutive
            $pattern = $opts->allowUtf8LocalPart
                ? "/^[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~.\\p{L}\\p{N}]+$/u"
                : "/^[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~.]+$/";
        } elseif ($opts->rejectC0Controls) {
            // dot-atom-text (RFC 5322 §3.2.3): 1*atext *("." 1*atext) — no leading, trailing, or consecutive dots
            $pattern = $dotAtomPattern;
        } else {
            // Legacy/non-strict: the state machine already rejects leading/consecutive dots;
            // trailing dots are permitted here for backward compatibility with v2.x.
            if ($opts->allowUtf8LocalPart) {
                $pattern = "/^[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~\\p{L}\\p{N}]+(?:\\.[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~\\p{L}\\p{N}]+)*\\.?$/u";
            } else {
                $pattern = "/^[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~]+(?:\\.[A-Za-z0-9!#$%&'*+\\-\\/=?^_`{|}~]+)*\\.?$/";
            }
        }

        if (!preg_match($pattern, $localPart)) {
            return ['valid' => false, 'reason' => 'Local part contains invalid characters', 'code' => Err::LocalPartContainsInvalidChars, 'normalized' => null];
        }

        return ['valid' => true, 'reason' => null, 'code' => null, 'normalized' => $normalizedLocalPart];
    }

    /**
     * Normalize a UTF-8 string using NFC normalization form.
     * RFC 6532 §3.1 recommends NFC normalization for internationalized email addresses.
     *
     * @param string $str The string to normalize
     * @return string|false The normalized string, or false on failure
     */
    protected function normalizeUtf8(string $str): string|false
    {
        if (!function_exists('normalizer_normalize')) {
            // Intl extension not available, return as-is
            return $str;
        }

        $normalized = \Normalizer::normalize($str, \Normalizer::NFC);

        return $normalized === false ? false : $normalized;
    }

    /**
     * Convert domain to ASCII (punycode/A-label) form via IDNA UTS#46 (RFC 5891/5892).
     *
     * Returns the domain unchanged if it is already pure ASCII. Returns null if
     * conversion fails (caller should reject the address).
     */
    protected function normalizeDomainAscii(string $domain): ?string
    {
        if ($domain === '' || !preg_match('/[^\x00-\x7F]/', $domain)) {
            return $domain;
        }

        // When `strictIdna` is enabled, apply full IDNA2008 conformance:
        //   - USE_STD3_RULES: reject labels containing characters outside LDH (RFC 5891 §4.4).
        //   - CHECK_BIDI: enforce the Bidi rule for labels with RTL characters (RFC 5893).
        //   - CHECK_CONTEXTJ: enforce CONTEXTJ rules for U+200C / U+200D (RFC 5892 Appendix A).
        //   - NONTRANSITIONAL_TO_ASCII: treat IDNA2008 deviations (ß, ς, etc.) literally
        //     instead of the IDNA2003 mapping — required for full RFC 5891 compliance.
        // Without strictIdna we retain the permissive UTS#46 default for backward compatibility.
        $flags = $this->options->strictIdna
            ? IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ | IDNA_NONTRANSITIONAL_TO_ASCII
            : IDNA_DEFAULT;

        $idnaInfo = [];
        $ascii = idn_to_ascii($domain, $flags, INTL_IDNA_VARIANT_UTS46, $idnaInfo);

        if ($ascii === false) {
            return null;
        }

        // Under strictIdna, idn_to_ascii() may still return a string while reporting
        // errors in $idnaInfo['errors']. Treat any reported error as a conversion failure.
        if ($this->options->strictIdna && ($idnaInfo['errors'] ?? 0) !== 0) {
            return null;
        }

        return $ascii;
    }

    /**
     * Validates the ASCII (punycode) form of a domain name.
     *
     * Enforces RFC 5321 §4.1.2 + RFC 1035 §2.3.4 domain label rules:
     *   - Max 255 octets total (RFC 5321 §4.5.3.1.2)
     *   - Each label at most maxDomainLabelLength octets (RFC 1035 §2.3.4: 63)
     *   - Labels contain only [A-Za-z0-9-] (letters, digits, hyphen)
     *   - Labels may not start or end with a hyphen (RFC 1035 §2.3.4)
     *   - RFC 1123 §2.1 relaxed the original restriction that allowed labels starting
     *     with a letter only, permitting labels that start with a digit.
     *
     * @param string $domain The ASCII domain name to validate (after punycode conversion)
     *
     * @return array{valid: bool, reason?: string, code?: ParseErrorCode}
     */
    protected function validateDomainName(string $domain): array
    {
        // RFC 5321 §4.5.3.1.2: total domain length limit is in octets
        if (strlen($domain) > 255) {
            return ['valid' => false, 'reason' => 'Domain name too long', 'code' => Err::DomainTooLong];
        } else {
            // $domain is always ASCII here (post-punycode, via normalizeDomainAscii),
            // so a plain explode on the label separator is sufficient. This avoids
            // mb_regex_encoding(), deprecated since PHP 8.6 (the underlying oniguruma
            // library is no longer maintained). See GitHub issue #57.
            // Labels are guaranteed non-empty: the state machine rejects consecutive
            // and edge dots (ConsecutiveDots) before the domain validator runs.
            $parts = explode('.', $domain);
            $maxLabelLen = $this->options->getLengthLimits()->maxDomainLabelLength;
            foreach ($parts as $part) {
                if (strlen($part) > $maxLabelLen) {
                    return ['valid' => false, 'reason' => "Domain name part '{$part}' must be less than {$maxLabelLen} octets", 'code' => Err::DomainLabelTooLong];
                }
                if (!preg_match('/^[a-zA-Z0-9\-]+$/', $part)) {
                    return ['valid' => false, 'reason' => "Domain name '{$domain}' can only contain letters a through z, numbers 0 through 9 and hyphen.  The part '{$part}' contains characters outside of that range.", 'code' => Err::DomainContainsInvalidChars];
                }
                if ('-' == substr($part, 0, 1) || '-' == substr($part, -1)) {
                    return ['valid' => false, 'reason' => "Parts of the domain name '{$domain}' can not start or end with '-'.  This part does: {$part}", 'code' => Err::DomainLabelStartsOrEndsWithHyphen];
                }
            }
        }

        return ['valid' => true];
    }
}
