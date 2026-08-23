<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Jobs\SendEmailNotification;
use App\Services\EmailNotificationService;
use App\Services\SmsService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms.api_key' => 'test-key',
            'services.sms.from_number' => '+15550001111',
            'services.sms.base_url' => 'https://sms.example.test',
            'services.sms.enabled' => true,
        ]);
    }

    // --- SmsService ---

    public function test_sms_sends_with_formatted_recipient_and_returns_true(): void
    {
        Http::fake([
            '*' => Http::response(['id' => 'abc'], 200),
        ]);

        $result = (new SmsService)->send('(415) 555-2671', 'Hello there');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sms.example.test/messages'
                && $request['to'] === '+14155552671'
                && $request['from'] === '+15550001111'
                && $request['message'] === 'Hello there'
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_sms_keeps_non_us_numbers_with_country_code(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        (new SmsService)->send('+44 20 7946 0958', 'Hi');

        Http::assertSent(fn ($request) => $request['to'] === '+442079460958');
    }

    public function test_sms_returns_false_on_failed_response(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $this->assertFalse((new SmsService)->send('4155552671', 'x'));
    }

    public function test_sms_no_ops_when_disabled(): void
    {
        config(['services.sms.enabled' => false]);
        Http::fake();

        $this->assertFalse((new SmsService)->send('4155552671', 'x'));

        Http::assertNothingSent();
    }

    public function test_sms_no_ops_when_phone_number_is_empty(): void
    {
        Http::fake();

        $this->assertFalse((new SmsService)->send('', 'x'));
        $this->assertFalse((new SmsService)->send(null, 'x'));

        Http::assertNothingSent();
    }

    // --- EmailNotificationService ---

    public function test_send_queues_job_with_recipient_and_mailable(): void
    {
        Queue::fake();

        $result = (new EmailNotificationService)->send(new StubNotificationMailable, 'user@example.test');

        $this->assertTrue($result);

        Queue::assertPushed(SendEmailNotification::class, function ($job) {
            return $this->jobProperty($job, 'recipient') === 'user@example.test'
                && $this->jobProperty($job, 'mailable') instanceof StubNotificationMailable;
        });
    }

    public function test_send_now_sends_mailable_to_recipient(): void
    {
        Mail::fake();

        $result = (new EmailNotificationService)->sendNow(new StubNotificationMailable, 'user@example.test');

        $this->assertTrue($result);

        Mail::assertSent(StubNotificationMailable::class, function ($mailable) {
            return $mailable->hasTo('user@example.test');
        });
    }

    private function jobProperty(object $job, string $name): mixed
    {
        $ref = new \ReflectionProperty($job, $name);
        $ref->setAccessible(true);

        return $ref->getValue($job);
    }
}

class StubNotificationMailable extends Mailable
{
    public function build(): self
    {
        return $this->subject('Stub')->html('<p>stub</p>');
    }
}
