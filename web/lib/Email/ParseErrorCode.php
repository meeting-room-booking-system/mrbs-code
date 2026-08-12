<?php

namespace Email;

/**
 * Structured error codes for invalid parsed email addresses.
 *
 * Each case corresponds to a distinct failure mode encountered during
 * parsing or validation. The human-readable message remains available
 * via the `invalid_reason` string field on parsed-address output; the
 * code enables programmatic handling without string matching.
 *
 * The backing string values are stable — treat them as part of the
 * public API once shipped.
 */
enum ParseErrorCode: string
{
    // --- Input and structural errors ---

    /** Separator character appeared where a separator is not permitted in the current state. */
    case SeparatorNotPermitted = 'separator_not_permitted';

    /** Separator character misplaced, or '@' symbol missing from the address. */
    case MisplacedSeparator = 'misplaced_separator';

    /** More than one unescaped '<' found in a single address. */
    case MultipleOpeningAngle = 'multiple_opening_angle';

    /** Closing '>' encountered without a preceding domain. */
    case MissingDomainBeforeClosingAngle = 'missing_domain_before_closing_angle';

    /** Unescaped '"' appeared in a position where a quote is not allowed. */
    case MisplacedQuote = 'misplaced_quote';

    /** More than one '@' symbol in the address. */
    case MultipleAtSymbols = 'multiple_at_symbols';

    /** Extra '@' symbol found after the domain. */
    case StrayAtAfterDomain = 'stray_at_after_domain';

    /** End of input reached without a closing '"' for a quoted string. */
    case UnterminatedQuote = 'unterminated_quote';

    /** End of input reached without a closing ')' for a comment. */
    case UnterminatedComment = 'unterminated_comment';

    /** End of input reached without a closing ']' for a domain-literal. */
    case UnterminatedSquareBracket = 'unterminated_square_bracket';

    /** Parser accumulated partial state with no complete address to commit. */
    case IncompleteAddress = 'incomplete_address';

    /** Unrecoverable internal parser state (should not occur in practice). */
    case ParseError = 'parse_error';

    /** Simultaneous `address_temp` and `quote_temp` when '@' was reached. */
    case ParserConfusion = 'parser_confusion';

    // --- Character-class errors ---

    /** Whitespace inside an address outside of permitted positions. */
    case WhitespaceInAddress = 'whitespace_in_address';

    /** Character invalid in any position within an email address. */
    case InvalidCharacterInAddress = 'invalid_character_in_address';

    /** Character invalid at the beginning of an email address. */
    case InvalidCharacterAtStart = 'invalid_character_at_start';

    /** Character invalid inside the local-part (before '@'). */
    case InvalidCharacterInLocalPart = 'invalid_character_in_local_part';

    /** Character invalid inside the domain (after '@'). */
    case InvalidCharacterInDomain = 'invalid_character_in_domain';

    /** Unexpected '[' outside a domain-literal position. */
    case InvalidOpeningBracket = 'invalid_opening_bracket';

    /** Character present in the ParseOptions::$bannedChars list. */
    case CharacterNotAllowed = 'character_not_allowed';

    // --- Dot placement errors ---

    /** Two or more consecutive dots in the local-part (RFC 5322 §3.2.3). */
    case ConsecutiveDots = 'consecutive_dots';

    /** Dot at the start of the local-part (RFC 5322 §3.2.3). */
    case LeadingDot = 'leading_dot';

    /** Dot after the domain portion. */
    case StrayPeriodAfterDomain = 'stray_period_after_domain';

    /** Dot in an unexpected position (e.g. inside unquoted display name). */
    case StrayPeriod = 'stray_period';

    /** Dot in an unquoted display name (RFC 5322 §3.4). */
    case UnquotedPeriodInDisplayName = 'unquoted_period_in_display_name';

    // --- Local-part content errors ---

    /** UTF-8 bytes in local-part when `allowUtf8LocalPart = false`. */
    case Utf8NotAllowedInLocalPart = 'utf8_not_allowed_in_local_part';

    /** C0 control character (U+0000-U+001F) in local-part (RFC 5321 §4.1.2). */
    case C0ControlInLocalPart = 'c0_control_in_local_part';

    /** C1 control character (U+0080-U+009F) in local-part (RFC 6530 §10.1). */
    case C1ControlInLocalPart = 'c1_control_in_local_part';

    /** Local-part bytes are not valid UTF-8 (after NFC normalization). */
    case InvalidUtf8Encoding = 'invalid_utf8_encoding';

    /** Local-part could not be NFC-normalized (RFC 6532 §3.1). */
    case LocalPartCannotBeNormalized = 'local_part_cannot_be_normalized';

