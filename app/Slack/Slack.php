<?php
namespace App\Slack;

use Maknz\Slack\Client;

/**
 * Class Slack
 *
 * Used to interface with another Slack API library
 *
 * @package App\Slack
 * @property Client $client
 */
class Slack
{
    private $client;

    public function __construct()
    {
        $webhookUrl = Configure::read('slack_webhook_url');
        $this->client = new Client($webhookUrl);
    }

    /**
     * @param string $title Title of event
     * @return void
     */
    public function sendNewEventAlert($title)
    {
        $moderationUrl = 'https://muncieevents.com/moderate';
        $this->client->send(sprintf(
            'New event added: *%s*. <%s|Go to moderation page>',
            $title,
            $moderationUrl
        ));
    }
}
