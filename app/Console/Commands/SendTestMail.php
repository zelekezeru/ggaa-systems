<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\StaffBroadcastMail;

class SendTestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:send-test {email : The email address to send the test message to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify SMTP and mailer configuration settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $this->info("Attempting to send test email to: {$email}");

        try {
            Mail::to($email)->send(
                new StaffBroadcastMail(
                    'GGAA Email Delivery Test',
                    "Hello,\n\nThis is a test email sent from the GGAA application to verify that the SMTP/email server settings are correctly configured.\n\nIf you received this message, your mail configuration is working perfectly!",
                    'GGAA Test User'
                )
            );
            $this->info('Success: Test email sent successfully!');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: Failed to send test email.');
            $this->line($e->getMessage());
            return self::FAILURE;
        }
    }
}
