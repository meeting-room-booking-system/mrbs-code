<?php

namespace Email;

/**
 * Immutable value object representing a single parsed email address.
 *
 * Produced by {@see Parse::parseSingle()} and {@see Parse::parseMultiple()}.
 * Every field is also present in the legacy array output of {@see Parse::parse()};
 * callers preferring typed access with IDE autocomplete should use the new methods.
 */
final class ParsedEmailAddress implements \Stringable
{
    /**
     * @param string              $address           Canonical address, comments stripped (e.g. `"J Doe" <j@x.com>`).
     * @param string              $originalAddress   Raw address as given, comments included.
     * @param string              $simpleAddress     local-part@domain-part (no display name).
     * @param string              $name              Display name including surrounding quotes if quoted.
     * @param string              $nameParsed        Display name without quotes.
     * @param string              $localPart         Local-part including quotes if quoted.
     * @param string              $localPartParsed   Local-part without quotes.
     * @param string              $domain            Domain after `@` (may be Unicode / U-label). Empty when an IP literal is used.
     * @param ?string             $domainAscii       Punycode (A-label) domain when `ParseOptions::$includeDomainAscii` is `true`; else `null`.
     * @param string              $ip                IP address if a domain-literal `[IP]` was used; else empty string.
     * @param string              $domainPart        Domain or `[IP]` as it appears after the `@`.
     * @param bool                $invalid           `true` if the address failed validation.
     * @param ?string             $invalidReason     Human-readable failure reason; `null` if valid.
     * @param ?ParseErrorCode     $invalidReasonCode Structured failure code; `null` if valid.
     * @param array<int, string>  $comments          RFC 5322 comments extracted from the address.
     * @param ?string             $obsRoute          RFC 5322 §4.4 obs-route prefix if one was stripped from inside angle-addr (e.g. `@host1,@host2`); `null` otherwise. Only populated when {@see ParseOptions::$allowObsRoute} is enabled.
     */
    public function __construct(
        public readonly string $address,
        public readonly string $originalAddress,
        public readonly string $simpleAddress,
        public readonly string $name,
        public readonly string $nameParsed,
        public readonly string $localPart,
        public readonly string $localPartParsed,
        public readonly string $domain,
        public readonly ?string $domainAscii,
        public readonly string $ip,
        public readonly string $domainPart,
        public readonly bool $invalid,
        public readonly ?string $invalidReason,
        public readonly ?ParseErrorCode $invalidReasonCode,
        public readonly array $comments,
        public readonly ?string $obsRoute = null,
    ) {
    }

    /**
     * Build from the array shape produced by {@see Parse::parse()}.
     *
     * @param array<string,mixed> $arr
     */
    public static function fromArray(array $arr): self
    {
        return new self(
            address:           $arr['address'],
            originalAddress:   $arr['original_address'],
            simpleAddress:     $arr['simple_address'],
            name:              $arr['name'],
            nameParsed:        $arr['name_parsed'],
            localPart:         $arr['local_part'],
            localPartParsed:   $arr['local_part_parsed'],
            domain:            $arr['domain'],
            domainAscii:       $arr['domain_ascii'],
            ip:                $arr['ip'],
            domainPart:        $arr['domain_part'],
            invalid:           $arr['invalid'],
            invalidReason:     $arr['invalid_reason'],
            invalidReasonCode: $arr['invalid_reason_code'],
            comments:          $arr['comments'],
            obsRoute:          $arr['obs_route'] ?? null,
        );
    }

    /**
     * Severity of the validation failure, derived from {@see $invalidReasonCode}.
     * Returns `null` when the address is valid (no failure to classify).
     *
     * Callers can use this to distinguish structural failures from policy
     * violations:
     *
     *   if ($parsed->invalid && $parsed->invalidSeverity() === ValidationSeverity::Warning) {
     *       // Well-formed but violates a configured rule — e.g. private-range IP
     *       // literal, non-FQDN domain, octet length over RFC 5321 §4.5.3.1.
     *       // Safe to accept in non-SMTP contexts.
     *   }
     */
    public function invalidSeverity(): ?ValidationSeverity
    {
        return $this->invalidReasonCode?->severity();
    }

