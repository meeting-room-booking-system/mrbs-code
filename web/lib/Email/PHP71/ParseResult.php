<?php

namespace Email\PHP71; // Changed for MRBS

/**
 * Immutable value object representing the outcome of parsing one or more addresses.
 *
 * Produced by {@see Parse::parseMultiple()}. For single-address parsing,
 * {@see Parse::parseSingle()} returns a {@see ParsedEmailAddress} directly.
 */
final class ParseResult
{
    /**
     * @var bool
     * @readonly
     */
    public $success;
    /**
     * @var ?string
     * @readonly
     */
    public $reason;
    /**
     * @var array<int, ParsedEmailAddress>
     * @readonly
     */
    public $emailAddresses;
    /**
     * @param bool                      $success        `true` when every address parsed successfully.
     * @param ?string                   $reason         Summary failure message when `$success` is `false`; else `null`.
     * @param array<int, ParsedEmailAddress> $emailAddresses Parsed addresses in input order.
     */
    public function __construct(bool $success, ?string $reason, array $emailAddresses)
    {
        $this->success = $success;
        $this->reason = $reason;
        $this->emailAddresses = $emailAddresses;
    }

    /**
     * Build from the array shape produced by {@see Parse::parse()} in multi-address mode.
     *
     * @param array{success: bool, reason: ?string, email_addresses: array<int, array<string, mixed>>} $arr
     */
    public static function fromArray(array $arr): self
    {
        return new self(
            $arr['success'],
            $arr['reason'],
            array_map(
                function (array $a) {
                    return ParsedEmailAddress::fromArray($a);
                },
                $arr['email_addresses']
            )
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
                function (ParsedEmailAddress $a) {
                    return $a->toArray();
                },
                $this->emailAddresses
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
