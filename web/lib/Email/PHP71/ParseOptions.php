<?php

namespace Email;

class ParseOptions
{
    /**
     * @var bool
     * @readonly
     */
    public $allowUtf8LocalPart;

    /**
     * @var bool
     * @readonly
     */
    public $allowObsLocalPart;

    /**
     * @var bool
     * @readonly
     */
    public $allowQuotedString;

    /**
     * @var bool
     * @readonly
     */
    public $validateQuotedContent;

    /**
     * @var bool
     * @readonly
     */
    public $rejectEmptyQuotedLocalPart;

    /**
     * @var bool
     * @readonly
     */
    public $allowUtf8Domain;

    /**
     * @var bool
     * @readonly
     */
    public $allowDomainLiteral;

    /**
     * @var bool
     * @readonly
     */
    public $requireFqdn;

    /**
     * @var bool
     * @readonly
     */
    public $validateIpGlobalRange;

    /**
     * @var bool
     * @readonly
     */
    public $rejectC0Controls;

    /**
     * @var bool
     * @readonly
     */
    public $rejectC1Controls;

    /**
     * @var bool
     * @readonly
     */
    public $applyNfcNormalization;

    /**
     * @var bool
     * @readonly
     */
    public $enforceLengthLimits;

    /**
     * @var bool
     * @readonly
     */
    public $includeDomainAscii;

    /**
     * @var bool
     * @readonly
     */
    public $validateDisplayNamePhrase;

    /**
     * @var bool
     * @readonly
     */
    public $strictIdna;

    /**
     * @var bool
     * @readonly
     */
    public $allowObsRoute;

    /**
     * @readonly
     * @var bool
     */
    public $trimSingleAddressWhitespace;

    /**
     * @readonly
     * @var bool
     */
    public $strictMultiWhitespace;

    /**
     * @readonly
     * @var bool
     */
    public $rejectTrailingDot;

    /**
     * @var bool
     */
    public $detectConfusableDomain;

    /**
     * @var ?\Closure
     * @readonly
     */
    public $localPartNormalizer;
    /** @var array<string, bool> */
    private $bannedChars = [];
    /** @var array<string, bool> */
    private $separators = [];
    /**
     * @var bool
     */
    private $useWhitespaceAsSeparator;
    /**
     * @var \Email\LengthLimits
     */
    private $lengthLimits;
    /**
     * Whitespace characters treated as insignificant (folding/separators in
     * multi-address mode; trimmable). A whitespace character outside this set is
     * an invalid character. In single-address parsing, CR and LF are always
     * rejected regardless of this set — a lone addr-spec has no line endings.
     *
     * @var array<string, bool>
     */
    private $allowedWhitespace = [];

