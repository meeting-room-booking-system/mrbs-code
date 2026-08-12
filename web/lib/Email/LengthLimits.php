<?php

namespace Email;

/**
 * Email address length limits configuration.
 *
 * Immutable value object containing the maximum length constraints for email
 * addresses as defined by RFC 5321, RFC 1035, and RFC 3696 EID 1690.
 */
class LengthLimits
{
    /**
     * @param int $maxLocalPartLength Maximum length for local part (before @) in octets. Default: 64 per RFC 5321
     * @param int $maxTotalLength Maximum total email length in octets. Default: 254 per RFC 3696 EID 1690
     * @param int $maxDomainLabelLength Maximum length for domain labels in octets. Default: 63 per RFC 1035
     */
    public function __construct(
        public readonly int $maxLocalPartLength = 64,
        public readonly int $maxTotalLength = 254,
        public readonly int $maxDomainLabelLength = 63,
    ) {
    }

    /**
     * Create LengthLimits with RFC-compliant defaults.
     */
    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * Create LengthLimits with relaxed constraints for legacy systems.
     */
    public static function createRelaxed(): self
    {
        return new self(128, 512, 128);
    }
}
