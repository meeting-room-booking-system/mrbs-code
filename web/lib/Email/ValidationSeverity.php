<?php

namespace Email;

/**
 * Severity levels attached to parse failures.
 *
 * Each {@see ParseErrorCode} maps to exactly one severity via
 * {@see ParseErrorCode::severity()}. Callers can use the severity to decide
 * whether a given failure is acceptable for their use case: for example, a
 * mail-storage system may choose to accept `Warning` addresses (syntactically
 * valid but policy-violating) while rejecting `Critical` ones (unparseable).
 *
 * The backing string values are stable public API.
 */
enum ValidationSeverity: string
{
    /**
     * The address cannot be parsed or structurally violates RFC 5322 / 5321
     * (missing '@', unterminated delimiters, invalid characters, bad IP
     * syntax, etc.). A Critical failure means the input is not a valid
     * address in any interpretation.
     */
    case Critical = 'critical';

    /**
     * The address is syntactically well-formed but violates a configured
     * validation rule — for example, UTF-8 rejected in an ASCII-only preset,
     * a non-FQDN domain when `requireFqdn` is set, a private-range IP
     * literal when `validateIpGlobalRange` is set, or an octet-length limit
     * from RFC 5321 §4.5.3.1. Callers may choose to accept Warning
     * failures depending on context.
     */
    case Warning = 'warning';

    /**
     * Informational only — currently unused; reserved for future
     * deprecation hints and non-blocking advisory messages.
     */
    case Info = 'info';
}
