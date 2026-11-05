<?php

namespace App\Services\Office;

use App\Models\Employee;

/**
 * Role mapping from prox_employee.employee_group, which is how the current
 * office already distinguishes people:
 * 1 admin, 2 editor, 3 writer, 4 sub-admin, 5 applicant (no office access).
 *
 * Admin always has every permission. The matrix edits the other roles and
 * is stored in app_role_permissions. Until a role is saved, the defaults
 * below apply.
 */
class Permissions
{
    public const CATALOG = [
        'orders.view_all' => 'See every order',
        'orders.view_review' => 'See the editor review queue',
        'orders.view_own' => 'See orders assigned to me',
        'orders.assign' => 'Assign and reassign writers and editors',
        'orders.bids' => 'Approve a writer bid',
        'bids.place' => 'Bid on orders in the pool',
        'orders.deadlines' => 'Change customer and writer deadlines',
        'orders.cancel' => 'Cancel an order',
        'orders.refund' => 'Cancel or refund within the sub-admin limit',
        'files.download' => 'Download order files',
        'files.upload' => 'Upload drafts and finals',
        'files.approve' => 'Approve or return a file in review',
        'messages.staff' => 'Message support, writers, and editors',
        'messages.customer' => 'Message the customer',
        'customers.view' => 'Open the customer list',
        'customers.pii' => 'See customer name, email, and phone',
        'fines.manage' => 'Add fines and credits',
        'payouts.request' => 'Request a payout',
        'payouts.approve' => 'Approve payout batches',
        'people.manage' => 'Suspend staff and move writers between pools',
        'roles.manage' => 'Edit the permission matrix',
        'settings.manage' => 'Change office settings and the rate card',
        'gateways.view' => 'See payment gateway status',
        'reports.view' => 'Open reports and export CSV',
        'news.manage' => 'Publish announcements',
        'news.view' => 'Read announcements',
        'audit.view' => 'Read the activity log',
    ];

    public const DEFAULTS = [
        Employee::GROUP_SUB_ADMIN => [
            'orders.view_all', 'orders.assign', 'orders.bids', 'orders.deadlines',
            'orders.cancel', 'orders.refund', 'files.download', 'files.upload',
            'messages.staff', 'messages.customer', 'customers.view', 'customers.pii',
            'fines.manage', 'reports.view', 'news.view',
        ],
        Employee::GROUP_EDITOR => [
            'orders.view_review', 'files.download', 'files.upload', 'files.approve',
            'messages.staff', 'news.view',
        ],
        Employee::GROUP_WRITER => [
            'orders.view_own', 'bids.place', 'files.download', 'files.upload',
            'messages.staff', 'payouts.request', 'news.view',
        ],
    ];

    public static function roleName(int $group): string
    {
        return match ($group) {
            Employee::GROUP_ADMIN => 'Admin',
            Employee::GROUP_SUB_ADMIN => 'Sub-admin',
            Employee::GROUP_EDITOR => 'Editor',
            Employee::GROUP_WRITER => 'Writer',
            5 => 'Applicant',
            default => 'Staff',
        };
    }
}
