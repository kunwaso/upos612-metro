<?php

namespace Modules\VasAccounting\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalStep;

class ExpenseApprovalEscalatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected FinanceDocument $document,
        protected FinanceApprovalStep $approvalStep,
        protected array $approvalInsight = [],
        protected ?string $reason = null
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        if (function_exists('isPusherEnabled') && isPusherEnabled()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload();
    }

    public function toArray($notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => __('vasaccounting::lang.notifications.expense_approval_escalated.title', [
                'document' => $this->documentReference(),
            ]),
            'body' => __('vasaccounting::lang.notifications.expense_approval_escalated.body', [
                'role' => data_get($this->approvalInsight, 'escalation_role_label')
                    ?: data_get($this->approvalInsight, 'current_step_role_label')
                    ?: __('vasaccounting::lang.notifications.expense_approval_escalated.fallback_role'),
            ]),
            'link' => $this->workspaceLink(),
        ]);
    }

    protected function payload(): array
    {
        return [
            'title' => __('vasaccounting::lang.notifications.expense_approval_escalated.title', [
                'document' => $this->documentReference(),
            ]),
            'body' => __('vasaccounting::lang.notifications.expense_approval_escalated.body', [
                'role' => data_get($this->approvalInsight, 'escalation_role_label')
                    ?: data_get($this->approvalInsight, 'current_step_role_label')
                    ?: __('vasaccounting::lang.notifications.expense_approval_escalated.fallback_role'),
            ]),
            'reason' => $this->reason,
            'document_id' => $this->document->id,
            'document_no' => $this->document->document_no,
            'document_type' => $this->document->document_type,
            'approval_step_no' => $this->approvalStep->step_no,
            'escalation_role' => data_get($this->approvalInsight, 'escalation_role'),
            'escalation_role_label' => data_get($this->approvalInsight, 'escalation_role_label'),
            'link' => $this->workspaceLink(),
        ];
    }

    protected function workspaceLink(): string
    {
        return route('vasaccounting.expenses.index', array_filter([
            'location_id' => $this->document->business_location_id,
            'focus' => 'escalated_approvals',
        ])) . '#expense-register';
    }

    protected function documentReference(): string
    {
        return $this->document->document_no ?: ('#' . $this->document->id);
    }
}
