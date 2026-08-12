<?php

namespace Email\Branch2dot2;  // Changed by MRBS!

class ParseOptions
{
    /**
     * @var array<string, bool>
     */
    private $bannedChars = [];

    /**
     * @var array<string, bool>
     */
    private $separators = [];

    /**
     * @var bool
     */
    private $useWhitespaceAsSeparator = true;

    /**
     * @param array<string> $bannedChars
     * @param array<string> $separators
     * @param bool $useWhitespaceAsSeparator
     */
    public function __construct(array $bannedChars = [], array $separators = [','], $useWhitespaceAsSeparator = true)
    {
        if ($bannedChars) {
            $this->setBannedChars($bannedChars);
        }
        $this->setSeparators($separators);
        $this->useWhitespaceAsSeparator = $useWhitespaceAsSeparator;
    }

    /**
     * @param array<string> $bannedChars
     * @return void
     */
    public function setBannedChars(array $bannedChars)
    {
        $this->bannedChars = [];
        foreach ($bannedChars as $bannedChar) {
            $this->bannedChars[$bannedChar] = true;
        }
    }

    /**
     * @return array<string, bool>
     */
    public function getBannedChars()
    {
        return $this->bannedChars;
    }

    /**
     * @param array<string> $separators
     * @return void
     */
    public function setSeparators(array $separators)
    {
        $this->separators = [];
        foreach ($separators as $separator) {
            $this->separators[$separator] = true;
        }
    }

    /**
     * @return array<string, bool>
     */
    public function getSeparators()
    {
        return $this->separators;
    }

    /**
     * @param bool $useWhitespaceAsSeparator
     * @return void
     */
    public function setUseWhitespaceAsSeparator($useWhitespaceAsSeparator)
    {
        $this->useWhitespaceAsSeparator = $useWhitespaceAsSeparator;
    }

    /**
     * @return bool
     */
    public function getUseWhitespaceAsSeparator()
    {
        return $this->useWhitespaceAsSeparator;
    }
}
