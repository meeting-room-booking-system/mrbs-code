<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace OpenPsa\Ranger;

use IntlDateFormatter;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use IntlDatePatternGenerator;
use RuntimeException;
use OpenPsa\Ranger\Provider\DefaultProvider;

class Ranger
{
    const ERA = 0;
    const YEAR = 1;
    const QUARTER = 2;
    const MONTH = 3;
    const WEEK = 4;
    const DAY = 5;
    const AM = 6;
    const HOUR = 7;
    const MINUTE = 8;
    const SECOND = 9;
    const TIMEZONE = -1;
    const NO_MATCH = -2;

    /**
     * @var array
     */
    private $pattern_characters = [
        'G' => self::ERA,
        'y' => self::YEAR,
        'Y' => self::YEAR,
        'u' => self::YEAR,
        'U' => self::YEAR,
        'r' => self::YEAR,
        'Q' => self::QUARTER,
        'q' => self::QUARTER,
        'M' => self::MONTH,
        'L' => self::MONTH,
        'w' => self::WEEK,
        'W' => self::WEEK,
        'd' => self::DAY,
        'D' => self::DAY,
        'F' => self::DAY,
        'g' => self::DAY,
        'E' => self::DAY,
        'e' => self::DAY,
        'c' => self::DAY,
        'a' => self::AM,
        'B' => self::AM,
        'h' => self::HOUR,
        'H' => self::HOUR,
        'k' => self::HOUR,
        'K' => self::HOUR,
        'm' => self::MINUTE,
        's' => self::SECOND,
        'S' => self::SECOND,
        'A' => self::SECOND,
        'z' => self::TIMEZONE,
        'Z' => self::TIMEZONE,
        'O' => self::TIMEZONE,
        'v' => self::TIMEZONE,
        'V' => self::TIMEZONE,
        'X' => self::TIMEZONE,
        'x' => self::TIMEZONE
    ];

    /**
     * @var string
     */
    private $escape_character = "'";

    /**
     * @var string
     */
    private $virtual_separator = '<<<>>>';

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var int|null
     */
    private $max_precision;

    /**
     * @var string|null
     */
    private $generated_pattern;

    /**
     * @var array
     */
    private $pattern_mask;

    /**
     * @var int
     */
    private $precision;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $range_separator = '–';

    /**
     * @var string
     */
    private $date_time_separator = ', ';

    /**
     * @var int
     */
    private $date_type = IntlDateFormatter::MEDIUM;

    /**
     * @var int
     */
    private $time_type = IntlDateFormatter::NONE;

