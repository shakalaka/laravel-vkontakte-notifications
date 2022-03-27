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
    private const DEFAULT_VERSION_API = '5.131';
    /** @var Client Guzzle client */
    protected $client;

    /** @var null|string Vkontakte secret key. */
    protected $secret;

    /** @var string Vkontakte API Base URI */
    protected $baseUrl;

    /** @var string Vkontakte API Version */
    protected $version;

    public function __construct(string $secret = null, Client $client = null, string $baseUri = null)
    {
        $this->secret = $secret;
        $this->client = $client ?? new Client();
        $this->setBaseUri($baseUri ?? self::DEFAULT_BASE_URI);
        $this->setVersion(self::DEFAULT_VERSION_API);
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
     * Set version VK api
     *
     * @param string $version
     *
     * @return $this
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

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

    protected function prepareAttachments(array $params): array
    {
        $apiUri = sprintf('%s/method/photos.getWallUploadServer?group_id=%s&access_token=%s&v=%s', $this->baseUri, $params['owner_id'], $this->secret, $this->version);
        $result = json_decode($this->client->get($apiUri)->getBody()->getContents());
        if (!empty($result->response->upload_url)) {
            $upload = json_decode($this->client->post($result->response->upload_url, [
                'photo' => $params['attachments'][0],
            ])->getBody()->getContents());
            if (!empty($upload->server)) {
                $uploadUrl = sprintf(
                    '%s/method/photos.saveWallPhoto?group_id=%s&server=%s&photo=%s&hash=%s&access_token=%s&v=%s',
                    $this->baseUri,
                    ltrim($params['owner_id'], '-'),
                    $upload->server,
                    stripslashes($upload->photo),
                    $upload->hash,
                    $this->secret,
                    $this->version
                );

                $save = json_decode($this->client->get($uploadUrl)->getBody()->getContents());

            }
        }

        return $params;
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

        $apiUri = sprintf('%s/method/%s?', $this->baseUri, $endpoint);

        $params['access_token'] = $this->secret;
        $params['v'] = $this->version;
        try {
            $params = $this->prepareAttachments($params);
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
