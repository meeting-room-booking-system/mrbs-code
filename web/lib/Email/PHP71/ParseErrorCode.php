<?php

namespace Email\PHP71; // Changed for MRBS

/**
 * Structured error codes for parse failures (PHP 7.1 emulation of the 3.x backed
 * enum — see {@see EnumEmulation}). Each case is a static accessor,
 * e.g. ParseErrorCode::LocalPartTooLong(), returning a shared singleton; the
 * backing string values are stable public API.
 */
final class ParseErrorCode implements \JsonSerializable
{
    use EnumEmulation;

    public static function SeparatorNotPermitted(): self
    {
        return self::instance('SeparatorNotPermitted', 'separator_not_permitted');
    }

    public static function MisplacedSeparator(): self
    {
        return self::instance('MisplacedSeparator', 'misplaced_separator');
    }

    public static function MultipleOpeningAngle(): self
    {
        return self::instance('MultipleOpeningAngle', 'multiple_opening_angle');
    }

    public static function MissingDomainBeforeClosingAngle(): self
    {
        return self::instance('MissingDomainBeforeClosingAngle', 'missing_domain_before_closing_angle');
    }

    public static function MisplacedQuote(): self
    {
        return self::instance('MisplacedQuote', 'misplaced_quote');
    }

    public static function MultipleAtSymbols(): self
    {
        return self::instance('MultipleAtSymbols', 'multiple_at_symbols');
    }

    public static function StrayAtAfterDomain(): self
    {
        return self::instance('StrayAtAfterDomain', 'stray_at_after_domain');
    }

    public static function UnterminatedQuote(): self
    {
        return self::instance('UnterminatedQuote', 'unterminated_quote');
    }

    public static function UnterminatedComment(): self
    {
        return self::instance('UnterminatedComment', 'unterminated_comment');
    }

    public static function ControlCharInComment(): self
    {
        return self::instance('ControlCharInComment', 'control_char_in_comment');
    }

    public static function UnterminatedSquareBracket(): self
    {
        return self::instance('UnterminatedSquareBracket', 'unterminated_square_bracket');
    }

    public static function IncompleteAddress(): self
    {
        return self::instance('IncompleteAddress', 'incomplete_address');
    }

    public static function ParseError(): self
    {
        return self::instance('ParseError', 'parse_error');
    }

    public static function ParserConfusion(): self
    {
        return self::instance('ParserConfusion', 'parser_confusion');
    }

    public static function WhitespaceInAddress(): self
    {
        return self::instance('WhitespaceInAddress', 'whitespace_in_address');
    }

    public static function InvalidCharacterInAddress(): self
    {
        return self::instance('InvalidCharacterInAddress', 'invalid_character_in_address');
    }

    public static function InvalidCharacterAtStart(): self
    {
        return self::instance('InvalidCharacterAtStart', 'invalid_character_at_start');
    }

    public static function InvalidCharacterInLocalPart(): self
    {
        return self::instance('InvalidCharacterInLocalPart', 'invalid_character_in_local_part');
    }

    public static function InvalidCharacterInDomain(): self
    {
        return self::instance('InvalidCharacterInDomain', 'invalid_character_in_domain');
    }

    public static function InvalidOpeningBracket(): self
    {
        return self::instance('InvalidOpeningBracket', 'invalid_opening_bracket');
    }

    public static function CharacterNotAllowed(): self
    {
        return self::instance('CharacterNotAllowed', 'character_not_allowed');
    }

    public static function ConsecutiveDots(): self
    {
        return self::instance('ConsecutiveDots', 'consecutive_dots');
    }

    public static function LeadingDot(): self
    {
        return self::instance('LeadingDot', 'leading_dot');
    }

    public static function StrayPeriodAfterDomain(): self
    {
        return self::instance('StrayPeriodAfterDomain', 'stray_period_after_domain');
    }

    public static function StrayPeriod(): self
    {
        return self::instance('StrayPeriod', 'stray_period');
    }

    public static function UnquotedPeriodInDisplayName(): self
    {
        return self::instance('UnquotedPeriodInDisplayName', 'unquoted_period_in_display_name');
    }

    public static function Utf8NotAllowedInLocalPart(): self
    {
        return self::instance('Utf8NotAllowedInLocalPart', 'utf8_not_allowed_in_local_part');
    }

    public static function C0ControlInLocalPart(): self
    {
        return self::instance('C0ControlInLocalPart', 'c0_control_in_local_part');
    }

    public static function C1ControlInLocalPart(): self
    {
        return self::instance('C1ControlInLocalPart', 'c1_control_in_local_part');
    }

    public static function InvalidUtf8Encoding(): self
    {
        return self::instance('InvalidUtf8Encoding', 'invalid_utf8_encoding');
    }

    public static function LocalPartCannotBeNormalized(): self
    {
        return self::instance('LocalPartCannotBeNormalized', 'local_part_cannot_be_normalized');
    }

    public static function LocalPartContainsInvalidChars(): self
    {
        return self::instance('LocalPartContainsInvalidChars', 'local_part_contains_invalid_chars');
    }

    public static function LocalPartTooLong(): self
    {
        return self::instance('LocalPartTooLong', 'local_part_too_long');
    }

    public static function EmptyQuotedLocalPart(): self
    {
        return self::instance('EmptyQuotedLocalPart', 'empty_quoted_local_part');
    }

    public static function TrailingBackslashInQuotedString(): self
    {
        return self::instance('TrailingBackslashInQuotedString', 'trailing_backslash_in_quoted_string');
    }

    public static function InvalidEscapedCharInQuotedString(): self
    {
        return self::instance('InvalidEscapedCharInQuotedString', 'invalid_escaped_char_in_quoted_string');
    }

