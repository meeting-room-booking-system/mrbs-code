<?php

namespace Email\PHP71; // Changed for MRBS

/**
 * Severity levels attached to parse failures (PHP 7.1 emulation of the 3.x
 * backed enum — see {@see EnumEmulation}). Each {@see ParseErrorCode} maps to
 * exactly one severity via {@see ParseErrorCode::severity()}. The backing
 * string values are stable public API.
 */
final class ValidationSeverity implements \JsonSerializable
{
    use EnumEmulation;

    /** The address cannot be parsed or structurally violates RFC 5322 / 5321. */
    public static function Critical(): self
    {
        return self::instance('Critical', 'critical');
    }

    /** The address is well-formed but violates a configured validation rule. */
    public static function Warning(): self
    {
        return self::instance('Warning', 'warning');
    }

    /** Informational only — reserved for future non-blocking advisories. */
    public static function Info(): self
    {
        return self::instance('Info', 'info');
    }

    /**
     * All cases, in declaration order (native enum cases()).
     *
     * @return array<int, self>
     */
    public static function cases(): array
    {
        return [self::Critical(), self::Warning(), self::Info()];
    }
}
