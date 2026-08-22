<?php

namespace Email\PHP71; // Changed for MRBS

/**
 * PHP 7.1-compatible emulation of a backed (string) enum, for the php7.1 build
 * of the 3.x line (native enums require PHP 8.1). A using class exposes one
 * static accessor per case — e.g. ParseErrorCode::LocalPartTooLong() — each
 * returning a shared singleton so identity comparison (===) and match/switch
 * behave like a native enum. The backing value is the public $value property.
 */
trait EnumEmulation
{
    /** @var string case name, e.g. "LocalPartTooLong" (native enum ->name) */
    public $name;

    /** @var string backing value, e.g. "local_part_too_long" (native enum ->value) */
    public $value;

    /** @var array<string, array<string, self>> keyed by class then value */
    private static $pool = [];

    private function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Return the shared instance for a case (created once per class).
     *
     * @return static
     */
    protected static function instance(string $name, string $value)
    {
        $class = static::class;
        if (!isset(self::$pool[$class][$value])) {
            self::$pool[$class][$value] = new static($name, $value);
        }

        return self::$pool[$class][$value];
    }

    /**
     * Serialize to the backing value, matching a native backed enum's JSON form.
     * ReturnTypeWillChange keeps this compatible with JsonSerializable across PHP
     * versions (an attribute on 8.0+, an inert `#` comment on 7.1).
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->value;
    }

    /**
     * Resolve a case by backing value, or throw (native enum from() semantics).
     *
     * @return static
     */
    public static function from(string $value)
    {
        $case = static::tryFrom($value);
        if ($case === null) {
            throw new \ValueError(sprintf('%s is not a valid backing value for enum %s', var_export($value, true), static::class));
        }

        return $case;
    }

    /**
     * Resolve a case by backing value, or null (native enum tryFrom()).
     *
     * @return static|null
     */
    public static function tryFrom(string $value)
    {
        foreach (static::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return null;
    }
}