    public static function InvalidCharInQuotedString(): self
    {
        return self::instance('InvalidCharInQuotedString', 'invalid_char_in_quoted_string');
    }

    public static function C1ControlInQuotedString(): self
    {
        return self::instance('C1ControlInQuotedString', 'c1_control_in_quoted_string');
    }

    public static function AtextAfterQuotedString(): self
    {
        return self::instance('AtextAfterQuotedString', 'atext_after_quoted_string');
    }

    public static function AtextAfterComment(): self
    {
        return self::instance('AtextAfterComment', 'atext_after_comment');
    }

    public static function MissingDomain(): self
    {
        return self::instance('MissingDomain', 'missing_domain');
    }

    public static function DomainTooLong(): self
    {
        return self::instance('DomainTooLong', 'domain_too_long');
    }

    public static function DomainLabelTooLong(): self
    {
        return self::instance('DomainLabelTooLong', 'domain_label_too_long');
    }

    public static function DomainContainsInvalidChars(): self
    {
        return self::instance('DomainContainsInvalidChars', 'domain_contains_invalid_chars');
    }

    public static function DomainLabelStartsOrEndsWithHyphen(): self
    {
        return self::instance('DomainLabelStartsOrEndsWithHyphen', 'domain_label_starts_or_ends_with_hyphen');
    }

    public static function PunycodeConversionFailed(): self
    {
        return self::instance('PunycodeConversionFailed', 'punycode_conversion_failed');
    }

    public static function DomainInvalid(): self
    {
        return self::instance('DomainInvalid', 'domain_invalid');
    }

    public static function TrailingDotNotAllowed(): self
    {
        return self::instance('TrailingDotNotAllowed', 'trailing_dot_not_allowed');
    }

    public static function FqdnRequired(): self
    {
        return self::instance('FqdnRequired', 'fqdn_required');
    }

    public static function IpNotInGlobalRange(): self
    {
        return self::instance('IpNotInGlobalRange', 'ip_not_in_global_range');
    }

    public static function Ipv6NotInGlobalRange(): self
    {
        return self::instance('Ipv6NotInGlobalRange', 'ipv6_not_in_global_range');
    }

    public static function InvalidIpAddress(): self
    {
        return self::instance('InvalidIpAddress', 'invalid_ip_address');
    }

    public static function TotalLengthExceeded(): self
    {
        return self::instance('TotalLengthExceeded', 'total_length_exceeded');
    }

    public static function InvalidDisplayNamePhrase(): self
    {
        return self::instance('InvalidDisplayNamePhrase', 'invalid_display_name_phrase');
    }

    /**
     * All cases, in declaration order (native enum cases()).
     *
     * @return array<int, self>
     */
    public static function cases(): array
    {
        return [
            self::SeparatorNotPermitted(),
            self::MisplacedSeparator(),
            self::MultipleOpeningAngle(),
            self::MissingDomainBeforeClosingAngle(),
            self::MisplacedQuote(),
            self::MultipleAtSymbols(),
            self::StrayAtAfterDomain(),
            self::UnterminatedQuote(),
            self::UnterminatedComment(),
            self::ControlCharInComment(),
            self::UnterminatedSquareBracket(),
            self::IncompleteAddress(),
            self::ParseError(),
            self::ParserConfusion(),
            self::WhitespaceInAddress(),
            self::InvalidCharacterInAddress(),
            self::InvalidCharacterAtStart(),
            self::InvalidCharacterInLocalPart(),
            self::InvalidCharacterInDomain(),
            self::InvalidOpeningBracket(),
            self::CharacterNotAllowed(),
            self::ConsecutiveDots(),
            self::LeadingDot(),
            self::StrayPeriodAfterDomain(),
            self::StrayPeriod(),
            self::UnquotedPeriodInDisplayName(),
            self::Utf8NotAllowedInLocalPart(),
            self::C0ControlInLocalPart(),
            self::C1ControlInLocalPart(),
            self::InvalidUtf8Encoding(),
            self::LocalPartCannotBeNormalized(),
            self::LocalPartContainsInvalidChars(),
            self::LocalPartTooLong(),
            self::EmptyQuotedLocalPart(),
            self::TrailingBackslashInQuotedString(),
            self::InvalidEscapedCharInQuotedString(),
            self::InvalidCharInQuotedString(),
            self::C1ControlInQuotedString(),
            self::AtextAfterQuotedString(),
            self::AtextAfterComment(),
            self::MissingDomain(),
            self::DomainTooLong(),
            self::DomainLabelTooLong(),
            self::DomainContainsInvalidChars(),
            self::DomainLabelStartsOrEndsWithHyphen(),
            self::PunycodeConversionFailed(),
            self::DomainInvalid(),
            self::TrailingDotNotAllowed(),
            self::FqdnRequired(),
            self::IpNotInGlobalRange(),
            self::Ipv6NotInGlobalRange(),
            self::InvalidIpAddress(),
            self::TotalLengthExceeded(),
            self::InvalidDisplayNamePhrase(),
        ];
    }

    /**
     * Classify this error by severity. Warning = well-formed but policy-rejected;
     * Critical = structurally invalid.
     */
    public function severity(): ValidationSeverity
    {
        switch ($this->value) {
            case 'utf8_not_allowed_in_local_part':
            case 'c0_control_in_local_part':
            case 'c1_control_in_local_part':
            case 'c1_control_in_quoted_string':
            case 'empty_quoted_local_part':
            case 'fqdn_required':
            case 'ip_not_in_global_range':
            case 'ipv6_not_in_global_range':
            case 'local_part_too_long':
            case 'total_length_exceeded':
            case 'domain_too_long':
            case 'domain_label_too_long':
            case 'punycode_conversion_failed':
                return ValidationSeverity::Warning();
            default:
                return ValidationSeverity::Critical();
        }
    }
}
