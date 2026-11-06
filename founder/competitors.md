# Competitors: PoriFyx Widget

Researched 2026-11-06 from public pages. Most prices come from third-party pricing guides that disagree with each other. Check each vendor's own pricing page before quoting it. Raw rows: `competitors.csv`.

## 1. Who is there (most direct first)

| Name | Type | Comparable price (per month) | Model |
|---|---|---|---|
| Chatbase | Direct | about $40 / $150 / $500 | Message credits; a reply costs 2 to 6 credits |
| Tidio + Lyro | Direct | $29 to $59 base, plus Lyro from $39 for 50 AI chats | AI sold as a separate add-on |
| Botsonic | Direct | $16 to $41 | About 1,000 messages |
| SiteGPT | Direct | Base not confirmed; $39 add-on to remove branding | Not confirmed |
| CustomGPT | Direct | About $99 | Plan-based |
| Same Codester script | Direct | One-off script price (not checked) | Anyone can buy and resell it |
| Intercom Fin | Indirect | $0.99 per resolution, plus $29 to $39 per seat | Pay per outcome |
| tawk.to | Indirect | Free live chat; AI reportedly $29 after 100 messages | Freemium |
| Contact form, email, WhatsApp button | Substitute | Free | Staff answer later |
| FAQ page + human live chat | Substitute | Staff time | People answer during working hours |

## 2. Price range for an entry AI plan

- Lowest: about $16/mo (Botsonic).
- Median: about $40/mo (Chatbase Hobby; Tidio Starter plus the Lyro add-on lands near $68).
- Highest for small businesses: about $99/mo (CustomGPT). Intercom becomes the most expensive once there is volume.
- PoriFyx Starter at 29 (currency not confirmed) sits at the low end. Pro at 79 sits above the median.

## 3. Positioning map

The axes are cost predictability (how sure the owner is of the bill) and breadth (AI only, or AI plus inbox, leads and bookings).

```
              broad (AI + inbox + leads + bookings)
                        |
      Intercom Fin      |   PoriFyx (planned)
      Tidio + Lyro      |
  unpredictable --------+-------- predictable bill
      Chatbase          |   Botsonic, CustomGPT
                        |   tawk.to (free chat)
              narrow (AI answers only)
```

Placement is a judgement from the pricing models above, not measured data.

## 4. What their customers complain about

1. **Bills that are hard to predict.** This is the strongest theme.
   - Tidio: "expensive" appears in 76 reviews and "too expensive" in 56, according to one review analysis ([Kayako](https://kayako.com/tools/tidio-review/)).
   - Tidio: reviewers report "automatic plan upgrades during traffic spikes without consent" ([getmacha](https://www.getmacha.com/blog/tidio-review)).
   - Chatbase: "Message credits are not messages", and a reply costs 2 to 6 credits ([myaskai](https://myaskai.com/blog/chatbase-complete-guide-2026)).
   - Chatbase: when credits run out, the bot stops responding unless auto-recharge is on ([same source](https://myaskai.com/blog/chatbase-complete-guide-2026)).
2. **AI sold as an extra.** Tidio's Lyro is "not included in any base plan" ([eesel](https://www.eesel.ai/blog/tidio-ai-reviews)). tawk.to's AI is a paid add-on on top of free chat ([getmacha](https://www.getmacha.com/blog/tawk-to-review-and-alternatives)).
3. **Confident wrong answers.** Chatbase hallucinations are "mentioned in 52 reviews" ([Tooliverse](https://tooliverse.ai/tools/chatbase)). One reviewer said "not working accurately" ([AppSumo](https://appsumo.com/products/chatbase/reviews/isnt-working-correctly-no-support-251372/)).
4. **Slow support and billing disputes** (thin evidence, from secondhand summaries). Chatbase on Trustpilot ([fi.trustpilot](https://fi.trustpilot.com/review/chatbase.co)); Tidio renewal problems ([eesel](https://www.eesel.ai/blog/tidio-reviews)).

Caveat: many of these summaries are written by competitors or affiliates. I did not read the Trustpilot or G2 pages directly.

## 5. The gap

**"One flat monthly price with AI included, no credits and no surprise upgrades. When the AI is unsure, it says so and hands over."**

This is tied to complaints 1 to 3. PoriFyx already has the pieces:
- AI included in every plan, with hard limits instead of automatic upgrades.
- A confidence threshold with a fallback reply.
- Strict mode to keep answers on-topic.
- Human handoff.
- Bookings inside the chat.

None of the direct rivals above bundle bookings into the chat widget at this price.

The gap holds only if two things are true:
- AI costs per busy customer stay under the plan price. `/founder-cfo` checks this.
- The limits are shown clearly and the bot keeps working, with a "leave a message" form, when the limits run out.

The board's "no single customer" risk still stands. The gap is stronger for a niche that books appointments (clinics, salons, trades) than for shops in general.

## 6. The threat

- **Same Codester script.** It is the fastest copy. Any buyer can launch the same base product within days. The defence is everything built since (bookings, flows, automations, 133 languages, workspaces), plus brand and niche focus.
- **Tidio.** It could bundle Lyro into its base plans at any time. That would close the "AI as an extra" gap for SMBs.
- **tawk.to.** Free chat plus cheap AI pulls in price-sensitive owners.

Next: `/founder-consumer` turns these complaints into buyer objections, and `/founder-pricing` uses the price range.
