<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace OpenPsa\Ranger\Provider;

use IntlDateFormatter;
use OpenPsa\Ranger\Ranger;

class DeProvider implements Provider
{
    /**
     * {@inheritDoc}
     */
    public function modifySeparator(IntlDateFormatter $intl, int $best_match, string $separator) : string
    {
        if (   $best_match < Ranger::YEAR
            || $best_match > Ranger::MONTH
            || $intl->getDateType() < IntlDateFormatter::MEDIUM) {
            $separator = ' ' . trim($separator) . ' ';
        }
        if (   $best_match == Ranger::MONTH
            || (   $intl->getDateType() > IntlDateFormatter::LONG
                && $best_match == Ranger::YEAR)) {
            return '.' . $separator;
        }
        return $separator;
    }
}
