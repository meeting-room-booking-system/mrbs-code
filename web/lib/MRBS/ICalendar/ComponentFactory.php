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
    $component_name = mb_substr($first_line, mb_strlen('BEGIN:'));

    // Check that the last line is a matching END: line.
    if ("END:$component_name" !== ($last_line = array_pop($lines)))
    {
      trigger_error("Last line of component is not 'END:$component_name' line: '$last_line'", E_USER_WARNING);
      return false;
    }

    // Create the component object.
    switch ($component_name)
    {
      case Alarm::NAME:
        $component = new Alarm();
        break;
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
    while (null !== ($line = array_shift($lines)))
    {
      // Check for a nested component
      if (!str_starts_with($line, 'BEGIN:'))
      {
        // Not a nested component, so add the line as a property.
        $component->addProperty(Property::createFromString($line));
      }
      else
      {
        // We've got a nested component.
        $nested_component_name = mb_substr($line, mb_strlen('BEGIN:'));
        // Save the lines until we reach the END: line
        $nested_lines = [];
        do {
          $nested_lines[] = $line;
        } while (null !== ($line = array_shift($lines)) && ("END:$nested_component_name" !== $line));
        if (null === $line)
        {
          trigger_error("Nested $nested_component_name component does not have an END: line", E_USER_WARNING);
          return false;
        }
        // Add the END: line to the nested lines.
        $nested_lines[] = $line;
        // Get the nested component and add it to this component.
        // This code allows for an unlimited depth of nested components, though in practice only one level should be needed.
        if (false === ($nested_component = self::createFromString(implode(Calendar::EOL, $nested_lines))))
        {
          return false;
        }
        $component->addComponent($nested_component);
      }
    }

    return $component;
  }


  /**
   * Reads the next component from a stream and creates a corresponding component object.
   *
   * @param resource $stream The input stream to read the component from.
   * @param string|null $component_name The specific component name to look for, or null to read any component.
   *
   * @return Component|false         The component object if a valid component is found, or false if there are no more components.
   */
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
          $this_component_name = mb_substr($line, mb_strlen('BEGIN:'));
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
          // We've reached the end of the component, so return the Component object.
          $content = implode(Calendar::EOL, $lines);
          return ComponentFactory::createFromString($content);
        }
      }
    }

    return false;
  }

}
