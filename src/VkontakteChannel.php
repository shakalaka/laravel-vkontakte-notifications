<?php

namespace NotificationChannels\Vkontakte;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use NotificationChannels\Vkontakte\Exceptions\CouldNotSendNotification;

/**
 * Class VkontakteChannel.
 */
class VkontakteChannel
{
    /**
     * @var Vkontakte
     */
    protected $vkontakte;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * Channel constructor.
     */
    public function __construct(Vkontakte $vkontakte, Dispatcher $dispatcher)
    {
        $this->vkontakte = $vkontakte;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     *
     * @throws CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification): ?array
    {
        $message = $notification->toVkontakte($notifiable);

        if (is_string($message)) {
            $message = VkontakteMessage::create($message);
        }

        if ($message->toNotGiven()) {
            $to = $notifiable->routeNotificationFor('vkontakte', $notification)
                ?? $notifiable->routeNotificationFor(self::class, $notification);

            if (!$to) {
                return null;
            }

            $message->to($to);
        }

        if ($message->hasSecret()) {
            $this->vkontakte->setSecret($message->secret);
        }

        $params = $message->toArray();

        $sendMethod = str_replace('Vkontakte', 'send', array_reverse(explode('\\', get_class($message)))[0]);

        try {
            if ($message instanceof VkontakteMessage) {
                $response = $this->vkontakte->sendMessage($params);
            } elseif (method_exists($this->vkontakte, $sendMethod)) {
                $response = $this->vkontakte->{$sendMethod}($params);
            } else {
                return null;
            }
        } catch (CouldNotSendNotification $exception) {
            $this->dispatcher->dispatch(new NotificationFailed(
                $notifiable,
                $notification,
                'vkontakte',
                []
            ));

            throw $exception;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
