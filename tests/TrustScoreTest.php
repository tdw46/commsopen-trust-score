<?php

require dirname(__DIR__) . '/src/TrustScore.php';

use CommsOpen\Trust\TrustScore;

$withoutMessages = TrustScore::calculate(array('accountAgeDays' => 100));
$withUnansweredMessage = TrustScore::calculate(array('accountAgeDays' => 100, 'messageResponseOpportunities' => 1, 'messageResponses' => 0));
$withAnsweredMessage = TrustScore::calculate(array('accountAgeDays' => 100, 'messageResponseOpportunities' => 1, 'messageResponses' => 1));

assert($withoutMessages['availablePoints'] < $withUnansweredMessage['availablePoints']);
assert($withAnsweredMessage['score'] > $withUnansweredMessage['score']);
assert($withoutMessages['score'] >= $withUnansweredMessage['score']);
assert(TrustScore::calculate(array('accountAgeDays' => 9999, 'starsReceived' => 9999))['score'] <= 100);

$alignedNegative = TrustScore::calculate(array('consensusAlignedActions' => 8, 'consensusEvaluatedActions' => 10));
$mostlyDisagrees = TrustScore::calculate(array('consensusAlignedActions' => 2, 'consensusEvaluatedActions' => 10));
assert($alignedNegative['score'] > $mostlyDisagrees['score']);

echo "TrustScore tests passed\n";
