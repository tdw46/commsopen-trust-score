<?php

declare(strict_types=1);

namespace CommsOpen\Trust;

/**
 * Pure, platform-neutral reputation scoring.
 *
 * Inputs must be non-negative counts except moderationPenalties. Private message
 * text, comment text, IP addresses, and identity documents are intentionally not
 * inputs. Inapplicable response categories are excluded from the denominator.
 */
final class TrustScore {
	public const VERSION = '1.0.0';

	public static function calculate(array $input): array {
		$ageDays = max(0, (int) ($input['accountAgeDays'] ?? 0));
		$profileCompleteness = self::unit($input['profileCompleteness'] ?? 0);
		$verified = ! empty($input['verifiedIdentity']);
		$creator = ! empty($input['approvedCreator']);

		$factors = array(
			self::factor('Account history', min(15.0, 15.0 * log(1 + $ageDays) / log(1 + 730)), 15.0),
			self::factor('Profile completeness', 5.0 * $profileCompleteness, 5.0),
			self::factor('Verified and approved identity', ($verified ? 6.0 : 0.0) + ($creator ? 4.0 : 0.0), 10.0),
			self::countFactor('Constructive comments', $input['commentsAuthored'] ?? 0, 20, 8.0),
			self::countFactor('Community Notes contributed', $input['notesAuthored'] ?? 0, 8, 8.0),
			self::countFactor('Stars received', $input['starsReceived'] ?? 0, 60, 16.0),
			self::countFactor('Helpful Community Note votes received', $input['helpfulNoteVotesReceived'] ?? 0, 30, 10.0),
			self::countFactor('Followers earned', $input['followers'] ?? 0, 40, 7.0),
		);
		$evaluatedActions = max(0, (int) ($input['consensusEvaluatedActions'] ?? 0));
		if ($evaluatedActions > 0) {
			$factors[] = self::ratioFactor('Community sentiment alignment', $input['consensusAlignedActions'] ?? 0, $evaluatedActions, 10.0);
		}

		$messageOpportunities = max(0, (int) ($input['messageResponseOpportunities'] ?? 0));
		if ($messageOpportunities > 0) {
			$factors[] = self::ratioFactor('Message responsiveness', $input['messageResponses'] ?? 0, $messageOpportunities, 8.0);
		}
		$commentOpportunities = max(0, (int) ($input['commentResponseOpportunities'] ?? 0));
		if ($commentOpportunities > 0) {
			$factors[] = self::ratioFactor('Comment responsiveness', $input['commentResponses'] ?? 0, $commentOpportunities, 7.0);
		}
		if (array_key_exists('networkUnique', $input)) {
			$factors[] = self::factor('Vote network uniqueness', ! empty($input['networkUnique']) ? 4.0 : 0.0, 4.0);
		}

		$earned = array_sum(array_column($factors, 'earned'));
		$available = array_sum(array_column($factors, 'available'));
		$penalty = min(35.0, max(0.0, (float) ($input['moderationPenalties'] ?? 0)));
		$score = $available > 0 ? max(0.0, min(100.0, ($earned / $available) * 100.0 - $penalty)) : 0.0;

		return array(
			'version' => self::VERSION,
			'score' => round($score, 2),
			'band' => $score >= 80 ? 'high' : ($score >= 55 ? 'established' : ($score >= 30 ? 'developing' : 'limited')),
			'label' => $score >= 80 ? 'High trust' : ($score >= 55 ? 'Established' : ($score >= 30 ? 'Developing' : 'Limited history')),
			'factors' => $factors,
			'moderationPenalty' => round($penalty, 2),
			'availablePoints' => round($available, 2),
		);
	}

	private static function factor(string $label, float $earned, float $available): array {
		return array('label' => $label, 'earned' => round(max(0.0, min($available, $earned)), 2), 'available' => $available);
	}

	private static function countFactor(string $label, mixed $count, int $target, float $available): array {
		return self::factor($label, $available * min(1.0, max(0, (int) $count) / $target), $available);
	}

	private static function ratioFactor(string $label, mixed $responses, int $opportunities, float $available): array {
		return self::factor($label, $available * min(1.0, max(0, (int) $responses) / max(1, $opportunities)), $available);
	}

	private static function unit(mixed $value): float {
		return max(0.0, min(1.0, (float) $value));
	}
}
