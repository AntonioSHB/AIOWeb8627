<?php

namespace App\Mail;

use App\Models\Configuracion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorreoAltaUsuariosMailable extends Mailable
{
    use Queueable, SerializesModels;
    
    protected $datos;
    protected $asunto;    
    protected $imagen;
    /**
     * Create a new message instance.
     */
    public function __construct($datos, $asunto)
    {        
        $this->datos = $datos;
        $this->asunto = $asunto;
        $this->imagen = Configuracion::obtenerValorPorParametro('url_logo_dp');        
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asunto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {   
        if ($this->datos[0] == '1') {            
            return new Content(
                view: 'home.aplicaciones.gestionUsuarios.mails.CorreoAltaUsuarios',
                with: [
                    'type' => $this->datos[0],
                    'Nombre' => $this->datos[1],
                    'Role' => $this->datos[2],
                    'Usuario' => $this->datos[3],
                    'Contraseña' => $this->datos[4],
                    'urlPortal' => $this->datos[5],
                    'imagen' => $this->imagen
                ]
            );
        }else if ($this->datos[0] == '2') {           
            return new Content(
                view: 'home.aplicaciones.gestionUsuarios.mails.CorreoAltaUsuarios',
                with: [
                    'type' => $this->datos[0],
                    'Nombre' => $this->datos[1],
                    'Role' => $this->datos[2],
                    'Usuario' => $this->datos[3],
                    'Plazas' => $this->datos[4],
                    'TiendaID' => $this->datos[5],
                    'imagen' => $this->imagen
                ]
            );
        }else if ($this->datos[0] == '3') {            
            return new Content(
                view: 'home.aplicaciones.gestionUsuarios.mails.CorreoAltaUsuarios',
                with: [
                    'type' => $this->datos[0],
                    'Nombre' => $this->datos[1],
                    'Role' => $this->datos[2],
                    'Usuario' => $this->datos[3],
                    'Contraseña' => $this->datos[4],
                    'urlPortal' => $this->datos[5],
                    'imagen' => $this->imagen
                ]
            );
        }else if ($this->datos[0] == '4') {            
            return new Content(
                view: 'home.aplicaciones.gestionUsuarios.mails.CorreoAltaUsuarios',
                with: [
                    'type' => $this->datos[0],                    
                    'Usuario' => $this->datos[1],
                    'Contraseña' => $this->datos[2],
                    'urlPortal' => $this->datos[3],
                    'imagen' => $this->imagen
                ]
            );
        }else if ($this->datos[0] == '5') {            
            return new Content(
                view: 'home.aplicaciones.gestionUsuarios.mails.CorreoAltaUsuarios',
                with: [
                    'type' => $this->datos[0],                    
                    'Usuario' => $this->datos[1],
                    'CodPlaza' => $this->datos[2],
                    'TiendaID' => $this->datos[3],
                    'Oficina' => $this->datos[4],
                    'TiendaOVTA' => $this->datos[5],
                    'imagen' => $this->imagen
                ]
            );
        }else if ($this->datos[0] == '6') {            
            return new Content(
                view: 'home.aplicaciones.gestionUsuarios.mails.CorreoAltaUsuarios',
                with: [
                    'type' => $this->datos[0],                    
                    'Usuario' => $this->datos[1],
                    'Contraseña' => $this->datos[2],
                    'urlPortal' => $this->datos[3],
                    'imagen' => $this->imagen
                ]
            );
        }
                
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
