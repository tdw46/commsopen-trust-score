# CommsOpen Trust Score

An auditable, rules-based reputation score for creator communities. The score
uses account history, constructive participation, feedback received, consensus
alignment, and conditional responsiveness. A person is never penalized for having no
messages or comments to answer: response factors enter the denominator only
after an actual response opportunity exists.

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
  own action is excluded when calculating the consensus used to assess it.
- Keep moderation penalties explicit and separately reported.
- Treat a post-scoped network-uniqueness marker as a small anti-brigading signal,
  never as identity or location evidence.
- Recalculate from canonical platform events; do not sell score boosts.

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

`score` is 0–100. `factors` contains the exact earned and available points.
Weights are versioned in `TrustScore::VERSION`; changing them requires a new
version and a changelog entry.

## Privacy and limitations

This is a community-context signal, not proof of identity, honesty, authorship,
or professional ability. Small/new accounts naturally have less evidence; the
UI should say “Limited history,” not “untrustworthy.” Platforms should provide
appeal and moderation review paths and should not use this score as the sole
basis for irreversible decisions.

MIT licensed. See [LICENSE](LICENSE).
