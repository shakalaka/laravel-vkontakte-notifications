<?php

namespace NotificationChannels\Vkontakte;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use NotificationChannels\Vkontakte\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Vkontakte.
 */
class Vkontakte
{
    private const DEFAULT_BASE_URI = 'https://api.vk.com';
    /** @var Client Guzzle client */
    protected $client;

    /** @var null|string Vkontakte secret key. */
    protected $secret;

    /** @var string Vkontakte API Base URI */
    protected $baseUrl;

    public function __construct(string $secret = null, Client $client = null, string $baseUri = null)
    {
        $this->secret = $secret;
        $this->client = $client ?? new Client();
        $this->setBaseUri($baseUri ?? self::DEFAULT_BASE_URI);
    }

    /**
     * Secret getter.
     */
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * Secret setter.
     *
     * @return $this
     */
    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Base URI getter.
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * API Base URI setter.
     *
     * @return $this
     */
    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = rtrim($baseUri, '/');

        return $this;
    }


    /**
     * Get HttpClient.
     */
    protected function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Set HTTP Client.
     *
     * @return $this
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Send text message.
     *
     * @throws CouldNotSendNotification
     */
    public function sendMessage(array $params): ?ResponseInterface
    {
        return $this->sendRequest('sendMessage', $params);
    }
    /**
     * Send text message.
     *
     * @throws CouldNotSendNotification
     */
    public function sendGroup(array $params): ?ResponseInterface
    {
        return $this->sendRequest('wall.post', $params);
    }

    /**
     * Get updates.
     *
     * @throws CouldNotSendNotification
     */
    public function getUpdates(array $params): ?ResponseInterface
    {
        return $this->sendRequest('getUpdates', $params);
    }

    /**
     * Send an API request and return response.
     *
     * @throws CouldNotSendNotification
     */
    protected function sendRequest(string $endpoint, array $params, bool $multipart = false): ?ResponseInterface
    {
        if (blank($this->secret)) {
            throw CouldNotSendNotification::vkontakteSecretNotProvided('You must provide your vk api secret to make any API requests.');
        }

        $apiUri = sprintf('%s/bot%s/%s', $this->baseUri, $this->secret, $endpoint);

        try {
            return $this->client->post($apiUri, [
                $multipart ? 'multipart' : 'form_params' => $params,
            ]);
        } catch (ClientException $exception) {
            throw CouldNotSendNotification::vkontakteRespondedWithAnError($exception);
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotConnect($exception);
        }
    }
}
