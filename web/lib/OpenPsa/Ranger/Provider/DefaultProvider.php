<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace OpenPsa\Ranger\Provider;

use IntlDateFormatter;
use OpenPsa\Ranger\Ranger;

class DefaultProvider implements Provider
{
    /**
     * {@inheritDoc}
     */
    public function modifySeparator(IntlDateFormatter $intl, int $best_match, string $separator) : string
    {
        if (   $best_match != Ranger::MONTH
            || $intl->getDateType() < IntlDateFormatter::MEDIUM) {
            return ' ' . trim($separator) . ' ';
        }
        return $separator;
    }
}
