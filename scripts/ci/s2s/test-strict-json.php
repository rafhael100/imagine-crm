<?php

declare(strict_types=1);

use App\Homologation\S2S\StrictJson;

require dirname(__DIR__, 3).'/app/Homologation/S2S/StrictJson.php';

// Standalone regression: no Laravel boot, network, credentials or SQL.
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException('STRICT_JSON_TEST_FAILED: '.$label);
    }
    $checks++;
};
$reject = static function (string $wire, string $label, int $limit = 4096, int $depth = 8) use ($check): void {
    try {
        StrictJson::decodeObject($wire, $limit, $depth);
    } catch (JsonException) {
        $check(true, $label);
        return;
    }
    $check(false, $label);
};

$check(StrictJson::decodeObject('{}') === [], 'empty object');
$check(StrictJson::decodeObject(" \t\n{\"a\" : 1}\r ") === ['a' => 1], 'JSON whitespace');
$types = '{"integer":1,"decimal":1.5,"exponent":1e3,"boolean":false,"nothing":null,"list":[1,"2",true,null]}';
$check(StrictJson::decodeObject($types) === [
    'integer' => 1, 'decimal' => 1.5, 'exponent' => 1000.0,
    'boolean' => false, 'nothing' => null, 'list' => [1, '2', true, null],
], 'native scalar and array types');

$strings = ['text' => '{"company_id":"A","company_id":"B"}: []',
    'escaped' => 'quote " and backslash \\ and slash /',
    'unicode' => 'São Paulo 🚀', 'control' => "\0\n\t"];
$check(StrictJson::decodeObject(json_encode($strings, JSON_THROW_ON_ERROR)) === $strings,
    'syntax-looking content inside string values');
$siblings = '{"left":{"a":1},"right":{"a":2},"items":[{"a":3},{"a":4}],"a":5}';
$check(StrictJson::decodeObject($siblings) === json_decode($siblings, true, 8, JSON_THROW_ON_ERROR),
    'identical names in different object scopes');
$check(StrictJson::decodeObject('{"0":1,"00":2,"01":3}') === [0 => 1, '00' => 2, '01' => 3],
    'distinct numeric-looking names');
$check(StrictJson::decodeObject('{"e\\u0301":1,"é":2}') === ["e\u{0301}" => 1, 'é' => 2],
    'no unintended Unicode normalization');

$duplicates = [
    'literal' => '{"company_id":"A","company_id":"B"}',
    'identical values' => '{"company_id":"A","company_id":"A"}',
    'escaped ASCII name' => '{"company_id":"A","company_\\u0069d":"B"}',
    'escaped name first' => '{"\\u0061":1,"a":2}',
    'escaped non-ASCII name' => '{"é":1,"\\u00e9":2}',
    'surrogate pair name' => '{"🚀":1,"\\ud83d\\ude80":2}',
    'escaped slash' => '{"a/b":1,"a\\/b":2}',
    'escaped quote' => '{"a\"b":1,"a\\u0022b":2}',
    'escaped backslash' => '{"a\\\\b":1,"a\\u005cb":2}',
    'NUL name' => '{"\\u0000":1,"\\u0000":2}',
    'numeric name' => '{"0":1,"\\u0030":2}',
    'empty name' => '{"":1,"":2}',
    'previous null' => '{"a":null,"a":2}',
    'previous false' => '{"a":false,"a":2}',
    'nested object' => '{"safe":1,"nested":{"a":1,"a":2}}',
    'object within array' => '{"items":[{"safe":1},{"a":1,"a":2}]}',
    'nested duplicate in escaped value context' => '{"text":"}:[]","nested":{"a":":{","a":"}"}}',
];
foreach ($duplicates as $label => $wire) {
    $check(is_array(json_decode($wire, true, 8, JSON_THROW_ON_ERROR)),
        'duplicate fixture is otherwise valid JSON: '.$label);
    $reject($wire, 'duplicate '.$label);
}

foreach (['[]', '[{}]', 'null', 'true', '0', '1.5', '"string"'] as $wire) {
    $reject($wire, 'root must be object: '.$wire);
}
foreach (['', '{', '{"a":1,}', '{"a":1}{"b":2}', '{"a":/* comment */1}',
    '{"a":"\\ud800"}', '{"a":01}', '{"a":"\\x41"}', "{\"a\":\"\xB1\"}",
    "\xEF\xBB\xBF{}"] as $index => $wire) {
    $reject($wire, 'malformed input '.$index);
}

$exact = '{"x":"'.str_repeat('a', 4088).'"}';
$check(strlen($exact) === 4096 && strlen(StrictJson::decodeObject($exact)['x']) === 4088,
    'exact byte limit accepted');
$reject($exact.' ', 'byte limit includes trailing whitespace');
$multibyte = '{"x":"é"}';
$reject($multibyte, 'limit counts UTF-8 bytes', strlen($multibyte) - 1);

$deep = str_repeat('{"a":', 7).'0'.str_repeat('}', 7);
$check(StrictJson::decodeObject($deep, 4096, 8) === json_decode($deep, true, 8, JSON_THROW_ON_ERROR),
    'permitted nested object depth');
$reject(str_repeat('{"a":', 8).'0'.str_repeat('}', 8), 'object depth exceeded');
$reject('{"a":'.str_repeat('[', 8).'0'.str_repeat(']', 8).'}', 'array depth exceeded');
foreach ([[1, 8], [4096, 0], [4096, 65]] as [$limit, $depth]) {
    try {
        StrictJson::decodeObject('{}', $limit, $depth);
        $check(false, 'invalid parser configuration');
    } catch (InvalidArgumentException) {
        $check(true, 'invalid parser configuration rejected');
    }
}

echo 'S2S_STRICT_JSON_PASS checks='.$checks."\n";
