<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use MRBS\DB\DBStatement;
use MRBS\Exception;
use MRBS\Language;
use MRBS\RepeatRule;
use MRBS\Utf8\Utf8String;
use function MRBS\get_mrbs_version;
use function MRBS\row_cast_columns;
use function MRBS\unpack_status;

require_once MRBS_ROOT . '/version.inc';

class Calendar
{
  private const NAME = 'VCALENDAR';
  private const CR = "\r";
  private const LF = "\n";
  private const MAX_OCTETS_IN_LINE =75;  // The maximum line length allowed
  private const LINE_FOLD = self::EOL . ' '; // The RFC also allows a horizontal tab instead of a space
  private const LINE_FOLD_OCTETS = 1;

  public const EOL = self::CR . self::LF;

  private $components = [];
  private $properties = [];


  public function __construct(?string $method=null)
  {
    $this->properties[] = new Property('PRODID', '-//MRBS//NONSGML ' . get_mrbs_version() . '//EN');
    $this->properties[] = new Property('VERSION', '2.0');
    $this->properties[] = new Property('CALSCALE', 'GREGORIAN');
    if (isset($method))
    {
      $this->properties[] = new Property('METHOD', $method);
    }
  }


  public function addComponent(Component $component) : self
  {
    $this->components[] = $component;
    return $this;
  }


  public function addComponents(array $components) : self
  {
    foreach ($components as $component)
    {
      $this->addComponent($component);
    }
    return $this;
  }

  public function toString(): string
  {
    $result = 'BEGIN:' . self::NAME . self::EOL;

    foreach ($this->properties as $property)
    {
      $result .= $property->toString();
    }

    foreach ($this->components as $component)
    {
      $result .= $component->toString();
    }

    $result .= 'END:' . self::NAME . self::EOL;
    return self::fold($result);
  }


  /**
   * "Fold" lines longer than 75 octets. Multibyte safe.
   */
  public static function fold(string $str) : string
  {
    // "Lines of text SHOULD NOT be longer than 75 octets, excluding the line
    // break.  Long content lines SHOULD be split into a multiple line
    // representations using a line "folding" technique.  That is, a long
    // line can be split between any two characters by inserting a CRLF
    // immediately followed by a single linear white-space character (i.e.,
    // SPACE or HTAB).  Any sequence of CRLF followed immediately by a
    // single linear white-space character is ignored (i.e., removed) when
    // processing the content type."  (RFC 5545)

    // Deal with the trivial case
    if ($str === '')
    {
      return $str;
    }

    // We assume that we are using UTF-8 and therefore that a space character
    // is one octet long.  If we ever switched for some reason to using, for
    // example, UTF-16, this assumption would be invalid.
    if ((Language::MRBS_CHARSET != 'utf-8') || (Language::MAIL_CHARSET != 'utf-8'))
    {
      throw new Exception("MRBS: internal error - using unsupported character set");
    }

    $utf8_string = new Utf8String($str);

    // Simple case: no folding necessary
    if ($utf8_string->byteCount() <= self::MAX_OCTETS_IN_LINE)
    {
      return $str;
    }

    // Iterate through the characters working out when to insert a fold
    $result = '';
    $n_chars = count($utf8_string->toArray());
    $octets = 0;
    $previous = [];

    foreach ($utf8_string as $i => $char)
    {
      // Store the character
      $previous[] = $char;

      // If it's a CR and there's at least one more character to come, then get that one.
      if (($char == self::CR) && ($i < $n_chars - 1))
      {
        continue;
      }

      // If it's a LF and the previous character was a CR, then we've reached the end of a line
      if (($char == self::LF) && (count($previous) == 2) && ($previous[0] == self::CR))
      {
        // Output the EOL, clear the previous characters and reset the octet count
        $result .= self::EOL;
        $previous = [];
        $octets = 0;
        continue;
      }

      // Otherwise output the previous characters, inserting a fold if necessary
      while (null !== ($previous_char = array_shift($previous)))
      {
        $previous_char_octets = (new Utf8String($previous_char))->byteCount();
        // If this character would take us over the line length limit, then output a line fold
        if ($octets + $previous_char_octets > self::MAX_OCTETS_IN_LINE)
        {
          $result .= self::LINE_FOLD;
          // Reset the octet count to account for the whitespace introduced during folding.
          // [Note:  It's not entirely clear from the RFC whether the octet that is introduced
          // when folding counts towards the 75 octets.   Some implementations (eg Google
          // Calendar as of Jan 2011) do not count it.  However, it can do no harm to err on
          // the safe side and include the initial whitespace in the count.]
          $octets = self::LINE_FOLD_OCTETS;
        }
        // Now output the character and add on the octets just output.
        $result .= $previous_char;
        $octets += $previous_char_octets;
      }
    }

    return $result;
  }


