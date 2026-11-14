<?php

namespace App\Support;

/**
 * Customer-facing status titles and descriptions from the current site
 * (prox-globals.php orderStatuses), and the order-list tabs from
 * Api\Orders::getOrders() / getUnpaidOrders().
 */
class OrderStatuses
{
    public const TITLES = [
        0 => 'Waiting for payment',
        1 => 'Dispute',
        2 => 'Confirmation Pending',
        6 => 'Writer Assigned',
        7 => 'Writer Accepted',
        8 => 'Done',
        9 => 'Canceled',
        10 => 'Finished',
        11 => 'Delivered',
        12 => 'New Paid',
        13 => 'Free Inquiry',
        14 => 'Revision',
        15 => 'Reply Pending',
        16 => 'Inquiry Published',
        17 => 'Inquiry Writer Accepted',
        19 => 'Looking for writer',
        20 => 'Convert Failed',
        21 => 'Call Pending',
    ];

    public const DESCRIPTIONS = [
        0 => 'Your order is unpaid. Please check your email and follow the tips to complete the payment procedure.',
        6 => 'Your order has been assigned to a writer. Please, check your page from time to time for updates.',
        7 => 'Your order is being processed. The most suitable writer will be assigned shortly. We will notify you of any changes.',
        8 => 'Your order has been assigned to a writer. Please, check your page from time to time for updates.',
        9 => 'Your order has been cancelled. If you want us to proceed working on it, please, contact the Support Team.',
        10 => 'Your order has been approved.',
        11 => 'Your order was completed and delivered. Please check the preview version on the Files List.',
        12 => 'We have already started to search for the writer to complete your order. Please, check whether you have provided all necessary instructions.',
        13 => 'Your inquiry is unpaid. Please wait - the Support Team will contact you shortly.',
        14 => 'You will be informed once the assignment is ready.',
        19 => 'We have already started to search for the writer to complete your order. Please, check whether you have provided all necessary instructions.',
    ];

    /**
     * Main path shown on the order page. Inquiry, dispute, and cancelled
     * sit beside it rather than inside it.
     *
     * @var array<int, string>
     */
    public const FLOW = [
        0 => 'Waiting for payment',
        12 => 'New Paid',
        19 => 'Looking for writer',
        6 => 'Writer Assigned',
        7 => 'Writer Accepted',
        8 => 'Done',
        11 => 'Delivered',
        14 => 'Revision',
        10 => 'Finished',
    ];

    /**
     * Tabs on the customer order list. "recent" matches getOrders(type=recent).
     * "unpaid" matches getUnpaidOrders(), which is status 0 only.
     *
     * @var array<string, string>
     */
    public const TABS = [
        'recent' => 'Recent',
        'unpaid' => 'Unpaid',
        'finished' => 'Finished',
        'cancelled' => 'Canceled',
    ];

    /** @var list<int> */
    public const RECENT = [0, 12, 19, 2, 6, 7, 13, 8, 11, 14, 15, 16];

    public static function title(int $status): string
    {
        return self::TITLES[$status] ?? 'Status '.$status;
    }

    public static function description(int $status): string
    {
        return self::DESCRIPTIONS[$status] ?? '';
    }

    public static function serviceTitle(int $type): string
    {
        return match ($type) {
            37 => 'Academic Writing',
            42 => 'Paid Revision (Minor)',
            43 => 'Paid Revision (Major)',
            49 => 'Resume',
            50 => 'Calculations',
            51 => 'Programming',
            52 => 'Article Writing',
            default => 'Order',
        };
    }
}
