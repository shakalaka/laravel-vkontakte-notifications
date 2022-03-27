<?php

namespace NotificationChannels\Vkontakte;

use JsonSerializable;
use NotificationChannels\Vkontakte\Traits\HasSharedLogic;

/**
 * Class VkontakteGroup.
 */
class VkontakteGroup implements JsonSerializable
{
    use HasSharedLogic;

    /**
     * Send message from group name flag
     */
    private const SEND_MESSAGE_FROM_GROUP = 1;
    public const METHOD = 'wall.post';

    /**
     * Vkontakte group constructor.
     *
     * @param null|string $content

     */
    public function __construct(string $content)
    {
        $this->content($content);
    }

    public function group(int $group): self {
        $this->payload['owner_id'] = $group;
        return $this;
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @return $this
     */
    public function content(string $content): self
    {
        $this->payload = [
            'message' => $content,
            'from_group' => self::SEND_MESSAGE_FROM_GROUP,
        ];

        return $this;
    }
    

    /**
     * @param null|float|string $content
     *
     * @return static
     */
    public static function create(string $content = ''): self
    {
        return new static($content);
    }
}
