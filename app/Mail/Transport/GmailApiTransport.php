<?php

namespace App\Mail\Transport;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class GmailApiTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->refreshToken(config('services.google.refresh_token'));

        $service = new Gmail($client);

        // Convert email to base64url encoded raw MIME string
        $rawMessage = rtrim(strtr(base64_encode($email->toString()), '+/', '-_'), '=');

        $gmailMessage = new Message();
        $gmailMessage->setRaw($rawMessage);

        $service->users_messages->send('me', $gmailMessage);
    }

    public function __toString(): string
    {
        return 'gmail_api';
    }
}
