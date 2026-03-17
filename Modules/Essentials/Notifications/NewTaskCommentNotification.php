<?php

namespace Modules\Essentials\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTaskCommentNotification extends Notification
{
    use Queueable;

    protected $comment;

    protected $link;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($comment, $link = null)
    {
        $this->comment = $comment;
        $this->link = $link;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        if (isPusherEnabled()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', 'https://laravel.com')
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'comment_id' => $this->comment->id,
            'link' => $this->link,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        $link = ! empty($this->link)
            ? $this->link
            : action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'show'], $this->comment->task->id);

        return new BroadcastMessage([
            'title' => __('essentials::lang.new_comment'),
            'body' => strip_tags(__('essentials::lang.new_task_comment_notification', ['added_by' => $this->comment->added_by->user_full_name, 'task_id' => $this->comment->task->task_id])),
            'link' => $link,
        ]);
    }
}
