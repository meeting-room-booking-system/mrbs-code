<?php
declare(strict_types=1);

/**
 * An emulation of the IntlDateFormatter class for use when the intl extension is not loaded.
 *
 * When it is loaded, we still have the option of using the emulation which (a) is useful for
 * testing and (b) sometimes gives better results if the ICU library is out of date.
 */
class IntlDateFormatter extends \MRBS\Intl\IntlDateFormatter
{

}
