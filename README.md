# CommsOpen Trust Score

An auditable, rules-based reputation score for creator communities. The score
uses account history, constructive participation, feedback received, consensus
alignment, and conditional responsiveness. A person is never penalized for having no
messages or comments to answer: response factors enter the denominator only
after an actual response opportunity exists.

Version 2 adds two explicit horizons. The long-term score remains the stable
baseline. A 30-day signal activates after three recent pieces of evidence (or
immediately for a serious moderation event or viral contribution), then applies
a bounded momentum adjustment. Positive recent history can meaningfully lift a
profile, while severe recent moderation events can override the normal long-term
bias. No recent evidence leaves the long-term score unchanged.

The algorithm intentionally excludes private message content, comment content,
raw IP addresses, precise location, purchases, payment volume, protected-class
inferences, and opaque machine-learning judgments.

## Principles

- Explain every factor and its available points.
- Cap every activity so volume cannot dominate quality forever.
- Count message/comment responsiveness only when an opportunity exists.
- Score evaluative actions by agreement with eventual community sentiment. A
  downvote on a broadly rejected note is as useful as an upvote on a broadly
  supported note; indiscriminate positivity does not raise trust. The voter's
  own action is excluded when calculating the consensus used to assess it, and
  a single other vote is not treated as community consensus.
- Keep moderation penalties explicit and separately reported.
- Treat a post-scoped network-uniqueness marker as a small anti-brigading signal,
  never as identity or location evidence.
- Recalculate from canonical platform events; do not sell score boosts.
- Require timestamped activity for the recent component. Never infer that an
  undated lifetime aggregate happened recently.
- Treat viral boosts as bounded recognition for unusually helpful Notes or
  strongly engaged portfolio work, not as a purchasable score multiplier.

## Usage

```php
use CommsOpen\Trust\TrustScore;

$result = TrustScore::calculate([
    'accountAgeDays' => 420,
    'profileCompleteness' => 0.8,
    'commentsAuthored' => 12,
    'starsReceived' => 24,
    'messageResponseOpportunities' => 3,
    'messageResponses' => 3,
]);
```

`score` is 0–100. `factors` contains the backward-compatible long-term factor
list. `longTerm` and `recent` expose each horizon, its factors, penalties, and
the final recent adjustment.
Weights are versioned in `TrustScore::VERSION`; changing them requires a new
version and a changelog entry.

## Rank badges

The seven display ranks are `Default Cube`, `Aspiring Art Mage`, `Mage's Apprentice`,
`Arcane Artisan`, `Visual Alchemist`, `Pipeline Sorcerer`, and `Wizard`. They
describe community trust history only; they do not represent artistic talent,
technical skill, professional seniority, or identity verification. API clients
should use `badge.key` for stable behavior and `badge.label` for display.
`TrustScore::ranks()` publishes the full ordered ladder and exact thresholds:
0–24, 25–39, 40–54, 55–66, 67–78, 79–89, and 90–100.

## Privacy and limitations

This is a community-context signal, not proof of identity, honesty, authorship,
artistic skill, or professional ability. Small/new accounts naturally have less
evidence; the entry rank is `Default Cube`, not “untrustworthy.” Platforms should provide
appeal and moderation review paths and should not use this score as the sole
basis for irreversible decisions.

MIT licensed. See [LICENSE](LICENSE).

Optional inputs `externalReputationVerified`, `stackExchangeReputation` and `stackOverflowReputation` support a positive-only external bonus. Hosts must authenticate ownership and obtain public-display consent before setting the verification flag. Use the highest non-Overflow community reputation for Stack Exchange, and the Stack Overflow value separately; never count Overflow twice. For each platform the bonus is `min(5, 5 * log(1 + max(0, reputation - 1)) / log(10001))`. Starting reputation, missing accounts and unverified inputs add zero; the combined bonus is capped at ten and the final score remains capped at 100. Outputs add `communityScore` and `externalReputation` with the adjustment and per-platform factors.

The linked bonus is a lifetime signal applied once after the long-term baseline and recent adjustment. It is outside the baseline denominator and does not activate the recent window, contribute dated evidence, momentum, or viral activity. Interfaces should explain this in both horizon summaries while displaying the bonus once in the final calculation. Ownership verification and public-display consent are separate from a link merely existing.

## Validation

```sh
php -d zend.assertions=1 -d assert.exception=1 tests/TrustScoreTest.php
php tests/ExternalReputationTest.php
```