    /** Local-part fails the atext / dot-atom-text / obs-local-part pattern. */
    case LocalPartContainsInvalidChars = 'local_part_contains_invalid_chars';

    /** Local-part exceeds the configured octet limit (RFC 5321 §4.5.3.1.1). */
    case LocalPartTooLong = 'local_part_too_long';

    // --- Quoted-string errors ---

    /** Empty quoted local-part `""@domain` when rejected (RFC 5321 EID 5414). */
    case EmptyQuotedLocalPart = 'empty_quoted_local_part';

    /** Backslash at the end of a quoted-string with no character to escape. */
    case TrailingBackslashInQuotedString = 'trailing_backslash_in_quoted_string';

    /** Backslash-escaped character outside %d32-126 (RFC 5321 §4.1.2 quoted-pairSMTP). */
    case InvalidEscapedCharInQuotedString = 'invalid_escaped_char_in_quoted_string';

    /** Character inside quoted-string violates qtextSMTP (RFC 5321 §4.1.2). */
    case InvalidCharInQuotedString = 'invalid_char_in_quoted_string';

    /** C1 control character inside a quoted-string (RFC 6530 §10.1). */
    case C1ControlInQuotedString = 'c1_control_in_quoted_string';

    /** atext or a second quoted-string immediately follows a quoted-string with no
     *  separating dot — a quoted-string is a whole word (RFC 5322 §3.2.4). */
    case AtextAfterQuotedString = 'atext_after_quoted_string';

    // --- Domain errors ---

    /** Empty domain after '@'. */
    case MissingDomain = 'missing_domain';

    /** Domain exceeds 255 octets (RFC 5321 §4.5.3.1.2). */
    case DomainTooLong = 'domain_too_long';

    /** Domain label exceeds configured octet limit (RFC 1035 §2.3.4). */
    case DomainLabelTooLong = 'domain_label_too_long';

    /** Domain label contains characters outside [A-Za-z0-9-] (RFC 1035 §2.3.4). */
    case DomainContainsInvalidChars = 'domain_contains_invalid_chars';

    /** Domain label starts or ends with a hyphen (RFC 1035 §2.3.4). */
    case DomainLabelStartsOrEndsWithHyphen = 'domain_label_starts_or_ends_with_hyphen';

    /** IDNA punycode conversion failed via idn_to_ascii(). */
    case PunycodeConversionFailed = 'punycode_conversion_failed';

    /** Domain invalid for an unknown reason (fallback). */
    case DomainInvalid = 'domain_invalid';

    /** Fully-qualified domain name required (RFC 5321 §2.3.5) but only one label found. */
    case FqdnRequired = 'fqdn_required';

    // --- IP-literal errors ---

    /** IPv4 address-literal not in global range (rejects loopback, private, RFC 5736/5737). */
    case IpNotInGlobalRange = 'ip_not_in_global_range';

    /** IPv6 address-literal not in global range. */
    case Ipv6NotInGlobalRange = 'ipv6_not_in_global_range';

    /** String between square brackets is not a valid IPv4 or IPv6 address. */
    case InvalidIpAddress = 'invalid_ip_address';

    // --- Length errors ---

    /** Total wire length exceeds configured octet limit (RFC 3696 EID 1690). */
    case TotalLengthExceeded = 'total_length_exceeded';

    // --- Display-name errors ---

    /** Unquoted display name contains characters outside atext + WSP (RFC 5322 §3.2.5 phrase). */
    case InvalidDisplayNamePhrase = 'invalid_display_name_phrase';

    /**
     * Classify this error by severity.
     *
     * Critical: the input is structurally unparseable or violates a fundamental
     * RFC 5322 / 5321 syntax rule — the address is not valid in any interpretation.
     *
     * Warning: the address is well-formed but was rejected by a configured
     * validation rule (UTF-8 gating, FQDN requirement, IP range check, length
     * limits, C0/C1 control policy, empty-quoted rejection, punycode conversion).
     * Callers may choose to accept Warning-level failures depending on context.
     */
    public function severity(): ValidationSeverity
    {
        return match ($this) {
            self::Utf8NotAllowedInLocalPart,
            self::C0ControlInLocalPart,
            self::C1ControlInLocalPart,
            self::C1ControlInQuotedString,
            self::EmptyQuotedLocalPart,
            self::FqdnRequired,
            self::IpNotInGlobalRange,
            self::Ipv6NotInGlobalRange,
            self::LocalPartTooLong,
            self::TotalLengthExceeded,
            self::DomainTooLong,
            self::DomainLabelTooLong,
            self::PunycodeConversionFailed => ValidationSeverity::Warning,
            default => ValidationSeverity::Critical,
        };
    }
}
