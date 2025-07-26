<?php

namespace App\Notifications;

use App\Models\AccreditationRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccreditationRequestReturned extends Notification implements ShouldQueue
{
    use Queueable;
    
    // Configuración para la cola
    public $tries = 3;           // Máximo 3 intentos
    public $maxExceptions = 3;   // Máximo 3 excepciones
    public $timeout = 60;       // 1 minuto timeout para envíos de correo

    protected $accreditationRequest;
    protected $returnedBy;
    protected $reason;

    /**
     * Create a new notification instance.
     *
     * @param AccreditationRequest $accreditationRequest
     * @param User $returnedBy
     * @param string $reason
     * @return void
     */
    public function __construct(AccreditationRequest $accreditationRequest, User $returnedBy, string $reason)
    {
        $this->accreditationRequest = $accreditationRequest;
        $this->returnedBy = $returnedBy;
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
        // Cargar todas las relaciones necesarias si no están cargadas
        if (!$this->accreditationRequest->relationLoaded('employee')) {
            $this->accreditationRequest->load('employee');
        }

        if (!$this->accreditationRequest->relationLoaded('event')) {
            $this->accreditationRequest->load('event');
        }

        $employeeName = $this->accreditationRequest->employee->first_name . ' ' . 
                        $this->accreditationRequest->employee->last_name;
        $eventName = $this->accreditationRequest->event->name;
        
        // URL para ver la solicitud
        $url = route('accreditation-requests.edit', $this->accreditationRequest->uuid);

        return (new MailMessage)
            ->subject('Solicitud de acreditación devuelta para corrección')
            ->greeting('Hola ' . $notifiable->name)
            ->line('La solicitud de acreditación para **' . $employeeName . '** en el evento **' . $eventName . '** ha sido devuelta para corrección.')
            ->line('**Motivo de la devolución:**')
            ->line($this->reason)
            ->line('**Devuelta por:** ' . $this->returnedBy->name)
            ->action('Ver Solicitud', $url)
            ->line('Por favor, realiza las correcciones necesarias y vuelve a enviar la solicitud.')
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
            'returned_by' => $this->returnedBy->id,
            'employee_name' => $this->accreditationRequest->employee->first_name . ' ' . 
                               $this->accreditationRequest->employee->last_name,
            'event_name' => $this->accreditationRequest->event->name
        ];
    }
}
