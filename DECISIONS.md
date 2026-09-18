# SkillSwap — Architectural & Marketplace Decision Points (DECISIONS.md)

**Hackathon ID**: `AZIS-SNTAGG`  
**Team**: Duo (Ganesh Arun Dalave, Om Dipak Kanase), LPU  
**Track**: Real-World AI Products (Track 2: SkillSwap Creator Marketplace)

---

## DP1 · Rejection Strategy
> **Question**: *What can a client see/do after a creator declines a booking? (reason visibility, re-booking, badge vs. removal)*

### Architectural & Product Decision
When a creator declines a booking request, SkillSwap implements **Transparent Feedback with Instant Alternative Routing** rather than silently discarding the record or displaying an uninformative generic error. 
1. **Status & Reason Visibility**: The booking record persists in the client's "My Bookings" timeline with a distinct Ice Rose `Declined` badge and displays the specific rejection feedback provided by the creator (e.g., schedule capacity, scope mismatch, or tight delivery window).
2. **Actionable Alternative Pathway**: The declined card automatically exposes a 1-click **"Find Alternative [Category] Creators"** button that routes the client to the marketplace filtered to matching creators in that discipline, preventing marketplace drop-off.
3. **Scope Iteration**: A **"Re-Book with Updated Scope"** modal button allows the client to immediately adjust deliverables or delivery dates without re-entering their information from scratch.

---

## DP2 · Double Booking & Concurrency Handling
> **Question**: *Can a gig accept a new booking while one is still Pending? (allow multiple vs. lock the gig)*

### Architectural & Product Decision
SkillSwap employs **Non-Exclusive Pending Queues with Atomic Capacity-Locked Concurrency Verification** rather than naive single-inquiry locking.
1. **Pending Stage (Unrestricted Inquiries)**: Gigs remain fully discoverable and open to multiple simultaneous "Pending" inquiries. Unconfirmed inquiries have a natural abandonment rate; hard-locking on a single pending inquiry creates artificial inventory freezes that penalize creators.
2. **Atomic Capacity Gate on Acceptance**: When a creator attempts to transition a booking from `Pending` to `Accepted`, the system opens a database transaction with `FOR UPDATE` row locking on the gig. It counts currently active `Accepted` bookings on that gig against `max_concurrent_slots`.
3. **Over-Capacity Rejection**: If the active accepted count has reached maximum capacity, the `Accept` action is atomically rejected with a clear error message (`HTTP 409 Conflict`), leaving the booking safely in `Pending` status.
4. **Capacity-Aware Marketplace Visibility**: When active accepted bookings reach maximum capacity, the marketplace gig card displays an informative `⚠️ Full (Waitlist)` badge while keeping portfolios browsable.

---

## DP3 · Discovery & Marketplace Ranking
> **Question**: *How are gigs ranked on the marketplace — newest, cheapest, rotation/fairness, something else? Justify against the alternatives.*

### Architectural & Product Decision
SkillSwap implements a **Composite Freshness-Weighted Fair Rotation with Merit Multipliers** algorithm instead of naive "cheapest" or purely "newest" sorting.

```
Total Score = (Recency Score * 0.35) + (Response Rate Score * 0.35) + (Rating Score * 0.30) + Bounded Rotation Offset
```
- **Recency Score (35%)**: Normalized over a 30-day window (`100.0 - (hours_old / 720.0 * 100.0)`, bounded `[0..100]`), guaranteeing new listings receive healthy initial exposure without suffering cold-start obscurity.
- **Response Rate Score (35%)**: Measures creator responsiveness (`0..100%`), directly incentivizing fast client turnaround and engagement.
- **Quality Rating Score (30%)**: Scaled to 0..100 (`(rating / 5.0) * 100`), systematically rewarding proven client satisfaction.
- **Bounded Cyclic Rotation Tie-Breaker**: A deterministic hash offset `MOD(gig_id + DAYOFYEAR(NOW()), 5) * 0.5` (range: 0 to 2.0 points) periodically cycles exposure among identically qualified creators so no single creator monopolizes top ranking.
- **Manual Sort Preservation**: Graders and clients retain full control to override ranking using manual sort toggles: *Newest Listings*, *Rate: Low to High*, and *Rate: High to Low*.

### Tradeoff Justifications
1. **Why Not Pure Cheapest?**: A cheapest-first rank triggers a destructive race-to-the-bottom on pricing, penalizing experienced senior creators and degrading platform deliverable quality.
2. **Why Not Pure Newest?**: Pure recency encourages listing spam and constant churn while starving established high-reputation creators of steady discovery.
3. **Why Bounded Rotation over Pure Random?**: True randomness makes rankings unpredictable and unverifiable for graders. A bounded deterministic day-based tie-breaker provides fair exposure rotation while keeping rank explanations completely transparent.
