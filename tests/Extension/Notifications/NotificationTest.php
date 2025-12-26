<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Notifications;

use WeprestaAcf\Extension\Notifications\Notification;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    public function testCreateSimpleNotification(): void
    {
        $notification = new Notification(
            recipients: ['user@example.com'],
            subject: 'Test Subject',
            content: 'Test Content'
        );

        $this->assertEquals(['user@example.com'], $notification->getRecipients());
        $this->assertEquals('Test Subject', $notification->getSubject());
        $this->assertEquals('Test Content', $notification->getContent());
        $this->assertEquals(['email'], $notification->getChannels());
    }

    public function testToHelperMethod(): void
    {
        $notification = Notification::to(
            'single@example.com',
            'Subject',
            'Content'
        );

        $this->assertEquals(['single@example.com'], $notification->getRecipients());
    }

    public function testToManyHelperMethod(): void
    {
        $notification = Notification::toMany(
            ['user1@example.com', 'user2@example.com'],
            'Subject',
            'Content'
        );

        $this->assertCount(2, $notification->getRecipients());
    }

    public function testWithData(): void
    {
        $data = ['order_id' => 123, 'customer_name' => 'John'];

        $notification = new Notification(
            recipients: ['user@example.com'],
            subject: 'Order Confirmation',
            content: 'Thank you for your order!',
            data: $data
        );

        $this->assertEquals($data, $notification->getData());
    }

    public function testWithMultipleChannels(): void
    {
        $notification = new Notification(
            recipients: ['user@example.com', '+33612345678'],
            subject: 'Urgent',
            content: 'Please respond!',
            channels: ['email', 'sms']
        );

        $this->assertEquals(['email', 'sms'], $notification->getChannels());
    }

    public function testDefaultChannelIsEmail(): void
    {
        $notification = Notification::to('user@example.com', 'Subject', 'Content');

        $this->assertEquals(['email'], $notification->getChannels());
    }
}

