<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace OpenPsa\Ranger\Provider;

use OpenPsa\Ranger\Ranger;
use IntlDateFormatter;

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
            || (   $best_match == Ranger::YEAR
                && $this->has_dotted_numeric_month($intl->getPattern()))) {
            return '.' . $separator;
        }
        return $separator;
    }

    private function has_dotted_numeric_month(string $pattern) : bool
    {
        // strip quoted literals, then look for M, MM, L or LL followed by a dot (MMM and up are month names)
        $pattern = preg_replace("/'[^']*'/", '', $pattern);
        return preg_match('/(?<![ML])[ML]{1,2}\./', $pattern) === 1;
    }
}