  /**
   * Creates and returns an iCalendar object from a database query result.
   *
   * @param DBStatement $res The result set from an SQL query on the entry table, which
   *                         has been sorted by repeat_id, start_time (both ascending).
   *                         As well as all the fields in the entry table, the rows will
   *                         also contain the area name, the room name, the timezone and
   *                         the repeat details (rep_type, end_date, rep_opt, rep_interval).
   * @param bool $keep_private Whether to mark events as private.
   * @param int $export_end Optional parameter specifying the end timestamp for exporting events. Defaults to PHP_INT_MAX.
   *
   * @return self The constructed iCalendar object.
   */
  public static function createFromStatement(DBStatement $res, bool $keep_private, int $export_end=PHP_INT_MAX) : self
  {
    // We construct an iCalendar by going through the rows from the SQL query.  Because
    // it was sorted by repeat_id we will
    //    - get all the individual entries (which will not have a repeat_id)
    //    - then get the series.    For each series we have to:
    //        - identify the series information.
    //        - identify any events that have been changed from the standard, ie events
    //          with entry_type == ENTRY_RPT_CHANGED
    //        - identify any events from the original series that have been cancelled.  We
    //          can do this because we know from the repeat information the events that
    //          should be there, and we can tell from the start times the events that are
    //          actually there.

    // We use PUBLISH rather than REQUEST because we're not inviting people to these meetings,
    // we're just exporting the calendar.   Furthermore, if we don't use PUBLISH then some
    // calendar apps (eg Outlook, at least 2010 and 2013) won't open the full calendar.
    $method = "PUBLISH";
    $calendar = new self($method);

    // We need to find all the timezones used in the result set before we can build the calendar.
    $timezones = [];
    $events = [];

    $n_rows = $res->count();

    for ($i=0; (false !== ($row = $res->next_row_keyed())); $i++)
    {
      row_cast_columns($row, 'entry');
      // Turn the last_updated column into an int (some MySQL drivers will return a string,
      // and it won't have been caught by row_cast_columns as it's a derived result).
      $row['last_updated'] = intval($row['last_updated']);
      unpack_status($row);

      // Generate a timezone component for this row, if we haven't already done so.
      if (!isset($timezones[$row['timezone']]))
      {
        $timezones[$row['timezone']] = Timezone::createFromTimezoneName($row['timezone']);
      }
      $tzid = ($row['timezone'] === false) ? null : $row['timezone'];

      // If this is an individual entry, then construct an event
      if (!isset($row['rep_type']) || ($row['rep_type'] == RepeatRule::NONE))
      {
        $events[] = Event::createFromData($method, $row, $tzid);
      }

      // Otherwise it's a series
      else
      {
        // If we haven't started a series, then start one
        if (!isset($series))
        {
          $series = new Series($row, $tzid, $export_end);
        }

        // Otherwise, if this row is a member of the current series, add the row to the series.
        elseif ($row['repeat_id'] == $series->repeat_id)
        {
          $series->addRow($row);
        }

        // If it's a series that we haven't seen yet, or we've got no more
        // rows, then process the series
        if (($row['repeat_id'] != $series->repeat_id) || ($i == $n_rows - 1))
        {
          $events = array_merge($events, $series->toEvents($method));
          // If we're at the start of a new series then create a new series
          if ($row['repeat_id'] != $series->repeat_id)
          {
            $series = new Series($row, $tzid, $export_end);
            // And if this is the last row, ie the only member of the new series
            // then process the new series
            if ($i == $n_rows - 1)
            {
              $events = array_merge($events, $series->toEvents($method));
            }
          }
        }
      }
    }

    // Now we've got all the timezones and events, add them to the calendar.
    foreach ($timezones as $timezone)
    {
      if ($timezone !== false)
      {
        $calendar->addComponent($timezone);
      }
    }

    // Use array_shift rather than foreach to save memory, by reducing the size
    // of the $events array while building the calendar.
    while (null !== ($event = array_shift($events)))
    {
      $calendar->addComponent($event);
    }

    return $calendar;
  }

}