    /**
     * Construct a parser configuration.
     *
     * The first four positional parameters preserve the v2.x / v3.0 signature for
     * backward compatibility. The 15 rule properties following them are readonly
     * (PHP 8.1) — mutate via the `withX()` fluent builders, which return new
     * instances with the change applied.
     *
     * Default values match legacy (v2.x) parser behavior so `new ParseOptions()`
     * preserves existing call sites.
     *
     * @param array<string>     $bannedChars
     * @param array<string>     $separators
     * @param array<string>     $allowedWhitespace
     * @param LengthLimits|null $lengthLimits       Email length limits; RFC defaults when null.
     *
     * @param bool              $allowUtf8LocalPart        Allow UTF-8 in local-part (RFC 6531 §3.3, 6532 §3.2).
     * @param bool              $allowObsLocalPart         Allow obs-local-part (RFC 5322 §4.4): leading/trailing/consecutive dots.
     * @param bool              $allowQuotedString         Allow quoted-string local-part (RFC 5322 §3.2.4, 5321 §4.1.2).
     * @param bool              $validateQuotedContent     Validate qtext/quoted-pair rules in quoted strings.
     * @param bool              $rejectEmptyQuotedLocalPart Reject `""@domain` (RFC 5321 EID 5414).
     * @param bool              $allowUtf8Domain           Allow U-label domains (RFC 6531 §3.3, 5890/5891).
     * @param bool              $allowDomainLiteral        Allow `[IP]` / `[IPv6:addr]` (RFC 5321 §4.1.3).
     * @param bool              $requireFqdn               Require fully-qualified domain name (RFC 5321 §2.3.5).
     * @param bool              $validateIpGlobalRange     Validate IP literals are in the global range.
     * @param bool              $rejectC0Controls          Reject C0 control chars U+0000-U+001F (RFC 5321 §4.1.2).
     * @param bool              $rejectC1Controls          Reject C1 control chars U+0080-U+009F (RFC 6530 §10.1, 6532 §3.2).
     * @param bool              $applyNfcNormalization     Apply NFC Unicode normalization (RFC 6532 §3.1).
     * @param bool              $enforceLengthLimits       Enforce RFC 5321 §4.5.3.1 length limits.
     * @param bool              $includeDomainAscii        Emit punycode domain in output.
     * @param bool              $validateDisplayNamePhrase Enforce RFC 5322 §3.2.5 phrase syntax for unquoted display names (atext + WSP only).
     * @param bool              $strictIdna                Apply full IDNA2008 conformance on U-label domains (CONTEXTJ/O, Bidi rule, STD3, nontransitional mapping).
     * @param bool              $allowObsRoute             Accept RFC 5322 §4.4 obs-route source-route prefix inside angle-addr (e.g. `<@host1,@host2:user@host3>`); the route is captured and the real addr-spec is used ("accept and discard" per spec).
     * @param ?\Closure         $localPartNormalizer       Optional callback `fn(string $localPart, string $domain): string` invoked after local-part validation succeeds. The returned string replaces `local_part_parsed` in the output (and is re-quoted if needed). Typical uses: Gmail dot-insensitivity, `+tag` plus-addressing.
     */
    public function __construct(
        array $bannedChars = [],
        array $separators = [','],
        bool $useWhitespaceAsSeparator = true,
        ?LengthLimits $lengthLimits = null,
        array $allowedWhitespace = [' ', "\t", "\r", "\n"],
        bool $allowUtf8LocalPart = true,
        bool $allowObsLocalPart = false,
        bool $allowQuotedString = true,
        bool $validateQuotedContent = false,
        bool $rejectEmptyQuotedLocalPart = false,
        bool $allowUtf8Domain = true,
        bool $allowDomainLiteral = true,
        bool $requireFqdn = false,
        bool $validateIpGlobalRange = true,
        bool $rejectC0Controls = false,
        bool $rejectC1Controls = false,
        bool $applyNfcNormalization = false,
        bool $enforceLengthLimits = true,
        bool $includeDomainAscii = false,
        bool $validateDisplayNamePhrase = false,
        bool $strictIdna = false,
        bool $allowObsRoute = false,
        bool $trimSingleAddressWhitespace = false,
        bool $strictMultiWhitespace = false,
        bool $rejectTrailingDot = false,
        bool $detectConfusableDomain = false,
        ?\Closure $localPartNormalizer = null
    ) {
        $this->allowUtf8LocalPart = $allowUtf8LocalPart;
        $this->allowObsLocalPart = $allowObsLocalPart;
        $this->allowQuotedString = $allowQuotedString;
        $this->validateQuotedContent = $validateQuotedContent;
        $this->rejectEmptyQuotedLocalPart = $rejectEmptyQuotedLocalPart;
        $this->allowUtf8Domain = $allowUtf8Domain;
        $this->allowDomainLiteral = $allowDomainLiteral;
        $this->requireFqdn = $requireFqdn;
        $this->validateIpGlobalRange = $validateIpGlobalRange;
        $this->rejectC0Controls = $rejectC0Controls;
        $this->rejectC1Controls = $rejectC1Controls;
        $this->applyNfcNormalization = $applyNfcNormalization;
        $this->enforceLengthLimits = $enforceLengthLimits;
        $this->includeDomainAscii = $includeDomainAscii;
        $this->validateDisplayNamePhrase = $validateDisplayNamePhrase;
        $this->strictIdna = $strictIdna;
        $this->allowObsRoute = $allowObsRoute;
        $this->trimSingleAddressWhitespace = $trimSingleAddressWhitespace;
        $this->strictMultiWhitespace = $strictMultiWhitespace;
        $this->rejectTrailingDot = $rejectTrailingDot;
        $this->detectConfusableDomain = $detectConfusableDomain;
        $this->localPartNormalizer = $localPartNormalizer;
        foreach ($bannedChars as $char) {
            $this->bannedChars[$char] = true;
        }
        foreach ($separators as $sep) {
            $this->separators[$sep] = true;
        }
        $this->useWhitespaceAsSeparator = $useWhitespaceAsSeparator;
        $this->lengthLimits = $lengthLimits ?? LengthLimits::createDefault();
        foreach ($allowedWhitespace as $ws) {
            $this->allowedWhitespace[$ws] = true;
        }
    }

