<?php

namespace App\Notifications;

use App\Models\AccreditationRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccreditationRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    // Configuración para la cola
    public $tries = 3;           // Máximo 3 intentos
    public $maxExceptions = 3;   // Máximo 3 excepciones
    public $timeout = 60;        // 1 minuto timeout para envíos de correo

    protected $accreditationRequest;
    protected $rejectedBy;
    protected $reason;

    /**
     * Create a new notification instance.
     * 
     * @param \App\Models\AccreditationRequest $accreditationRequest
     * @param \App\Models\User $rejectedBy
     * @param string $reason
     * @return void
     */
    public function __construct(AccreditationRequest $accreditationRequest, User $rejectedBy, string $reason)
    {
        $this->accreditationRequest = $accreditationRequest;
        $this->rejectedBy = $rejectedBy;
        $this->reason = $reason;
        $this->onQueue('emails'); // Cola dedicada para correos
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $employeeName = $this->accreditationRequest->employee->first_name . ' ' . 
                       $this->accreditationRequest->employee->last_name . ' ' . 
                       $this->accreditationRequest->employee->id;
        
        $eventName = $this->accreditationRequest->event->name;
        
        $url = url('/accreditation-requests/' . $this->accreditationRequest->uuid);
        
        return (new MailMessage)
            ->subject('Solicitud de acreditación rechazada')
            ->markdown('mail.accreditation-request.rejected')
            ->greeting('Hola ' . $notifiable->name)
            ->line('La solicitud de acreditación para **' . $employeeName . '** en el evento **' . $eventName . '** ha sido rechazada.')
            ->line('**Motivo del rechazo:**')
            ->line($this->reason)
            ->line('**Rechazada por:** ' . $this->rejectedBy->name)
            ->action('Ver Detalles', $url)
            ->line('Esta solicitud ha sido rechazada definitivamente y no podrá ser procesada.')
            ->salutation('Atentamente, Credenciales FVF');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'accreditation_request_id' => $this->accreditationRequest->id,
            'accreditation_request_uuid' => $this->accreditationRequest->uuid,
            'reason' => $this->reason,
            'rejected_by' => $this->rejectedBy->id,
            'employee_name' => $this->accreditationRequest->employee->first_name . ' ' . 
                              $this->accreditationRequest->employee->last_name,
            'event_name' => $this->accreditationRequest->event->name
        ];
    }
}
