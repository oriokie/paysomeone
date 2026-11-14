<?php

namespace App\Support;

/**
 * Values written into required prox_order columns when a new order does not
 * collect the academic fields the current site asks for.
 *
 * The columns and the lookup tables stay as they are. Existing orders that
 * already store an academic level, a citation format, or a subject still
 * read those values back on the order page.
 *
 * academic_level_id = 0
 *     The column is NOT NULL and has no foreign key. New orders do not ask
 *     for a level, so the row stores 0. The current site's article endpoint
 *     also leaves this property unset.
 *
 * paper_format_id = 4, paper_format_option = "Not applicable"
 *     createOrder() in the current site falls back to paper format 4 when
 *     the customer does not choose a citation style. 4 is that fallback.
 *     The option text records that no citation style was requested.
 *
 * topic_category_id = 52, topic_category_option = "Professional"
 *     Discipline 52 is the current site's free-text "Other" subject
 *     (createOrder() and createArticlesOrder() both special-case it).
 *     New orders do not ask for a subject, so they use that id and a
 *     neutral label.
 *
 * spacing = double
 *     The column default, and the current site's default when spacing is
 *     not part of the article form.
 *
 * order_type
 *     Article orders store "article". Page and programming orders store
 *     "academic", which is the table default the current page form leaves
 *     in place. The customer never sees this column. The label on screen
 *     comes from the service they picked.
 */
class OrderDefaults
{
    public const ACADEMIC_LEVEL_ID = 0;

    public const PAPER_FORMAT_ID = 4;

    public const PAPER_FORMAT_OPTION = 'Not applicable';

    public const TOPIC_CATEGORY_ID = 52;

    public const TOPIC_CATEGORY_OPTION = 'Professional';

    public const SPACING = 'double';
}
