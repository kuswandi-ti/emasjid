<?php

namespace App\Mail;

use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MosqueApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mosque $mosque,
        public Carbon $approvalDate,
        public string $invitationCode,
    ) {}

    /**
     * Build the message.
     */
    public function build(): static
    {
        return $this->subject('Your Mosque Has Been Approved')
            ->view('emails.mosque-approved');
    }
}
