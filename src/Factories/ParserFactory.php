<?php

namespace Adzinpratama\TrackingLogin\Factories;

use Adzinpratama\TrackingLogin\Parsers\Agent;
use Adzinpratama\TrackingLogin\Parsers\WhichBrowser;

class ParserFactory
{
    /**
     * Build a new user-agent parser.
     *
     * @param string $name
     * @return Agent|WhichBrowser
     * @throws \Exception
     */
    public static function build($name)
    {
        switch ($name) {
            case 'agent':
                return new Agent();
            case 'whichbrowser':
                return new WhichBrowser();
            default:
                throw new \Exception('Choose a supported User-Agent parser.');
        }
    }
}
