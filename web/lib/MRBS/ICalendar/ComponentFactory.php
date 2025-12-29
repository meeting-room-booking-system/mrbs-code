<?php
declare(strict_types=1);
namespace MRBS\ICalendar;


class ComponentFactory
{
  /**
   * Create a component from a string containing the component content.
   *
   * @return Component|false  The component object, or false if the string is not a valid component.
   */
  public static function createFromString(string $content)
  {
    // Trim and unfold the content, then split it into lines,
    $lines = explode(Calendar::EOL, Calendar::unfold(trim($content)));

    // It should have at least two lines: the first BEGIN: line and the last END: line.
    if (count($lines) < 2)
    {
      trigger_error("Component has fewer than two lines: '$content'", E_USER_WARNING);
      return false;
    }

    // Work out what kind of component this is from the first line.
    $first_line = array_shift($lines);
    if (!str_starts_with($first_line, 'BEGIN:'))
    {
      trigger_error("First line of component is not BEGIN: line: '$first_line'", E_USER_WARNING);
      return false;
    }
    $component_name = mb_strtoupper(mb_substr($first_line, mb_strlen('BEGIN:')));

    // Check that the last line is a matching END: line.
    if ("END:$component_name" !== ($last_line = array_pop($lines)))
    {
      trigger_error("Last line of component is not 'END:$component_name' line: '$last_line'", E_USER_WARNING);
      return false;
    }

    // Create the component object.
    switch ($component_name)
    {
      case Event::NAME:
        $component = new Event();
        break;
      case Timezone::NAME:
        $component = new Timezone();
        break;
      default:
        trigger_error("Unknown component type: '$component_name'", E_USER_WARNING);
        return false;
        break;
    }

    // Go through the lines and add the properties to the component.
    foreach ($lines as $line)
    {
      $component->addProperty(Property::createFromString($line));
    }

    return $component;
  }


  public static function getNextFromStream($stream, ?string $component_name=null)
  {
    $lines = [];

    // Theoretically the line should be folded if it's longer than 75 octets,
    // but, just in case the file has been created without using folding, we
    // will read a large number (4096) of bytes to make sure that we get as
    // far as the end of the line.
    while (false !== ($line = stream_get_line($stream, 4096, Calendar::EOL)))
    {
      if (empty($lines))
      {
        if (str_starts_with($line, 'BEGIN:'))
        {
          // Work out what kind of component this is from the first line and see if it's the
          // one we're looking for.  If it is, or if we're not looking for a specific component,
          // then start saving the content lines.
          $this_component_name = mb_strtoupper(mb_substr($line, mb_strlen('BEGIN:')));
          if (!isset($component_name))
          {
            $component_name = $this_component_name;
          }
          elseif ($component_name !== $this_component_name)
          {
            continue;
          }
          // We've reached the start of a new component, so start saving the lines.
          $lines[] = $line;
        }
      }
      else
      {
        $lines[] = $line;
        if ($line == "END:$this_component_name")
        {
          // We've reached the end of the event, so return the Component object.
          $content = implode(Calendar::EOL, $lines);
          return ComponentFactory::createFromString($content);
        }
      }
    }

    return false;
  }

}