    /**
     * Round-trip to the legacy array shape produced by {@see Parse::parse()}.
     * Field order matches the parser output so the result is compatible with
     * code that consumes the array-based API. `invalidReasonCode` is emitted
     * as a `ParseErrorCode` enum (or `null`); callers wanting the string form
     * should access `$result['invalid_reason_code']?->value`.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'simple_address' => $this->simpleAddress,
            'original_address' => $this->originalAddress,
            'name' => $this->name,
            'name_parsed' => $this->nameParsed,
            'local_part' => $this->localPart,
            'local_part_parsed' => $this->localPartParsed,
            'domain_part' => $this->domainPart,
            'domain' => $this->domain,
            'domain_ascii' => $this->domainAscii,
            'ip' => $this->ip,
            'invalid' => $this->invalid,
            'invalid_reason' => $this->invalidReason,
            'invalid_reason_code' => $this->invalidReasonCode,
            'comments' => $this->comments,
            'obs_route' => $this->obsRoute,
        ];
    }

    /**
     * JSON-encoded representation. Convenience wrapper over {@see toArray()}.
     * `ParseErrorCode` serializes to its backing string value under the default
     * enum-serialization rules.
     *
     * @param int $flags Flags passed through to `json_encode` (e.g. `JSON_PRETTY_PRINT`).
     */
    public function toJson(int $flags = 0): string
    {
        $encoded = json_encode($this->toArray(), $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Canonical RFC 5322 display form for the address.
     *
     * Rules:
     *   - Invalid addresses return the empty string.
     *   - No display name: returns `local@domain` (or `local@[IP]`).
     *   - With display name: returns `Name <local@domain>` or
     *     `"Display Name" <local@domain>` when the name or local-part contains
     *     characters that require quoting per RFC 5322 §3.2.4 / §3.2.5.
     *
     * Minimal quoting is applied: quotes are only added when the content
     * contains a character outside the atext set (for the local-part) or the
     * atext + WSP set (for the display-name phrase). This differs from
     * {@see $address} which preserves whichever form the parser observed in
     * the input.
     */
    public function canonical(): string
    {
        if ($this->invalid) {
            return '';
        }

        $local = self::isAtextDotAtom($this->localPartParsed) || $this->localPartParsed === ''
            ? $this->localPartParsed
            : '"' . addcslashes($this->localPartParsed, '"\\') . '"';

        $addrSpec = $local . '@' . $this->domainPart;

        if ($this->nameParsed === '') {
            return $addrSpec;
        }

        $name = self::isPhraseAtoms($this->nameParsed)
            ? $this->nameParsed
            : '"' . addcslashes($this->nameParsed, '"\\') . '"';

        return $name . ' <' . $addrSpec . '>';
    }

    /**
     * {@inheritDoc}
     *
     * Stringable: implicitly convertible to the address's simple form
     * (`local@domain-part`) for use in string contexts like logging and
     * templating. Invalid addresses stringify to the empty string.
     */
    public function __toString(): string
    {
        return $this->invalid ? '' : $this->simpleAddress;
    }

    /**
     * True when the string conforms to RFC 5322 §3.2.3 dot-atom-text
     * (1*atext *("." 1*atext)) — i.e. can appear unquoted in an addr-spec.
     */
    private static function isAtextDotAtom(string $s): bool
    {
        return (bool) preg_match(
            "/^[A-Za-z0-9!#\$%&'*+\\-\\/=?^_`{|}~]+(?:\\.[A-Za-z0-9!#\$%&'*+\\-\\/=?^_`{|}~]+)*\$/",
            $s,
        );
    }

    /**
     * True when the string is a sequence of RFC 5322 §3.2.5 phrase atoms —
     * atext runs separated by single spaces — meaning no display-name quoting
     * is required.
     */
    private static function isPhraseAtoms(string $s): bool
    {
        return (bool) preg_match(
            "/^[A-Za-z0-9!#\$%&'*+\\-\\/=?^_`{|}~]+(?:[ \\t]+[A-Za-z0-9!#\$%&'*+\\-\\/=?^_`{|}~]+)*\$/",
            $s,
        );
    }
}
