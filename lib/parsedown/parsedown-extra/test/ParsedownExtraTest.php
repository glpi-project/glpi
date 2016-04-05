<?php

class ParsedownExtraTest extends ParsedownTest
{
    protected function initDirs()
    {
        $dirs = parent::initDirs();

        $dirs []= dirname(__FILE__).'/data/';

        return $dirs;
    }

    protected function initParsedown()
    {
        $Parsedown = new ParsedownExtra();

        return $Parsedown;
    }
}
