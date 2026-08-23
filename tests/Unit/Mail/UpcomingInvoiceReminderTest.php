<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\UpcomingInvoiceReminder;
use Tests\TestCase;

class UpcomingInvoiceReminderTest extends TestCase
{
    public function test_html_in_template_values_is_escaped(): void
    {
        $template = (object) [
            'subject' => 'Reminder for {{name}}',
            'body' => 'Hello {{name}}',
        ];
        $data = ['name' => '<script>alert(1)</script>'];

        $mail = new UpcomingInvoiceReminder($data, $template);
        $method = (new \ReflectionMethod($mail, 'parseTemplate'));

        $parsed = $method->invoke($mail, $template->body);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $parsed);
        $this->assertStringContainsString('&lt;script&gt;', $parsed);
    }
}
