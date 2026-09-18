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
SkillSwap employs **Non-Exclusive Pending Queues with Capacity-Aware Concurrency Locks** rather than naive single-inquiry locking.
1. **Pending Stage (Open Inquiries)**: Gigs remain fully discoverable and open to multiple simultaneous "Pending" inquiries because unconfirmed inquiries have a natural abandonment rate; hard-locking on a single pending inquiry creates artificial inventory freezes that penalize creators.
2. **Accepted Stage (Active Capacity Counter)**: Once a creator accepts a booking, the active load counter increments against the creator's configured maximum concurrent slot capacity (default: 3 concurrent projects).
3. **Graceful Degradation**: When active accepted projects reach maximum capacity, the gig card dynamically displays a `⚠️ Full (Waitlist)` badge rather than vanishing from search, allowing clients to review portfolios while signaling current delivery lead times.

---

## DP3 · Discovery & Marketplace Ranking
> **Question**: *How are gigs ranked on the marketplace — newest, cheapest, rotation/fairness, something else? Justify against the alternatives.*

### Architectural & Product Decision
SkillSwap implements a **Composite Freshness-Weighted Fair Rotation with Merit Multipliers** algorithm (`Score = (Recency * 0.35) + (Creator Response Rate * 0.35) + (Quality Rating * 0.30)`) instead of naive "cheapest" or purely "newest" sorting.
1. **Why Not Pure Cheapest?**: A cheapest-first rank triggers a race-to-the-bottom on pricing, penalizing experienced creators and degrading marketplace service quality.
2. **Why Not Pure Newest?**: Pure recency encourages listing spam and churn while starving established high-reputation creators of steady discovery.
3. **Cold-Start Protection & Responsiveness**: SkillSwap's hybrid algorithm guarantees that new listings receive initial top-fold baseline impressions (cold-start mitigation) while systematically rewarding creators who maintain high responsiveness (>95% response rate) and verified client ratings. Users still retain full freedom to switch to explicit sort toggles (*Newest*, *Rate: Low to High*, *Rate: High to Low*).
