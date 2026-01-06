<?php

require_once 'autoload.php';

// Test de la méthode normalizeValue pour SelectField
use WeprestaAcf\Application\FieldType\SelectField;

$selectField = new SelectField();
$fieldConfig = ['multiple' => true];

// Test avec array
$valueArray = ['value1', 'value2'];
$normalized = $selectField->normalizeValue($valueArray, $fieldConfig);
echo 'Array input: '; print_r($valueArray);
echo 'Normalized: ' . $normalized . PHP_EOL;

// Test avec string (devrait être transformé en array)
$valueString = 'value1';
$normalized2 = $selectField->normalizeValue($valueString, $fieldConfig);
echo 'String input: ' . $valueString . PHP_EOL;
echo 'Normalized: ' . $normalized2 . PHP_EOL;

// Test renderValue
$rendered = $selectField->renderValue($normalized, $fieldConfig, ['separator' => ', ']);
echo 'Rendered: ' . $rendered . PHP_EOL;
