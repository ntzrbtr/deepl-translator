#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

$application = new \Netzarbeiter\DeeplTranslator\Application();
$application->run();
