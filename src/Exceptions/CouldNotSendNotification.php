<?php

namespace NotificationChannels\Vkontakte\Exceptions;

use Exception;
use GuzzleHttp\Exception\ClientException;

/**
 * Class CouldNotSendNotification.
 */
class CouldNotSendNotification extends Exception
{
    /**
     * Thrown when there's a bad request and an error is responded.
     *
     * @return static
     */
    public static function vkontakteRespondedWithAnError(ClientException $exception): self
    {
        if (!$exception->hasResponse()) {
            return new static('Vkontakte responded with an error but no response body found');
        }

        $statusCode = $exception->getResponse()->getStatusCode();

        $result = json_decode($exception->getResponse()->getBody()->getContents(), false);
        $description = $result->description ?? 'no description given';

        return new static("Vkontakte responded with an error `{$statusCode} - {$description}`", 0, $exception);
    }

    /**
     * Thrown when there's no bot token provided.
     *
     * @return static
     */
    public static function vkontakteSecretNotProvided(string $message): self
    {
        return new static($message);
    }

    /**
     * Thrown when we're unable to communicate with Vkontakte.
     *
     * @param $message
     *
     * @return static
     */
    public static function couldNotConnect($message): self
    {
        return new static("Connection failed. `{$message}`");
    }
}
