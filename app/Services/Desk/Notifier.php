<?php

namespace App\Services\Desk;

use App\Jobs\SendTemplateEmail;
use App\Models\Employee;
use App\Models\Notification;
use App\Support\Departments;

/**
 * Writes the same notification rows the current office already reads,
 * and queues a template email. Phase 3 can listen for DeskEvent as well.
 */
class Notifier
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function email(string $template, string $to, array $data, string $fallbackSubject): void
    {
        if ($to === '') {
            return;
        }

        SendTemplateEmail::dispatch($template, $to, $data, $fallbackSubject);
    }

    public function staff(string $action, string $nodeType, string $nodeUrl, string $message, int $fromCustomerId): void
    {
        if (config('legacy.read_only')) {
            return;
        }

        $staff = Employee::query()
            ->whereIn('employee_group', [Employee::GROUP_ADMIN, Employee::GROUP_SUB_ADMIN])
            ->where('is_banned', 0)
            ->get();

        foreach ($staff as $employee) {
            $this->write(
                toDepartment: (string) Departments::SUPPORT,
                toUserId: (int) $employee->employee_id,
                fromDepartment: (string) Departments::CUSTOMER,
                fromUserId: $fromCustomerId,
                action: $action,
                nodeType: $nodeType,
                nodeUrl: $nodeUrl,
                message: $message,
            );
            $employee->live_notifications_counter = (int) $employee->live_notifications_counter + 1;
            $employee->save();
        }
    }

    public function customer(int $customerId, string $action, string $nodeType, string $nodeUrl, string $message): void
    {
        if (config('legacy.read_only')) {
            return;
        }

        $this->write(
            toDepartment: (string) Departments::CUSTOMER,
            toUserId: $customerId,
            fromDepartment: (string) Departments::SUPPORT,
            fromUserId: 0,
            action: $action,
            nodeType: $nodeType,
            nodeUrl: $nodeUrl,
            message: $message,
        );
    }

    private function write(
        string $toDepartment,
        int $toUserId,
        string $fromDepartment,
        int $fromUserId,
        string $action,
        string $nodeType,
        string $nodeUrl,
        string $message,
    ): void {
        $note = new Notification;
        $note->to_department = $toDepartment;
        $note->to_user_id = $toUserId;
        $note->from_department = $fromDepartment;
        $note->from_user_id = $fromUserId;
        $note->action = $action;
        $note->node_type = $nodeType;
        $note->node_url = $nodeUrl;
        $note->message = $message;
        $note->is_seen = 0;
        $note->date_add = now();
        $note->date_upd = now();
        $note->save();
    }
}
