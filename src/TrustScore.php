<?php

declare(strict_types=1);

namespace CommsOpen\Trust;

/**
 * Pure, platform-neutral reputation scoring.
 *
 * Inputs must be non-negative counts except explicit moderation penalties.
 * Private message text, comment text, IP addresses, and identity documents are
 * intentionally not inputs. Inapplicable response categories are excluded from
 * the denominator.
 */
final class TrustScore {
	public const VERSION = '2.0.2';
	public const RECENT_WINDOW_DAYS = 30;
	public const MIN_RECENT_EVIDENCE = 3;

	public static function calculate(array $input): array {
		$longTerm = self::longTerm($input);
		$recent = self::recent($input);
		$score = $longTerm['score'];

		if ($recent['active']) {
			$momentum = self::clamp(($recent['score'] - 50.0) * 0.22, -11.0, 11.0);
			$viralBoost = min(12.0,
				max(0, (int) ($input['recentViralNotes'] ?? 0)) * 6.0
				+ max(0, (int) ($input['recentViralPortfolioPieces'] ?? 0)) * 5.0
			);
			$recentPenalty = $recent['moderationPenalty'];
			$severeOverride = $recentPenalty >= 15.0 ? min(45.0, ($recentPenalty - 10.0) * 1.2) : 0.0;
			$recent['adjustment'] = round($momentum + $viralBoost - $severeOverride, 2);
			$recent['momentumAdjustment'] = round($momentum, 2);
			$recent['viralBoost'] = round($viralBoost, 2);
			$recent['severePenalty'] = round($severeOverride, 2);
			$score += $recent['adjustment'];
		}

		$score = self::clamp($score, 0.0, 100.0);
		$badge = self::badge($score);

		return array(
			'version' => self::VERSION,
			'score' => (int) round($score),
			'band' => $badge['key'],
			'label' => $badge['label'],
			'badge' => $badge,
			'longTerm' => $longTerm,
			'recent' => $recent,
			'factors' => $longTerm['factors'],
			'moderationPenalty' => $longTerm['moderationPenalty'],
			'availablePoints' => $longTerm['availablePoints'],
		);
	}

	private static function longTerm(array $input): array {
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
		$score = $available > 0 ? self::clamp(($earned / $available) * 100.0 - $penalty, 0.0, 100.0) : 0.0;
		return array(
			'score' => round($score, 2),
			'factors' => $factors,
			'moderationPenalty' => round($penalty, 2),
			'availablePoints' => round($available, 2),
		);
	}

	private static function recent(array $input): array {
		$comments = max(0, (int) ($input['recentCommentsAuthored'] ?? 0));
		$notes = max(0, (int) ($input['recentNotesAuthored'] ?? 0));
		$stars = max(0, (int) ($input['recentStarsReceived'] ?? 0));
		$helpful = max(0, (int) ($input['recentHelpfulNoteVotesReceived'] ?? 0));
		$evaluated = max(0, (int) ($input['recentConsensusEvaluatedActions'] ?? 0));
		$aligned = max(0, min($evaluated, (int) ($input['recentConsensusAlignedActions'] ?? 0)));
		$responseOpportunities = max(0, (int) ($input['recentResponseOpportunities'] ?? 0));
		$responses = max(0, min($responseOpportunities, (int) ($input['recentResponses'] ?? 0)));
		$moderationPenalty = min(60.0, max(0.0, (float) ($input['recentModerationPenalties'] ?? 0)));
		$viralEvents = max(0, (int) ($input['recentViralNotes'] ?? 0)) + max(0, (int) ($input['recentViralPortfolioPieces'] ?? 0));
		$evidence = max(0, (int) ($input['recentEvidenceCount'] ?? ($comments + $notes + $stars + $helpful + $evaluated + $responseOpportunities)));
		$active = $evidence >= self::MIN_RECENT_EVIDENCE || $moderationPenalty > 0 || $viralEvents > 0;

		$factors = array(
			self::countFactor('Recent constructive contributions', $comments + $notes, 10, 10.0),
			self::countFactor('Recent stars received', $stars, 20, 10.0),
			self::countFactor('Recent helpful Note votes', $helpful, 12, 10.0),
		);
		if ($evaluated > 0) {
			$factors[] = self::ratioFactor('Recent sentiment alignment', $aligned, $evaluated, 12.0);
		}
		if ($responseOpportunities > 0) {
			$factors[] = self::ratioFactor('Recent responsiveness', $responses, $responseOpportunities, 8.0);
		}

		$behaviorPenalty = 0.0;
		if ($evaluated >= 3) {
			$behaviorPenalty += max(0.0, 0.5 - ($aligned / $evaluated)) * 30.0;
		}
		if ($responseOpportunities >= 3) {
			$behaviorPenalty += max(0.0, 0.5 - ($responses / $responseOpportunities)) * 12.0;
		}
		$positive = array_sum(array_column($factors, 'earned'));
		$score = self::clamp(50.0 + $positive - $behaviorPenalty - $moderationPenalty, 0.0, 100.0);

		return array(
			'active' => $active,
			'windowDays' => self::RECENT_WINDOW_DAYS,
			'minimumEvidence' => self::MIN_RECENT_EVIDENCE,
			'evidenceCount' => $evidence,
			'score' => round($score, 2),
			'factors' => $factors,
			'behaviorPenalty' => round($behaviorPenalty, 2),
			'moderationPenalty' => round($moderationPenalty, 2),
			'adjustment' => 0.0,
			'momentumAdjustment' => 0.0,
			'viralBoost' => 0.0,
			'severePenalty' => 0.0,
		);
	}

	public static function ranks(): array {
		return array(
			array('key' => 'cube', 'label' => 'Default Cube', 'icon' => '◇', 'minimum' => 0, 'maximum' => 24, 'description' => 'New member or not enough history yet'),
			array('key' => 'initiate', 'label' => 'Aspiring Art Mage', 'icon' => '✎', 'minimum' => 25, 'maximum' => 39, 'description' => 'Beginning to establish a creative community history'),
			array('key' => 'adept', 'label' => "Mage's Apprentice", 'icon' => '✧', 'minimum' => 40, 'maximum' => 54, 'description' => 'A growing record of reliable participation'),
			array('key' => 'artisan', 'label' => 'Arcane Artisan', 'icon' => '✦', 'minimum' => 55, 'maximum' => 66, 'description' => 'Established community reliability'),
			array('key' => 'alchemist', 'label' => 'Visual Alchemist', 'icon' => '⚗', 'minimum' => 67, 'maximum' => 78, 'description' => 'Strong and consistently constructive history'),
			array('key' => 'sorcerer', 'label' => 'Pipeline Sorcerer', 'icon' => '☄', 'minimum' => 79, 'maximum' => 89, 'description' => 'Deep, dependable community history'),
			array('key' => 'wizard', 'label' => 'Wizard', 'icon' => '✹', 'minimum' => 90, 'maximum' => 100, 'description' => 'Exceptional, sustained community trust'),
		);
	}

	private static function badge(float $score): array {
		$ranks = array_reverse(self::ranks());
		foreach ($ranks as $rank) {
			if ($score >= $rank['minimum']) {
				return $rank;
			}
		}
		return self::ranks()[0];
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
		return self::clamp((float) $value, 0.0, 1.0);
	}

	private static function clamp(float $value, float $minimum, float $maximum): float {
		return max($minimum, min($maximum, $value));
	}
}
