<?php

namespace NotificationChannels\Vkontakte;

use Illuminate\Support\Facades\View;
use JsonSerializable;
use NotificationChannels\Vkontakte\Traits\HasSharedLogic;

/**
 * Class VkontakteMessage.
 */
class VkontakteMessage implements JsonSerializable
{
    use HasSharedLogic;

    public function __construct(string $content = '')
    {
        $this->content($content);
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @return $this
     */
    public function content(string $content): self
    {
        $this->payload['text'] = $content;

        return $this;
    }


    /**
     * Attach a view file as the content for the notification.
     * Supports Laravel blade template.
     *
     * @return $this
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->content(View::make($view, $data, $mergeData)->render());
    }
}