    /**
     * @param string $locale
     */
    public function __construct(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param int $type One of the IntlDateFormatter constants
     * @return self
     */
    public function setDateType(int $type)
    {
        if ($type !== $this->date_type) {
            $this->date_type = $type;
            $this->pattern_mask = [];
            $this->precision = 0;
        }
        return $this;
    }

    /**
     * @param int $type One of the IntlDateFormatter constants
     * @return self
     */
    public function setTimeType(int $type)
    {
        if ($type !== $this->time_type) {
            $this->time_type = $type;
            $this->pattern_mask = [];
            $this->precision = 0;
        }
        return $this;
    }

    /**
     * Limit precision to the given value (e.g. Ranger::MONTH or Ranger::YEAR).
     *
     * @param int|null $precision One of the Ranger constants. Pass null to reset
     * @throws RuntimeException If IntlDatePatternGenerator is not available (PHP < 8.1)
     * @return self
     */
    public function setPrecision($precision)
    {
        if ($precision !== null && !class_exists(IntlDatePatternGenerator::class)) {
            throw new RuntimeException('setPrecision() requires IntlDatePatternGenerator (PHP 8.1+ with ext-intl)');
        }
        if ($precision !== $this->max_precision) {
            $this->max_precision = $precision;
            $this->pattern_mask = [];
            $this->precision = 0;
        }
        return $this;
    }

    /**
     * @param string $separator
     * @return self
     */
    public function setRangeSeparator(string $separator)
    {
        $this->range_separator = $separator;
        return $this;
    }

    /**
     * @param string $separator
     * @return self
     */
    public function setDateTimeSeparator(string $separator)
    {
        $this->date_time_separator = $separator;
        return $this;
    }

    /**
     *
     * @param mixed $start
     * @param mixed $end
     * @return string
     */
    public function format($start, $end) : string
    {
        $start = $this->prepare_date($start);
        $end = $this->prepare_date($end);

        $this->parse_pattern();

        $best_match = $this->find_best_match($start, $end);

        $start_tokens = $this->tokenize($start);
        $end_tokens = $this->tokenize($end);

        $left = '';
        foreach ($this->pattern_mask as $i => $part) {
            if ($part['delimiter']) {
                $left .= $this->render_delimiter($part['content']);
            } else {
                if ($part['content'] > $best_match) {
                    break;
                }
                $left .= $start_tokens[$i]['content'];
            }
        }

        if ($best_match >= $this->precision) {
            // the given dates are identical for the requested rendering
            return $left;
        }

        $right = '';
        for ($j = count($this->pattern_mask) - 1; $j + 1 > $i; $j--) {
            $part = $end_tokens[$j];
            if ($part['type'] == 'delimiter') {
                $right = $this->render_delimiter($part['content']) . $right;
            } else {
                if ($part['type'] > $best_match) {
                    break;
                }
                $right = $part['content'] . $right;
            }
        }

        $left_middle = '';
        $right_middle = '';
        for ($k = $i; $k <= $j; $k++) {
            $left_middle .= $this->render_delimiter($start_tokens[$k]['content']);
            $right_middle .= $this->render_delimiter($end_tokens[$k]['content']);
        }

        return $left . $left_middle . $this->get_range_separator($best_match) . $right_middle . $right;
    }

    private function render_delimiter(string $delimiter) : string
    {
        return $delimiter !== $this->virtual_separator ? $delimiter : '';
    }

    /**
     * @param mixed $input
     * @throws InvalidArgumentException
     * @return DateTime
     */
    private function prepare_date($input) : DateTime
    {
        if ($input instanceof DateTime) {
            return $input;
        }
        if ($input instanceof DateTimeImmutable) {
            $date = new DateTime('@' . $input->getTimestamp());
            $date->setTimezone($input->getTimezone());
            return $date;
        }
        if (is_numeric($input)) {
            $date = new Datetime;
            $date->setTimestamp(intval($input));
            return $date;
        }
        if (is_string($input)) {
            return new Datetime($input);
        }
        if ($input === null) {
            return new Datetime;
        }
        throw new InvalidArgumentException("Don't know how to handle " . gettype($input));
    }

    /**
     * @param int $best_match
     * @return string
     */
    private function get_range_separator(int $best_match) : string
    {
        if ($this->precision <= self::YEAR) {
            // year-only ranges (e.g. 2015–2020) never need padding
            return $this->range_separator;
        }
        $intl = new IntlDateFormatter($this->locale, $this->date_type, $this->time_type);

        $provider_class = 'OpenPsa\\Ranger\\Provider\\' . ucfirst(substr($intl->getLocale(), 0, 2)) . 'Provider';

        if ($this->generated_pattern !== null) {
            $intl->setPattern($this->generated_pattern);
        }

        if (!class_exists($provider_class)) {
            $provider_class = DefaultProvider::class;
        }
        $provider = new $provider_class();

        return $provider->modifySeparator($intl, $best_match, $this->range_separator);
    }

    /**
     * @param DateTime $date
     * @return array
     */
    private function tokenize(DateTime $date) : array
    {
        $tokens = [];

        if ($this->pattern === '') {
            // why would you want this?
            return $tokens;
        }

        $intl = new IntlDateFormatter($this->locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $date->getTimezone(), null, $this->pattern);
        $formatted = $intl->format((int) $date->format('U'));

        $type = null;
        foreach ($this->pattern_mask as $part) {
            if ($part['delimiter']) {
                $parts = explode($part['content'], $formatted, 2);
                if (count($parts) == 2) {
                    $tokens[] = ['type' => $type, 'content' => $parts[0]];
                    $formatted = $parts[1];
                }
                $tokens[] = ['type' => 'delimiter', 'content' => $part['content']];
            } else {
                $type = $part['content'];
            }
        }
        if (!$part['delimiter']) {
            $tokens[] = ['type' => $type, 'content' => $formatted];
        }
        return $tokens;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return int
     */
    private function find_best_match(DateTime $start, DateTime $end) : int
    {
        // make a copy of end because we might change pieces of it
        $end_copy = clone $end;

        // ignore the date if it's not output
        if (!$this->has_date_fields()) {
            $end_copy->setDate($start->format('Y'), $start->format('m'), $start->format('d'));
        }

        $map = [
            'Y' => self::TIMEZONE,
            'm' => self::YEAR,
            'd' => self::MONTH,
            'a' => self::DAY,
            'H' => self::AM,
            'i' => self::AM, // it makes no sense to display something like 10:00:00 - 30:00...
            's' => self::AM, // it makes no sense to display something like 10:00:00 - 30...
        ];
        $best_match = self::SECOND;

        foreach ($map as $part => $score) {
            if ($start->format($part) !== $end_copy->format($part)) {
                $best_match = $score;
                break;
            }
        }

        if (   $best_match === self::YEAR
            && $this->has_field(self::QUARTER)
            && ceil($start->format('n') / 3) == ceil($end_copy->format('n') / 3)) {
            $best_match = self::QUARTER;
        }

        //set to same time to avoid DST problems
        $end_copy->setTimestamp((int) $start->format('U'));
        if (   $start->format('T') !== $end_copy->format('T')
            || (   $this->precision > self::DAY
                && $best_match < self::DAY)) {
            $best_match = self::NO_MATCH;
        }

        return $best_match;
    }

    private function parse_pattern()
    {
        if (!empty($this->pattern_mask)) {
            return;
        }

        $this->pattern = '';
        $date_pattern = $time_pattern = '';
        if ($this->date_type !== IntlDateFormatter::NONE) {
            $intl = new IntlDateFormatter($this->locale, $this->date_type, IntlDateFormatter::NONE);
            $date_pattern = $this->reduce_pattern($intl->getPattern());
        }
        if ($this->time_type !== IntlDateFormatter::NONE) {
            $intl = new IntlDateFormatter($this->locale, IntlDateFormatter::NONE, $this->time_type);
            $time_pattern = $this->reduce_pattern($intl->getPattern());
        }

        $pattern = $date_pattern;
        if ($date_pattern !== '' && $time_pattern !== '') {
            $pattern .= "'" . $this->date_time_separator . "'";
        }
        $pattern .= $time_pattern;
        $this->generated_pattern = $this->max_precision !== null ? $pattern : null;

        $esc_active = false;
        $part = ['content' => '', 'delimiter' => false];
        foreach (str_split($pattern) as $char) {
            if ($char == $this->escape_character) {
                if ($esc_active) {
                    $esc_active = false;
                    // @todo the esc char handling is untested
                    if ($part['content'] === '') {
                        //Literal '
                        $part['content'] = $char;
                    }
                } else {
                    $esc_active = true;
                    if (!$part['delimiter']) {
                        $this->push_to_mask($part);
                        $part = ['content' => '', 'delimiter' => true];
                    }
                }
            } elseif ($esc_active) {
                $part['content'] .= $char;
            } elseif (!array_key_exists($char, $this->pattern_characters)) {
                if ($part['delimiter'] === false) {
                    $this->push_to_mask($part);
                    $part = ['content' => $char, 'delimiter' => true];
                } else {
                    $part['content'] .= $char;
                }
            } else {
                if ($part['delimiter'] === true) {
                    $this->push_to_mask($part);
                    $part = ['content' => $this->pattern_characters[$char], 'delimiter' => false];
                } else {
                    if (   $part['content'] !== ''
                        && $part['content'] !== $this->pattern_characters[$char]) {
                        $this->push_to_mask($part);
                        $this->push_to_mask(['content' => $this->virtual_separator, 'delimiter' => true]);
                        $this->pattern .= $this->virtual_separator;
                        $part = ['content' => '', 'delimiter' => false];
                    }
                    $part['content'] = $this->pattern_characters[$char];
                }
            }
            $this->pattern .= $char;
        }
        $this->push_to_mask($part);
    }

    /**
     * Remove all fields below max_precision and let ICU build a pattern from the rest
     */
    private function reduce_pattern(string $pattern) : string
    {
        if ($this->max_precision === null) {
            return $pattern;
        }
        $skeleton = '';
        $esc_active = false;
        $has_month = false;
        foreach (str_split($pattern) as $char) {
            if ($char == $this->escape_character) {
                $esc_active = !$esc_active;
            } elseif (   !$esc_active
                      && array_key_exists($char, $this->pattern_characters)) {
                if ($this->pattern_characters[$char] <= $this->max_precision) {
                    $skeleton .= $char;
                }
                $has_month = $has_month || $this->pattern_characters[$char] === self::MONTH;
            }
        }
        if ($this->max_precision === self::QUARTER && $has_month) {
            // locale patterns don't contain quarters, so show them instead of the month.
            // The width is explicit because older ICU versions render a single Q as numeric
            $skeleton .= 'QQQ';
        }
        if ($skeleton === '') {
            return '';
        }
        return (new IntlDatePatternGenerator($this->locale))->getBestPattern($skeleton);
    }

    private function has_field(int $unit) : bool
    {
        foreach ($this->pattern_mask as $part) {
            if (!$part['delimiter'] && $part['content'] === $unit) {
                return true;
            }
        }
        return false;
    }

    private function has_date_fields() : bool
    {
        foreach ($this->pattern_mask as $part) {
            if (   !$part['delimiter']
                && $part['content'] >= self::ERA // timezone fields
                && $part['content'] <= self::DAY) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $part
     */
    private function push_to_mask(array $part)
    {
        if ($part['content'] !== '') {
            $this->pattern_mask[] = $part;
            if (!$part['delimiter']) {
                $this->precision = max($this->precision, $part['content']);
            }
        }
    }
}
