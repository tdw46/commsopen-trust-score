# Changelog

## 2.0.2

- Rename the two early creative trust ranks to `Aspiring Art Mage` and
  `Mage's Apprentice` while preserving their stable keys and thresholds.

## 2.0.1

- Publish the seven rank thresholds through `TrustScore::ranks()` and include
  each rank's minimum and maximum score in the badge result.

## 2.0.0

- Add a stability-weighted long-term baseline and an evidence-gated 30-day
  recent component.
- Add bounded positive momentum and viral-contribution boosts.
- Allow severe recent moderation events to override the normal long-term bias.
- Publish separate long-term, recent, and adjustment fields for transparency.
- Add seven creative rank badges from `Default Cube` through `Wizard`.

## 1.0.0

- Publish the initial capped, auditable factor model.
- Make message and comment responsiveness conditional on real opportunities.
- Score evaluative actions by leave-one-out agreement with community sentiment,
  so aligned negative and positive judgments are treated symmetrically.
- Exclude private content, raw network addresses, location, and payment value.
