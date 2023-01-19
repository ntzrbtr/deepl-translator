<?php

declare(strict_types=1);

namespace Netzarbeiter\DeeplTranslator;

/**
 * Application
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * Application constructor
     */
    public function __construct()
    {
        parent::__construct('DeepL Translator', '1.0.0');
        $this->add(new Command\TranslateCommand());
        $this->setDefaultCommand('translate', true);
    }
}
