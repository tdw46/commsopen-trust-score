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

$stable = TrustScore::calculate(array(
	'accountAgeDays' => 730,
	'profileCompleteness' => 1,
	'verifiedIdentity' => true,
	'approvedCreator' => true,
	'commentsAuthored' => 20,
	'notesAuthored' => 8,
	'starsReceived' => 60,
	'helpfulNoteVotesReceived' => 30,
	'followers' => 40,
));
$insufficientRecent = TrustScore::calculate(array(
	'accountAgeDays' => 730,
	'profileCompleteness' => 1,
	'recentStarsReceived' => 2,
	'recentEvidenceCount' => 2,
));
assert($insufficientRecent['recent']['active'] === false);

$positiveRecent = TrustScore::calculate(array(
	'accountAgeDays' => 730,
	'profileCompleteness' => 1,
	'recentCommentsAuthored' => 8,
	'recentNotesAuthored' => 2,
	'recentStarsReceived' => 20,
	'recentHelpfulNoteVotesReceived' => 12,
	'recentConsensusAlignedActions' => 5,
	'recentConsensusEvaluatedActions' => 5,
	'recentResponseOpportunities' => 4,
	'recentResponses' => 4,
	'recentEvidenceCount' => 15,
	'recentViralPortfolioPieces' => 1,
));
assert($positiveRecent['recent']['active'] === true);
assert($positiveRecent['recent']['adjustment'] > 0);

$severeRecent = TrustScore::calculate(array(
	'accountAgeDays' => 730,
	'profileCompleteness' => 1,
	'verifiedIdentity' => true,
	'approvedCreator' => true,
	'commentsAuthored' => 20,
	'notesAuthored' => 8,
	'starsReceived' => 60,
	'helpfulNoteVotesReceived' => 30,
	'followers' => 40,
	'recentEvidenceCount' => 1,
	'recentModerationPenalties' => 40,
));
assert($severeRecent['score'] < $stable['score'] - 30);
assert($severeRecent['recent']['severePenalty'] > 0);
assert(in_array($stable['band'], array('cube', 'initiate', 'adept', 'artisan', 'alchemist', 'sorcerer', 'wizard'), true));
assert($stable['badge']['label'] === 'Wizard');
assert($stable['badge']['minimum'] === 90);
assert($stable['badge']['maximum'] === 100);

$publishedRanks = TrustScore::ranks();
assert(count($publishedRanks) === 7);
assert(array_column($publishedRanks, 'key') === array('cube', 'initiate', 'adept', 'artisan', 'alchemist', 'sorcerer', 'wizard'));

$badgeMethod = new ReflectionMethod(TrustScore::class, 'badge');
$rankCases = array(
	0.0 => 'cube',
	25.0 => 'initiate',
	40.0 => 'adept',
	55.0 => 'artisan',
	67.0 => 'alchemist',
	79.0 => 'sorcerer',
	90.0 => 'wizard',
);
foreach ($rankCases as $rankScore => $expectedRank) {
	assert($badgeMethod->invoke(null, (float) $rankScore)['key'] === $expectedRank);
}

echo "TrustScore tests passed\n";
