<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $greeting, $content,$data)
    {
        $this->subject = $subject;
        $this->data = $data;
        // $this->greeting = $greeting;
        // $this->content = $content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contact Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         // view: 'view.name',
    //         view: 'Mail.AdminContactMail'
    //     );
    // }

  public function build()
    {
        $email = $this->view('Mail.AdminContactMail')
                      ->subject($this->subject)
                      ->attach($this->data['file_url']);

        return $email;
    }
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