    /** @return array<string, bool> */
    public function getAllowedWhitespace(): array
    {
        return $this->allowedWhitespace;
    }

    // ===== RFC Preset Factory Methods =====

    /**
     * RFC 5321 Mailbox — strict ASCII-only, matching what SMTP servers must accept.
     *
     * Follows RFC 5321 §4.1.2 (Local-part), §4.1.3 (domain literals),
     * §4.5.3.1 (length limits), and §2.3.5 (FQDN). No obs-local-part, no UTF-8.
     */
    public static function rfc5321(): self
    {
        return new self([], [','], true, null, [' ', '	', '', '
'], false, false, true, true, true, false, true, true, true, true, false, false, true, false);
    }

    /**
     * RFC 6531/6532 — full internationalized email (EAI), strictest validation.
     *
     * Extends RFC 5321 Mailbox per RFC 6531 §3.3 and RFC 6532 §3 (UTF-8 in
     * addr-spec and headers). Adds NFC normalization (RFC 6532 §3.1),
     * C1-control rejection (RFC 6530 §10.1), and punycode output for IDNs.
     */
    public static function rfc6531(): self
    {
        return new self([], [','], true, null, [' ', '	', '', '
'], true, false, true, true, true, true, true, true, true, true, true, true, true, true, false, true);
    }

    /**
     * RFC 5322 addr-spec — recommended default for new code.
     *
     * Enforces dot-atom local-part structure per §3.2.3 (`1*atext *("." 1*atext)`):
     * a leading, trailing, or consecutive dot is rejected. This matches the actual
     * obs-local-part ABNF (§4.4: `word *("." word)`, words non-empty) — obs-local-part
     * never permitted empty words either. For the maximally permissive dot placement
     * some legacy systems emit, use rfc2822() or `->withAllowObsLocalPart(true)`.
     * ASCII only; no UTF-8 in local-part or domain.
     */
    public static function rfc5322(): self
    {
        return new self([], [','], true, null, [' ', '	', '', '
'], false, false, true, false, false, false, true, false, true, true, false, false, true, false, false, false, true);
    }

    /**
     * RFC 2822 — maximum compatibility with older software.
     *
     * Like rfc5322() but also permits C0 controls, which were not explicitly
     * prohibited by RFC 2822. Use only when accepting addresses from very old
     * or non-conforming systems.
     */
    public static function rfc2822(): self
    {
        return new self([], [','], true, null, [' ', '	', '', '
'], false, true, true, false, false, false, true, false, true, false, false, false, true, false, false, false, true);
    }

    // ===== Fluent builders =====
    //
    // The readonly rule properties cannot be reassigned. Each `withX()` method
    // returns a new ParseOptions instance with the single field replaced and
    // every other field preserved. The four non-readonly state fields
    // (bannedChars, separators, useWhitespaceAsSeparator, lengthLimits) also
    // have `withX()` builders for symmetry; they will become readonly in v4.0.

    /** @param array<string> $bannedChars */
    public function withBannedChars($bannedChars): self
    {
        return $this->cloneWith(['bannedChars' => $bannedChars]);
    }

    /** @param array<string> $separators */
    public function withSeparators($separators): self
    {
        return $this->cloneWith(['separators' => $separators]);
    }

    /**
     * @param bool $value
     */
    public function withUseWhitespaceAsSeparator($value): self
    {
        return $this->cloneWith(['useWhitespaceAsSeparator' => $value]);
    }

    /**
     * @param \Email\LengthLimits $limits
     */
    public function withLengthLimits($limits): self
    {
        return $this->cloneWith(['lengthLimits' => $limits]);
    }

    /**
     * Set the whitespace characters treated as insignificant. Pass a subset to
     * enforce strictness, e.g. `[' ', "\t", "\n"]` to reject a lone CR.
     *
     * @param array<string> $chars
     */
    public function withAllowedWhitespace($chars): self
    {
        return $this->cloneWith(['allowedWhitespace' => $chars]);
    }

    /**
     * @param bool $value
     */
    public function withTrimSingleAddressWhitespace($value): self
    {
        return $this->cloneWith(['trimSingleAddressWhitespace' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withStrictMultiWhitespace($value): self
    {
        return $this->cloneWith(['strictMultiWhitespace' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withRejectTrailingDot($value): self
    {
        return $this->cloneWith(['rejectTrailingDot' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withDetectConfusableDomain($value): self
    {
        return $this->cloneWith(['detectConfusableDomain' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withAllowUtf8LocalPart($value): self
    {
        return $this->cloneWith(['allowUtf8LocalPart' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withAllowObsLocalPart($value): self
    {
        return $this->cloneWith(['allowObsLocalPart' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withAllowQuotedString($value): self
    {
        return $this->cloneWith(['allowQuotedString' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withValidateQuotedContent($value): self
    {
        return $this->cloneWith(['validateQuotedContent' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withRejectEmptyQuotedLocalPart($value): self
    {
        return $this->cloneWith(['rejectEmptyQuotedLocalPart' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withAllowUtf8Domain($value): self
    {
        return $this->cloneWith(['allowUtf8Domain' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withAllowDomainLiteral($value): self
    {
        return $this->cloneWith(['allowDomainLiteral' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withRequireFqdn($value): self
    {
        return $this->cloneWith(['requireFqdn' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withValidateIpGlobalRange($value): self
    {
        return $this->cloneWith(['validateIpGlobalRange' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withRejectC0Controls($value): self
    {
        return $this->cloneWith(['rejectC0Controls' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withRejectC1Controls($value): self
    {
        return $this->cloneWith(['rejectC1Controls' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withApplyNfcNormalization($value): self
    {
        return $this->cloneWith(['applyNfcNormalization' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withEnforceLengthLimits($value): self
    {
        return $this->cloneWith(['enforceLengthLimits' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withIncludeDomainAscii($value): self
    {
        return $this->cloneWith(['includeDomainAscii' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withValidateDisplayNamePhrase($value): self
    {
        return $this->cloneWith(['validateDisplayNamePhrase' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withStrictIdna($value): self
    {
        return $this->cloneWith(['strictIdna' => $value]);
    }

    /**
     * @param bool $value
     */
    public function withAllowObsRoute($value): self
    {
        return $this->cloneWith(['allowObsRoute' => $value]);
    }

    /**
     * Supply a local-part normalizer callback, or `null` to clear any current one.
     *
     * The callback is invoked after local-part validation succeeds with
     * `fn(string $localPart, string $domain): string`. Its return value
     * replaces `local_part_parsed` in the output — typical uses are Gmail
     * dot-insensitivity (`john.doe` → `johndoe`) and plus-addressing
     * (`user+tag` → `user`), typically gated on the domain.
     *
     *   $opts = ParseOptions::rfc5322()->withLocalPartNormalizer(
     *       fn(string $local, string $domain): string =>
     *           $domain === 'gmail.com'
     *               ? strtolower(strstr(str_replace('.', '', $local), '+', true) ?: str_replace('.', '', $local))
     *               : $local,
     *   );
     * @param callable|null $normalizer
     */
    public function withLocalPartNormalizer($normalizer): self
    {
        return $this->cloneWith([
            'localPartNormalizer' => $normalizer === null ? null : \Closure::fromCallable($normalizer),
        ]);
    }

    /**
     * Build a new ParseOptions preserving every current value except those
     * listed in $overrides.
     *
     * @param array<string, mixed> $overrides
     */
    private function cloneWith(array $overrides): self
    {
        $get = function (string $name, $default) use ($overrides) {
            return $overrides[$name] ?? $default;
        };

        return new self(
            $get('bannedChars', array_keys($this->bannedChars)),
            $get('separators', array_keys($this->separators)),
            $get('useWhitespaceAsSeparator', $this->useWhitespaceAsSeparator),
            $get('lengthLimits', $this->lengthLimits),
            $get('allowedWhitespace', array_keys($this->allowedWhitespace)),
            $get('allowUtf8LocalPart', $this->allowUtf8LocalPart),
            $get('allowObsLocalPart', $this->allowObsLocalPart),
            $get('allowQuotedString', $this->allowQuotedString),
            $get('validateQuotedContent', $this->validateQuotedContent),
            $get('rejectEmptyQuotedLocalPart', $this->rejectEmptyQuotedLocalPart),
            $get('allowUtf8Domain', $this->allowUtf8Domain),
            $get('allowDomainLiteral', $this->allowDomainLiteral),
            $get('requireFqdn', $this->requireFqdn),
            $get('validateIpGlobalRange', $this->validateIpGlobalRange),
            $get('rejectC0Controls', $this->rejectC0Controls),
            $get('rejectC1Controls', $this->rejectC1Controls),
            $get('applyNfcNormalization', $this->applyNfcNormalization),
            $get('enforceLengthLimits', $this->enforceLengthLimits),
            $get('includeDomainAscii', $this->includeDomainAscii),
            $get('validateDisplayNamePhrase', $this->validateDisplayNamePhrase),
            $get('strictIdna', $this->strictIdna),
            $get('allowObsRoute', $this->allowObsRoute),
            $get('trimSingleAddressWhitespace', $this->trimSingleAddressWhitespace),
            $get('strictMultiWhitespace', $this->strictMultiWhitespace),
            $get('rejectTrailingDot', $this->rejectTrailingDot),
            $get('detectConfusableDomain', $this->detectConfusableDomain),
            array_key_exists('localPartNormalizer', $overrides)
                ? $overrides['localPartNormalizer']
                : $this->localPartNormalizer
        );
    }

    // ===== Legacy deprecated setters =====
    //
    // These remain as mutating setters for the four non-readonly state fields
    // only. They continue to work for v2.x callers; they will be removed in v4.0.

    /**
     * @deprecated v3.0 — Use constructor param or withBannedChars(). Removed in v4.0.
     * @param array<string> $bannedChars
     */
    public function setBannedChars($bannedChars): void
    {
        $this->bannedChars = [];
        foreach ($bannedChars as $char) {
            $this->bannedChars[$char] = true;
        }
    }

    /** @return array<string, bool> */
    public function getBannedChars(): array
    {
        return $this->bannedChars;
    }

    /**
     * @deprecated v3.0 — Use constructor param or withSeparators(). Removed in v4.0.
     * @param array<string> $separators
     */
    public function setSeparators($separators): void
    {
        $this->separators = [];
        foreach ($separators as $sep) {
            $this->separators[$sep] = true;
        }
    }

    /** @return array<string, bool> */
    public function getSeparators(): array
    {
        return $this->separators;
    }

    /** @deprecated v3.0 — Use constructor param or withUseWhitespaceAsSeparator(). Removed in v4.0.
     * @param bool $value */
    public function setUseWhitespaceAsSeparator($value): void
    {
        $this->useWhitespaceAsSeparator = $value;
    }

    public function getUseWhitespaceAsSeparator(): bool
    {
        return $this->useWhitespaceAsSeparator;
    }

    /** @deprecated v3.0 — Use constructor param or withLengthLimits(). Removed in v4.0.
     * @param \Email\LengthLimits $limits */
    public function setLengthLimits($limits): void
    {
        $this->lengthLimits = $limits;
    }

    public function getLengthLimits(): LengthLimits
    {
        return $this->lengthLimits;
    }

    /** @deprecated v3.0 — Construct a new LengthLimits and pass it. Removed in v4.0.
     * @param int $value */
    public function setMaxLocalPartLength($value): void
    {
        $this->lengthLimits = new LengthLimits(
            $value,
            $this->lengthLimits->maxTotalLength,
            $this->lengthLimits->maxDomainLabelLength
        );
    }

    public function getMaxLocalPartLength(): int
    {
        return $this->lengthLimits->maxLocalPartLength;
    }

    /** @deprecated v3.0 — Construct a new LengthLimits and pass it. Removed in v4.0.
     * @param int $value */
    public function setMaxTotalLength($value): void
    {
        $this->lengthLimits = new LengthLimits(
            $this->lengthLimits->maxLocalPartLength,
            $value,
            $this->lengthLimits->maxDomainLabelLength
        );
    }

    public function getMaxTotalLength(): int
    {
        return $this->lengthLimits->maxTotalLength;
    }

    /** @deprecated v3.0 — Construct a new LengthLimits and pass it. Removed in v4.0.
     * @param int $value */
    public function setMaxDomainLabelLength($value): void
    {
        $this->lengthLimits = new LengthLimits(
            $this->lengthLimits->maxLocalPartLength,
            $this->lengthLimits->maxTotalLength,
            $value
        );
    }

    public function getMaxDomainLabelLength(): int
    {
        return $this->lengthLimits->maxDomainLabelLength;
    }
}
