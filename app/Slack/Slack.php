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
        $webhookUrl = 'https://hooks.slack.com/services/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';
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
