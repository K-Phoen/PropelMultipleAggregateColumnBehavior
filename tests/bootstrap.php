<?php

$autoloader = require_once __DIR__ . '/../vendor/autoload.php';
$autoloader->add('', __DIR__ . '/fixtures');

set_include_path(__DIR__ . '/../vendor/phing/phing/classes' . PATH_SEPARATOR . get_include_path());

require_once __DIR__ . '/../vendor/propel/propel1/generator/lib/util/PropelQuickBuilder.php';
