<?php
require 'vendor/autoload.php';

use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

$apiKey = 'af208a7a8de618f5aa0a283f474f8c2f'; // Assuming the MAIL_PASSWORD is the API Key

try {
    $email = (new MailtrapEmail())
        ->from(new Address('admin@matchgrinder.com', 'MatchGrinder Admin'))
        ->to(new Address('danielbillion@gmail.com'))
        ->subject('Mailtrap API Test')
        ->text('This is a test email sent via the Mailtrap API to bypass SMTP port restrictions.')
    ;

    $response = MailtrapClient::initSendingEmails(
        apiKey: $apiKey
    )->send($email);

    echo "Response:\n";
    print_r(ResponseHelper::toArray($response));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
