<?php

namespace Email\PHP71; // Changed for MRBS

/**
 * Email address length limits configuration.
 *
 * Immutable value object containing the maximum length constraints for email
 * addresses as defined by RFC 5321, RFC 1035, and RFC 3696 EID 1690.
 */
class LengthLimits
{
    /**
     * @var int
     * @readonly
     */
    public $maxLocalPartLength;

    /**
     * @var int
     * @readonly
     */
    public $maxTotalLength;

    /**
     * @var int
     * @readonly
     */
    public $maxDomainLabelLength;

    /**
     * @param int $maxLocalPartLength Maximum length for local part (before @) in octets. Default: 64 per RFC 5321
     * @param int $maxTotalLength Maximum total email length in octets. Default: 254 per RFC 3696 EID 1690
     * @param int $maxDomainLabelLength Maximum length for domain labels in octets. Default: 63 per RFC 1035
     */
    public function __construct(int $maxLocalPartLength = 64, int $maxTotalLength = 254, int $maxDomainLabelLength = 63)
    {
        $this->maxLocalPartLength = $maxLocalPartLength;
        $this->maxTotalLength = $maxTotalLength;
        $this->maxDomainLabelLength = $maxDomainLabelLength;
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
