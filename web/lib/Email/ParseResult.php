<?php

namespace Email;

/**
 * Immutable value object representing the outcome of parsing one or more addresses.
 *
 * Produced by {@see Parse::parseMultiple()}. For single-address parsing,
 * {@see Parse::parseSingle()} returns a {@see ParsedEmailAddress} directly.
 */
final class ParseResult
{
    /**
     * @param bool                      $success        `true` when every address parsed successfully.
     * @param ?string                   $reason         Summary failure message when `$success` is `false`; else `null`.
     * @param array<int, ParsedEmailAddress> $emailAddresses Parsed addresses in input order.
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $reason,
        public readonly array $emailAddresses,
    ) {
    }

    /**
     * Build from the array shape produced by {@see Parse::parse()} in multi-address mode.
     *
     * @param array{success: bool, reason: ?string, email_addresses: array<int, array<string, mixed>>} $arr
     */
    public static function fromArray(array $arr): self
    {
        return new self(
            success: $arr['success'],
            reason:  $arr['reason'],
            emailAddresses: array_map(
                fn (array $a) => ParsedEmailAddress::fromArray($a),
                $arr['email_addresses'],
            ),
        );
    }

    /**
     * Round-trip to the array shape produced by {@see Parse::parse()} in
     * multi-address mode. Each address is serialized via
     * {@see ParsedEmailAddress::toArray()}.
     *
     * @return array{success: bool, reason: ?string, email_addresses: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'reason' => $this->reason,
            'email_addresses' => array_map(
                fn (ParsedEmailAddress $a) => $a->toArray(),
                $this->emailAddresses,
            ),
        ];
    }

    /**
     * JSON-encoded representation. Convenience wrapper over {@see toArray()}.
     *
     * @param int $flags Flags passed through to `json_encode` (e.g. `JSON_PRETTY_PRINT`).
     */
    public function toJson(int $flags = 0): string
    {
        $encoded = json_encode($this->toArray(), $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }
}
