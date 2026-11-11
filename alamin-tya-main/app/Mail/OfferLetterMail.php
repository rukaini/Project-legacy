<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OfferLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $applicant;
    public $application_id;
    public $offerDate;

    /**
     * Create a new message instance.
     */
    public function __construct($applicant, $application_id, $offerDate)
    {
        $this->applicant = $applicant;
        $this->application_id = $application_id;
        $this->offerDate = $offerDate;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Offer Letter from Al Amin HR - ' . $this->applicant->full_name)
                    ->markdown('emails.offer-letter')
                    ->with([
                        'applicant' => $this->applicant,
                        'application_id' => $this->application_id,
                        'offerDate' => $this->offerDate,
                    ]);
    }
}