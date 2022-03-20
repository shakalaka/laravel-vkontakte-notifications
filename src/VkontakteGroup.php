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

    /**
     * Vkontakte group constructor.
     *
     * @param null|string $content

     */
    public function __construct(string $content, int $owner_id)
    {
        $this->content($content, $owner_id);
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @return $this
     */
    public function content(string $content, int $owner_id): self
    {
        $this->payload = [
            'message' => $content,
            'from_group' => self::SEND_MESSAGE_FROM_GROUP,
            'owner_id' => $owner_id
        ];

        return $this;
    }

    /**
     * @param null|float|string $content
     *
     * @return static
     */
    public static function create(string $content, int $owner_id): self
    {
        return new static($content, $owner_id);
    }

}
