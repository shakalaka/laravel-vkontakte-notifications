<?php

namespace NotificationChannels\Vkontakte;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use NotificationChannels\Vkontakte\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Psr7;



/**
 * Class Vkontakte.
 */
class Vkontakte
{
    private const DEFAULT_BASE_URI = 'https://api.vk.com';
    private const DEFAULT_VERSION_API = '5.131';
    /** @var Client Guzzle client */
    protected $client;

    /** @var string[] Allowed Mime types  */
    protected $valid_types = ['image/png' => 'png', 'image/jpeg' => 'jpeg']; // add more valid types that you want and so on

    /** @var null|string Vkontakte secret key. */
    protected $secret;

    /** @var string Vkontakte API Base URI */
    protected $baseUri;

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

    protected function getRandomFileName(): string
    {
        return $random = Str::random(20);
    }

    protected function checkAndSaveFile(string $file)
    {
        try {
            $response = $this->client->get($file);
            $headers = $response->getHeaders();
            $contentType = $headers['Content-Type'];
            if (
                !empty($contentType[0]) &&
                array_key_exists($contentType[0], $this->valid_types)
            ) {
                $fileName = $this->getRandomFileName();
                $fileContent = $response->getBody()->getContents();
                $path = sprintf('images/%s.%s', $fileName, $this->valid_types[$contentType[0]]);

                $save = Storage::disk('public')->put(
                    $path,
                    $fileContent
                );

                if ($save) return [
                    'path' => storage_path('app/public/' . $path),
                    'name' => $fileName
                ];

                return false;
            }

        } catch (\Exception $e) {
            echo 'does not exist';
        }
    }

    protected function getAttachmentsFile(array $params): array
    {
        $files = [];
        foreach ($params['attachments'] as $i => $file) {
            $savedFile = $this->checkAndSaveFile($file);
            if ($savedFile) {
                $files[] = [
                    'name' => 'photo',
                    'contents' => Psr7\Utils::tryFopen($savedFile['path'], 'r'),
                ];
            }
        }

        return $files;
    }

    protected function getServerParams(array $params): \stdClass
    {
        $apiUri = sprintf('%s/method/photos.getWallUploadServer?group_id=%s&access_token=%s&v=%s', $this->baseUri, ltrim($params['owner_id'], '-'), $this->secret, $this->version);

        return json_decode($this->client->get($apiUri)->getBody()->getContents());
    }
    
    protected function uploadPhotos(array $params, \stdClass $server): \stdClass
    {
        $body = [
            'multipart' => $this->getAttachmentsFile($params),
        ];

        if (env('APP_DEBUG')) {
            $body['debug'] = true;
        }

        $response = json_decode(
            $this->client->request(
                'POST',
                $server->response->upload_url,
                $body,
            )
            ->getBody()
            ->getContents()
        );

        return $response;
    }

    protected function setLink(array $params): array
    {
        if (isset($params['link']) && $params['link']) {
            $params['attachments'] .= ', ' . $params['link'];
        }

        return $params;
    }

    protected function setAttachments(array $params): array
    {
        if (array_key_exists('attachments', $params) === false || count($params['attachments']) <= 0) {
            return $params;
        }

        $server = $this->getServerParams($params);

        if (!empty($server->response->upload_url)) {
            $upload = $this->uploadPhotos($params, $server);
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

                foreach ($save->response as $response) {
                    $photo[] = sprintf('photo%s_%s', $response->owner_id, $response->id);
                }

                $params['attachments'] = implode(',', $photo);
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
            $params = $this->setAttachments($params);
            $params = $this->setLink($params);
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
