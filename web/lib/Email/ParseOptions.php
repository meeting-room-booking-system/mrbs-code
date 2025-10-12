<?php

namespace Email;

class ParseOptions
{
    private $bannedChars = [];

    public function __construct(array $bannedChars = [])
    {
        if ($bannedChars) {
            $this->setBannedChars($bannedChars);
        }
    }

    public function setBannedChars(array $bannedChars)
    {
        $this->bannedChars = [];
        foreach ($bannedChars as $bannedChar) {
            $this->bannedChars[$bannedChar] = true;
        }
    }

    /**
     * @return array|null
     */
    public function getBannedChars()
    {
        return $this->bannedChars;
    }
}
