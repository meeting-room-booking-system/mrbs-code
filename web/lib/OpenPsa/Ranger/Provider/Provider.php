<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace OpenPsa\Ranger\Provider;

use IntlDateFormatter;

interface Provider
{
    /**
     * @param IntlDateFormatter $intl
     * @param int $best_match
     * @param string $separator
     * @return string
     */
    public function modifySeparator(IntlDateFormatter $intl, int $best_match, string $separator) : string;
}
