#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use App\ReportCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$command = new ReportCommand();

$application->add($command);
$application->setDefaultCommand($command->getName());
$application->run();
