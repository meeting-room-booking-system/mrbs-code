<?php

namespace Email;

class ParseOptions
{
    /** @var array<string, bool> */
    private array $bannedChars = [];
    /** @var array<string, bool> */
    private array $separators = [];
    private bool $useWhitespaceAsSeparator;
    private LengthLimits $lengthLimits;
    /**
     * Whitespace characters treated as insignificant (folding/separators in
     * multi-address mode; trimmable). A whitespace character outside this set is
     * an invalid character. In single-address parsing, CR and LF are always
     * rejected regardless of this set — a lone addr-spec has no line endings.
     *
     * @var array<string, bool>
     */
    private array $allowedWhitespace = [];

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
        public readonly bool $allowUtf8LocalPart = true,
        public readonly bool $allowObsLocalPart = false,
        public readonly bool $allowQuotedString = true,
        public readonly bool $validateQuotedContent = false,
        public readonly bool $rejectEmptyQuotedLocalPart = false,
        public readonly bool $allowUtf8Domain = true,
        public readonly bool $allowDomainLiteral = true,
        public readonly bool $requireFqdn = false,
        public readonly bool $validateIpGlobalRange = true,
        public readonly bool $rejectC0Controls = false,
        public readonly bool $rejectC1Controls = false,
        public readonly bool $applyNfcNormalization = false,
        public readonly bool $enforceLengthLimits = true,
        public readonly bool $includeDomainAscii = false,
        public readonly bool $validateDisplayNamePhrase = false,
        public readonly bool $strictIdna = false,
        public readonly bool $allowObsRoute = false,
        public readonly bool $trimSingleAddressWhitespace = false,
        public readonly ?\Closure $localPartNormalizer = null,
    ) {
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
        return new self(
            allowUtf8LocalPart: false,
            allowObsLocalPart: false,
            allowQuotedString: true,
            validateQuotedContent: true,
            rejectEmptyQuotedLocalPart: true,
            allowUtf8Domain: false,
            allowDomainLiteral: true,
            requireFqdn: true,
            validateIpGlobalRange: true,
            rejectC0Controls: true,
            rejectC1Controls: false,
            applyNfcNormalization: false,
            enforceLengthLimits: true,
            includeDomainAscii: false,
        );
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
        return new self(
            allowUtf8LocalPart: true,
            allowObsLocalPart: false,
            allowQuotedString: true,
            validateQuotedContent: true,
            rejectEmptyQuotedLocalPart: true,
            allowUtf8Domain: true,
            allowDomainLiteral: true,
            requireFqdn: true,
            validateIpGlobalRange: true,
            rejectC0Controls: true,
            rejectC1Controls: true,
            applyNfcNormalization: true,
            enforceLengthLimits: true,
            includeDomainAscii: true,
            strictIdna: true,
        );
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
        return new self(
            allowUtf8LocalPart: false,
            allowObsLocalPart: false,
            allowQuotedString: true,
            validateQuotedContent: false,
            rejectEmptyQuotedLocalPart: false,
            allowUtf8Domain: false,
            allowDomainLiteral: true,
            requireFqdn: false,
            validateIpGlobalRange: true,
            rejectC0Controls: true,
            rejectC1Controls: false,
            applyNfcNormalization: false,
            enforceLengthLimits: true,
            includeDomainAscii: false,
            allowObsRoute: true,
        );
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
        return new self(
            allowUtf8LocalPart: false,
            allowObsLocalPart: true,
            allowQuotedString: true,
            validateQuotedContent: false,
            rejectEmptyQuotedLocalPart: false,
            allowUtf8Domain: false,
            allowDomainLiteral: true,
            requireFqdn: false,
            validateIpGlobalRange: true,
            rejectC0Controls: false,
            rejectC1Controls: false,
            applyNfcNormalization: false,
            enforceLengthLimits: true,
            includeDomainAscii: false,
            allowObsRoute: true,
        );
    }

    // ===== Fluent builders =====
    //
    // The readonly rule properties cannot be reassigned. Each `withX()` method
    // returns a new ParseOptions instance with the single field replaced and
    // every other field preserved. The four non-readonly state fields
    // (bannedChars, separators, useWhitespaceAsSeparator, lengthLimits) also
    // have `withX()` builders for symmetry; they will become readonly in v4.0.

    /** @param array<string> $bannedChars */
    public function withBannedChars(array $bannedChars): self
    {
        return $this->cloneWith(['bannedChars' => $bannedChars]);
    }

    /** @param array<string> $separators */
    public function withSeparators(array $separators): self
    {
        return $this->cloneWith(['separators' => $separators]);
    }

    public function withUseWhitespaceAsSeparator(bool $value): self
    {
        return $this->cloneWith(['useWhitespaceAsSeparator' => $value]);
    }

    public function withLengthLimits(LengthLimits $limits): self
    {
        return $this->cloneWith(['lengthLimits' => $limits]);
    }

    /**
     * Set the whitespace characters treated as insignificant. Pass a subset to
     * enforce strictness, e.g. `[' ', "\t", "\n"]` to reject a lone CR.
     *
     * @param array<string> $chars
     */
    public function withAllowedWhitespace(array $chars): self
    {
        return $this->cloneWith(['allowedWhitespace' => $chars]);
    }

    public function withTrimSingleAddressWhitespace(bool $value): self
    {
        return $this->cloneWith(['trimSingleAddressWhitespace' => $value]);
    }

    public function withAllowUtf8LocalPart(bool $value): self
    {
        return $this->cloneWith(['allowUtf8LocalPart' => $value]);
    }

    public function withAllowObsLocalPart(bool $value): self
    {
        return $this->cloneWith(['allowObsLocalPart' => $value]);
    }

    public function withAllowQuotedString(bool $value): self
    {
        return $this->cloneWith(['allowQuotedString' => $value]);
    }

    public function withValidateQuotedContent(bool $value): self
    {
        return $this->cloneWith(['validateQuotedContent' => $value]);
    }

    public function withRejectEmptyQuotedLocalPart(bool $value): self
    {
        return $this->cloneWith(['rejectEmptyQuotedLocalPart' => $value]);
    }

    public function withAllowUtf8Domain(bool $value): self
    {
        return $this->cloneWith(['allowUtf8Domain' => $value]);
    }

    public function withAllowDomainLiteral(bool $value): self
    {
        return $this->cloneWith(['allowDomainLiteral' => $value]);
    }

    public function withRequireFqdn(bool $value): self
    {
        return $this->cloneWith(['requireFqdn' => $value]);
    }

    public function withValidateIpGlobalRange(bool $value): self
    {
        return $this->cloneWith(['validateIpGlobalRange' => $value]);
    }

    public function withRejectC0Controls(bool $value): self
    {
        return $this->cloneWith(['rejectC0Controls' => $value]);
    }

    public function withRejectC1Controls(bool $value): self
    {
        return $this->cloneWith(['rejectC1Controls' => $value]);
    }

    public function withApplyNfcNormalization(bool $value): self
    {
        return $this->cloneWith(['applyNfcNormalization' => $value]);
    }

    public function withEnforceLengthLimits(bool $value): self
    {
        return $this->cloneWith(['enforceLengthLimits' => $value]);
    }

    public function withIncludeDomainAscii(bool $value): self
    {
        return $this->cloneWith(['includeDomainAscii' => $value]);
    }

    public function withValidateDisplayNamePhrase(bool $value): self
    {
        return $this->cloneWith(['validateDisplayNamePhrase' => $value]);
    }

    public function withStrictIdna(bool $value): self
    {
        return $this->cloneWith(['strictIdna' => $value]);
    }

    public function withAllowObsRoute(bool $value): self
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
     */
    public function withLocalPartNormalizer(?callable $normalizer): self
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
        $get = fn (string $name, mixed $default): mixed => $overrides[$name] ?? $default;

        return new self(
            bannedChars:                $get('bannedChars', array_keys($this->bannedChars)),
            separators:                 $get('separators', array_keys($this->separators)),
            useWhitespaceAsSeparator:   $get('useWhitespaceAsSeparator', $this->useWhitespaceAsSeparator),
            lengthLimits:               $get('lengthLimits', $this->lengthLimits),
            allowedWhitespace:          $get('allowedWhitespace', array_keys($this->allowedWhitespace)),
            allowUtf8LocalPart:         $get('allowUtf8LocalPart', $this->allowUtf8LocalPart),
            allowObsLocalPart:          $get('allowObsLocalPart', $this->allowObsLocalPart),
            allowQuotedString:          $get('allowQuotedString', $this->allowQuotedString),
            validateQuotedContent:      $get('validateQuotedContent', $this->validateQuotedContent),
            rejectEmptyQuotedLocalPart: $get('rejectEmptyQuotedLocalPart', $this->rejectEmptyQuotedLocalPart),
            allowUtf8Domain:            $get('allowUtf8Domain', $this->allowUtf8Domain),
            allowDomainLiteral:         $get('allowDomainLiteral', $this->allowDomainLiteral),
            requireFqdn:                $get('requireFqdn', $this->requireFqdn),
            validateIpGlobalRange:      $get('validateIpGlobalRange', $this->validateIpGlobalRange),
            rejectC0Controls:           $get('rejectC0Controls', $this->rejectC0Controls),
            rejectC1Controls:           $get('rejectC1Controls', $this->rejectC1Controls),
            applyNfcNormalization:      $get('applyNfcNormalization', $this->applyNfcNormalization),
            enforceLengthLimits:        $get('enforceLengthLimits', $this->enforceLengthLimits),
            includeDomainAscii:         $get('includeDomainAscii', $this->includeDomainAscii),
            validateDisplayNamePhrase:  $get('validateDisplayNamePhrase', $this->validateDisplayNamePhrase),
            strictIdna:                 $get('strictIdna', $this->strictIdna),
            allowObsRoute:              $get('allowObsRoute', $this->allowObsRoute),
            trimSingleAddressWhitespace: $get('trimSingleAddressWhitespace', $this->trimSingleAddressWhitespace),
            localPartNormalizer:        array_key_exists('localPartNormalizer', $overrides)
                ? $overrides['localPartNormalizer']
                : $this->localPartNormalizer,
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
    public function setBannedChars(array $bannedChars): void
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
    public function setSeparators(array $separators): void
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

    /** @deprecated v3.0 — Use constructor param or withUseWhitespaceAsSeparator(). Removed in v4.0. */
    public function setUseWhitespaceAsSeparator(bool $value): void
    {
        $this->useWhitespaceAsSeparator = $value;
    }

    public function getUseWhitespaceAsSeparator(): bool
    {
        return $this->useWhitespaceAsSeparator;
    }

    /** @deprecated v3.0 — Use constructor param or withLengthLimits(). Removed in v4.0. */
    public function setLengthLimits(LengthLimits $limits): void
    {
        $this->lengthLimits = $limits;
    }

    public function getLengthLimits(): LengthLimits
    {
        return $this->lengthLimits;
    }

    /** @deprecated v3.0 — Construct a new LengthLimits and pass it. Removed in v4.0. */
    public function setMaxLocalPartLength(int $value): void
    {
        $this->lengthLimits = new LengthLimits(
            $value,
            $this->lengthLimits->maxTotalLength,
            $this->lengthLimits->maxDomainLabelLength,
        );
    }

    public function getMaxLocalPartLength(): int
    {
        return $this->lengthLimits->maxLocalPartLength;
    }

    /** @deprecated v3.0 — Construct a new LengthLimits and pass it. Removed in v4.0. */
    public function setMaxTotalLength(int $value): void
    {
        $this->lengthLimits = new LengthLimits(
            $this->lengthLimits->maxLocalPartLength,
            $value,
            $this->lengthLimits->maxDomainLabelLength,
        );
    }

    public function getMaxTotalLength(): int
    {
        return $this->lengthLimits->maxTotalLength;
    }

    /** @deprecated v3.0 — Construct a new LengthLimits and pass it. Removed in v4.0. */
    public function setMaxDomainLabelLength(int $value): void
    {
        $this->lengthLimits = new LengthLimits(
            $this->lengthLimits->maxLocalPartLength,
            $this->lengthLimits->maxTotalLength,
            $value,
        );
    }

    public function getMaxDomainLabelLength(): int
    {
        return $this->lengthLimits->maxDomainLabelLength;
    }
}
