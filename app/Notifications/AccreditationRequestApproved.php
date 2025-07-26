<?php

namespace App\Notifications;

use App\Models\AccreditationRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccreditationRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    // Configuración para la cola
    public $tries = 3;           // Máximo 3 intentos
    public $maxExceptions = 3;   // Máximo 3 excepciones
    public $timeout = 60;        // 1 minuto timeout para envíos de correo

    protected $accreditationRequest;
    protected $approvedBy;
    protected $comment;

    /**
     * Create a new notification instance.
     * 
     * @param \App\Models\AccreditationRequest $accreditationRequest
     * @param \App\Models\User $approvedBy
     * @param string|null $comment
     * @return void
     */
    public function __construct(AccreditationRequest $accreditationRequest, User $approvedBy, ?string $comment = null)
    {
        $this->accreditationRequest = $accreditationRequest;
        $this->approvedBy = $approvedBy;
        $this->comment = $comment;
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
        
        $message = (new MailMessage)
            ->subject('Solicitud de acreditación aprobada')
            ->greeting('Hola ' . $notifiable->name)
            ->line('La solicitud de acreditación para **' . $employeeName . '** en el evento **' . $eventName . '** ha sido aprobada.')
            ->line('**Aprobada por:** ' . $this->approvedBy->name);
        
        if (!empty($this->comment)) {
            $message->line('**Comentario:**')
                   ->line($this->comment);
        }
        
        $message->line('La credencial está siendo generada y pronto estará disponible para descarga.')
               ->action('Ver Detalles', $url)
               ->salutation('Atentamente, Credenciales FVF');
        
        return $message;
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
            'comment' => $this->comment,
            'approved_by' => $this->approvedBy->id,
            'employee_name' => $this->accreditationRequest->employee->first_name . ' ' . 
                              $this->accreditationRequest->employee->last_name,
            'event_name' => $this->accreditationRequest->event->name
        ];
    }
}
