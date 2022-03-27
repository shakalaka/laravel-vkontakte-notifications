<?php

namespace NotificationChannels\Vkontakte\Traits;

use Illuminate\Support\Traits\Conditionable;
use NotificationChannels\Vkontakte\VkontakteMessage;

/**
 * Trait HasSharedLogic.
 */
trait HasSharedLogic
{
    use Conditionable;

    /** @var string Vk secret. */
    public $secret;

    /** @var array Params payload. */
    protected $payload = [];

    /** @var array Attachments */
    protected $attachments = [];


    /**
     * Recipient's User ID.
     *
     * @param int|string $user_id
     *
     * @return $this
     */
    public function to($user_id): self
    {
        $this->payload['user_id'] = $user_id;

        return $this;
    }

    /**
     * Send the message silently.
     * Users will receive a notification with no sound.
     *
     * @return $this
     */
    public function disableNotification(bool $disableNotification = true): self
    {
        $this->payload['disable_notification'] = $disableNotification;

        return $this;
    }

    /**
     * Vk Secret.
     * Overrides default vk secret with the given value for this notification.
     *
     * @return $this
     */
    public function secret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Determine if vk secret is given for this notification.
     */
    public function hasSecret(): bool
    {
        return null !== $this->secret;
    }

    /**
     * Additional options to pass to sendMessage method.
     *
     * @return $this
     */
    public function options(array $options): self
    {
        $this->payload = array_merge($this->payload, $options);

        return $this;
    }

    /**
     * Determine if chat id is not given.
     */
    public function toNotGiven(): bool
    {
        return !isset($this->payload['user_id']);
    }

    /**
     * Get payload value for given key.
     *
     * @return null|mixed
     */
    public function getPayloadValue(string $key)
    {
        return $this->payload[$key] ?? null;
    }

    /**
     * Returns params payload.
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }


    /**
     * Add File to Message.
     *
     * Generic method to attach files of any type based on API.
     *
     * @param resource|StreamInterface|string $file
     *
     * @return $this
     */
    public function attachments($file, string $filename = null): self
    {
        $this->attachments[] = is_resource($file) ? $file : fopen($file, 'rb');
        $this->payload['attachments'] = $this->attachments;

        return $this;
    }
}
