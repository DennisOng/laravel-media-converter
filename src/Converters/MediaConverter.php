<?php

namespace Meema\MediaConvert\Converters;

use Aws\Credentials\Credentials;
use Aws\MediaConvert\MediaConvertClient;
use Meema\MediaConvert\Contracts\Converter;

class MediaConverter implements Converter
{
    /**
     * Client instance of MediaConvert.
     *
     * @var \Aws\MediaConvert\MediaConvertClient
     */
    protected MediaConvertClient $client;

    /**
     * Construct converter.
     *
     * @param \Aws\MediaConvert\MediaConvertClient $client
     */
    public function __construct(MediaConvertClient $client)
    {
        $result = $client->describeEndpoints([]);
        $config = config('media-convert');

        $this->client = new MediaConvertClient([
            'version' => $config['version'],
            'region' => $config['region'],
            'credentials' => new Credentials($config['credentials']['key'], $config['credentials']['secret']),
            'endpoint' => $result['Endpoints'][0]['Url'],
        ]);
    }

    /**
     * Get the Polly Client.
     *
     * @return \Aws\MediaConvert\MediaConvertClient
     */
    public function getClient(): MediaConvertClient
    {
        return $this->client;
    }

    /**
     * Cancels an active job.
     *
     * @param string $id
     * @return \Aws\Result
     */
    public function cancelJob(string $id)
    {
        return $this->client->cancelJob([
            'Id' => $id,
        ]);
    }

    /**
     * Creates a new job based on the settings passed.
     *
     * @param array $settings
     * @param int $mediaId
     * @param array $tags
     * @param int $priority
     * @return \Aws\Result
     */
    public function createJob(array $settings, int $mediaId, $tags = [], $priority = 0)
    {
        $interval = 'SECONDS_60'; // gracefully default to this value, in case the config value is missing or incorrect
        $webhookInterval = config('media-convert.webhook_interval');
        $allowedValues = [10, 12, 15, 20, 30, 60, 120, 180, 240, 300, 360, 420, 480, 540, 600];

        if (in_array($webhookInterval, [$allowedValues])) {
            $interval = 'SECONDS_'.$webhookInterval;
        }

        return $this->client->createJob([
            'Role' => config('media-convert.iam_arn'),
            'Settings' => $settings,
            'Queue' => config('media-convert.queue_arn'),
            'UserMetadata' => [
                'Customer' => 'Amazon',
            ],
            'StatusUpdateInterval' => $interval,
            'Priority' => $priority,
            'Tags' => array_merge([
                'model_id' => $mediaId,
            ], $tags),
        ]);
    }

    /**
     * Gets the job.
     *
     * @param string $id
     * @return \Aws\Result
     */
    public function getJob(string $id)
    {
        return $this->client->getJob([
            'Id' => $id,
        ]);
    }

    /**
     * Lists all of the jobs based on your options provided.
     *
     * @param array $options
     * @return \Aws\Result
     */
    public function listJobs(array $options)
    {
        return $this->client->listJobs($options);
    }
}
