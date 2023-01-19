# DeepL Translator

This is a simple command-line tool to translate files containing locale strings in the form of PHP array (as e.g. used
by Laravel) using the DeepL API.

## Installation

Install the package using composer: `composer require --dev netzarbeiter/deepl-translate`.

## Setup

- Create a DeepL API key at https://www.deepl.com/pro#developer
- Create a `.env` file in the root of your project and add the following line: `DEEPL_API_KEY=your-api-key`

## Usage

- Run `vendor/bin/deepl-translate [options] <source> <target>` to translate your file
    - `<source>` is the source file
    - `<target>` is the target file
    - Within `[options]` you can set the source and target language:
      - `--source-language` (default: `en`)
      - `--target-language` (default: `de`)
