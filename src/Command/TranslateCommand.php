<?php

declare(strict_types=1);

namespace Netzarbeiter\DeeplTranslator\Command;

use DeepL\LanguageCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to translate language files
 */
class TranslateCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * @inheritDoc
     */
    protected static $defaultName = 'translate';

    /**
     * @inheritDoc
     */
    protected static $defaultDescription = 'Translate a language file using DeepL';

    /**
     * I/O helper
     *
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    protected \Symfony\Component\Console\Style\SymfonyStyle $io;

    /**
     * Translator
     *
     * @var \DeepL\Translator
     */
    protected \DeepL\Translator $translator;

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source language file'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Target language file'
            )
            ->addOption(
                'source-language',
                's',
                InputOption::VALUE_REQUIRED,
                'Source language',
                LanguageCode::ENGLISH
            )
            ->addOption(
                'target-language',
                't',
                InputOption::VALUE_REQUIRED,
                'Target language',
                LanguageCode::GERMAN
            );
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
        $this->io->title(self::$defaultDescription);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Check DeepL API key.
        if (!$_ENV['DEEPL_API_KEY']) {
            $this->io->error('DeepL API key not set in environment variable DEEPL_API_KEY');
            return self::FAILURE;
        }

        // Set up translator.
        $this->translator = new \DeepL\Translator($_ENV['DEEPL_API_KEY']);

        // Check and read source file.
        $sourceFile = $input->getArgument('source');
        if (!is_string($sourceFile)) {
            $this->io->error('Source file argument is not a string');
            return self::FAILURE;
        }
        if (!is_file($sourceFile)) {
            $this->io->error('Source file does not exist');
            return self::FAILURE;
        }
        $items = require $sourceFile;

        // Check target file.
        $targetFile = $input->getArgument('target');
        if (!is_string($targetFile)) {
            $this->io->error('Target file argument is not a string');
            return self::FAILURE;
        }
        if (is_file($targetFile) && !$this->io->confirm("Target file $targetFile already exists. Overwrite?", false)) {
            return self::SUCCESS;
        }

        // Check source language.
        $sourceLanguage = $input->getOption('source-language');
        if (!is_string($sourceLanguage)) {
            $this->io->error('Source language option is not a string');
            return self::FAILURE;
        }
        if (!$this->checkLanguage($sourceLanguage)) {
            $this->io->error('Source language is not supported');
            return self::FAILURE;
        }

        // Check target language.
        $targetLanguage = $input->getOption('target-language');
        if (!is_string($targetLanguage)) {
            $this->io->error('Target language option is not a string');
            return self::FAILURE;
        }
        if (!$this->checkLanguage($targetLanguage)) {
            $this->io->error('Target language is not supported');
            return self::FAILURE;
        }

        // Count items.
        $count = $this->countItems($items);

        // Translate items.
        $this->io->info("Translating $count items in $sourceFile to $targetFile ($sourceLanguage to $targetLanguage)...");
        $this->io->progressStart($count);
        $translatedItems = $this->translate($items, $sourceLanguage, $targetLanguage);
        $this->io->progressFinish();
        $this->io->info('...done');

        // Write target file.
        $encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder([
            'string.utf8' => true,
        ]);
        $content = "<?php\n\nreturn " . $encoder->encode($translatedItems) . ";\n";
        file_put_contents($targetFile, $content);
        $this->io->success("Translations written to $targetFile");

        return self::SUCCESS;
    }

    /**
     * Check if the language is supported by DeepL.
     *
     * @param string $language
     * @return bool
     */
    protected function checkLanguage(string $language): bool
    {
        $reflection = new \ReflectionClass(LanguageCode::class);
        return in_array($language, $reflection->getConstants(), true);
    }

    /**
     * Count the number of items in the array (recursively).
     *
     * @param array $items
     * @return int
     */
    protected function countItems(array $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (is_array($item)) {
                $count += $this->countItems($item);
            } else {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Translate an array of items.
     *
     * @param array $items
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @return array
     */
    protected function translate(array $items, string $sourceLanguage, string $targetLanguage): array
    {
        $translatedItems = [];
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $translatedItems[$key] = $this->translate($value, $sourceLanguage, $targetLanguage);
            } else {
                $translatedItems[$key] = $this->translator->translateText(
                    $value,
                    $sourceLanguage,
                    $targetLanguage
                )->text;
                $this->io->progressAdvance();
            }
        }

        return $translatedItems;
    }
}
