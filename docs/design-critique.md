# Why the order page and desk feel cheap

Reviewed from the current desktop and mobile screens of `/order`, `/account`, and an order detail page (new and legacy). The public marketing pages are out of scope.

## Density

The order form is one long stacked sheet. Service, title, keywords, deadline, writer, English, audience, tone, style, feel, and coupon sit in a single scroll with the same vertical rhythm. Nothing is allowed to breathe, so every choice feels equally urgent. On the desk, the overview is three equal stat cards and a table, then a large empty region. The empty space is not calm; it is unused, because the content above is packed into a narrow left column under a heavy sidebar.

## Alignment

Form rows use a fixed label column that does not line up with the card padding once the viewport drops below the two-column grid. Radio options inside those rows wrap to different heights, so the right edge of the form is ragged. On the order detail page the definition list, the status chips, and the tab underline do not share a left edge with the title. The mobile bottom bar cuts through the tab row and the detail card, which makes the screen look unfinished rather than layered.

## Spacing

Spacing jumps between 8, 12, 16, and 24 pixels without a rule. Section gaps inside the form are the same as gaps between a label and its input. Cards on the overview use one padding; the table header uses another; the legacy “recorded on the previous form” block uses a third. There is no 4/8 grid you can feel.

## Hierarchy

The page title, the section labels, and the field labels are all dark and similar in weight. “Place an order” does not lead. The price sits in a dark card that shouts louder than the decision in front of the user. Status is a small pill, a sentence, a warning banner, and a full row of every possible status at once. The eye has nowhere to land.

## Inputs

Inputs are default browser controls with a thin border and a small radius. They are short, so they do not feel tappable. Labels sit in a side column instead of above the field. There is no helper text, no character count, and no inline error until the server rejects the post. Focus is a generic outline. Selects for deadline and document type look like a template form, not a product.

## Borders

Almost every region is wrapped in a 1px border and a shadow. The form is a bordered card, each choice is a bordered card, the summary is a bordered card, and the order detail stacks more bordered cards under a bordered table. Borders plus shadows plus the dark summary make the UI feel boxed in. A premium surface uses a hairline or a shadow, not both at full strength.

## Rhythm

The order page has no steps, so there is no sense of progress or of a short decision. The desk has no top bar, no search, and no single primary action in a consistent place. Tables have no relative time, no row hover that suggests a click, and no empty state beyond a sentence. Messages, files, and payment are tabs on one page but they do not look like different tools; they look like more of the same card.

## What this pass has to fix

A checkout with five short steps and a sticky summary. Large service cards, deadline tiles, a word stepper, and extra toggles with prices. A desk shell with a slim sidebar, a top bar, and pages that each have one job: overview, orders, a single order, payment, wallet, settings. Same fields, statuses, and data. Quieter surfaces.

## Resolved in this pass

The order flow is five short steps with a sticky summary (a bottom sheet on a phone). Choices are cards, tiles, and chips. Inputs are 48px with the label above. The desk is a navy sidebar, a top bar, and separate pages for overview, orders, messages, a single order, payment, wallet, and settings. Surfaces use a soft shadow without a heavy border. Status stays a tinted pill. The same fields, extras, coupons, statuses, and tabs are still there.
