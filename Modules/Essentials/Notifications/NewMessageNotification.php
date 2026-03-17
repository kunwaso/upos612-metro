<?php

namespace Modules\Essentials\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $message;

    protected $link;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($message, $link = null)
    {
        $this->message = $message;
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
        $channels = $this->message->database_notification ? ['database'] : [];
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
            'from' => $this->message->sender->user_full_name,
            'from_id' => $this->message->user_id,
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
            : action([\Modules\Essentials\Http\Controllers\EssentialsMessageController::class, 'index']);

        return new BroadcastMessage([
            'title' => __('essentials::lang.new_message'),
            'body' => strip_tags(__('essentials::lang.new_message_notification', ['sender' => $this->message->sender->user_full_name])),
            'link' => $link,
        ]);
    }
}
