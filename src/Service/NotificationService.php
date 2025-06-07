<?php

namespace App\Service;

// Importation des classes nécessaires
use App\Entity\Booking;
use App\Entity\User;
use App\Entity\Event;
use App\Entity\OrganizerRequest;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    // Envoie un email de bienvenue à un nouvel utilisateur après son inscription
    public function sendRegistration(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('contact@tonsite.fr', 'Nostrae Events'))
            ->to($user->getEmail())
            ->subject('Bienvenue sur Nostrae !')
            ->htmlTemplate('emails/registration.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    // Envoie un email de confirmation de réservation à l'utilisateur
    // et une notification à l'organisateur si différent
    public function sendReservation(Booking $booking): void
    {
        $event = $booking->getEvent();
        $user = $booking->getUser();
        $organizer = $event->getCreatedBy();

        // Email pour l'utilisateur
        $userEmail = (new TemplatedEmail())
            ->from('contact@tonsite.fr')
            ->to($user->getEmail())
            ->subject('Réservation confirmée')
            ->htmlTemplate('emails/reservation_user.html.twig')
            ->context(compact('booking', 'user', 'event'));

        $this->mailer->send($userEmail);

        // Email pour l’organisateur si ce n’est pas lui qui a réservé
        if ($organizer && $organizer->getEmail() !== $user->getEmail()) {
            $orgEmail = (new TemplatedEmail())
                ->from('contact@tonsite.fr')
                ->to($organizer->getEmail())
                ->subject('Nouvelle réservation pour votre événement')
                ->htmlTemplate('emails/reservation_organizer.html.twig')
                ->context(compact('booking', 'organizer', 'event'));

            $this->mailer->send($orgEmail);
        }
    }

    // Envoie un email d’annulation à l’utilisateur et au créateur de l’événement
    public function sendCancellation(Booking $booking): void
    {
        $event = $booking->getEvent();
        $user = $booking->getUser();
        $organizer = $event->getCreatedBy();

        // Email à l’utilisateur
        $userEmail = (new TemplatedEmail())
            ->from('contact@tonsite.fr')
            ->to($user->getEmail())
            ->subject('Réservation annulée')
            ->htmlTemplate('emails/cancellation_user.html.twig')
            ->context(compact('booking', 'user', 'event'));

        $this->mailer->send($userEmail);

        // Email à l’organisateur si ce n’est pas lui-même qui annule
        if ($organizer && $organizer->getEmail() !== $user->getEmail()) {
            $orgEmail = (new TemplatedEmail())
                ->from('contact@tonsite.fr')
                ->to($organizer->getEmail())
                ->subject('Une réservation a été annulée')
                ->htmlTemplate('emails/cancellation_organizer.html.twig')
                ->context(compact('booking', 'organizer', 'event'));

            $this->mailer->send($orgEmail);
        }
    }

    // Envoie un email de rappel à un utilisateur pour un événement imminent
    public function sendEventReminder(User $user, Event $event): void
    {
        $email = (new Email())
            ->from('contact@tonsite.fr') // Correction : propriété non déclarée `$this->fromAddress` remplacée
            ->to($user->getEmail())
            ->subject('🎉 Rappel : votre événement commence bientôt !')
            ->html("
                <h2>Bonjour {$user->getUsername()} !</h2>
                <p>Petit rappel : l'événement <strong>{$event->getTitle()}</strong> commence le 
                <strong>{$event->getStartAt()->format('d/m/Y à H:i')}</strong>.</p>
                <p>Lieu : {$event->getLocation()->getName()}, {$event->getLocation()->getCity()}</p>
                <p>À bientôt !</p>
            ");

        $this->mailer->send($email);
    }

    // Envoie une alerte à l’administrateur lorsqu’une nouvelle demande d’organisateur est soumise
    public function notifyAdminNewRequest(OrganizerRequest $request): void
    {
        $adminEmail = 'admin@tonsite.com'; // Adresse à personnaliser dans les paramètres si nécessaire

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@tonsite.com', 'Nostrae'))
            ->to($adminEmail)
            ->subject('Nouvelle demande d\'organisateur')
            ->htmlTemplate('emails/admin_new_request.html.twig')
            ->context([
                'user' => $request->getUser(),
                'request' => $request,
            ]);

        $this->mailer->send($email);
    }

    // Confirme à l’utilisateur que sa demande d’organisateur a bien été envoyée
    public function notifyUserRequestSent(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@tonsite.com', 'Nostrae'))
            ->to($user->getEmail())
            ->subject('Votre demande d\'organisateur a été envoyée')
            ->htmlTemplate('emails/user_request_sent.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }

    // Informe l’utilisateur du résultat (accepté/refusé) de sa demande d’organisateur
    public function notifyUserRequestStatus(User $user, string $status): void
    {
        $subject = $status === 'accepted'
            ? 'Votre demande d\'organisateur a été acceptée 🎉'
            : 'Votre demande d\'organisateur a été refusée ❌';

        $template = $status === 'accepted'
            ? 'emails/user_request_accepted.html.twig'
            : 'emails/user_request_refused.html.twig';

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@tonsite.com', 'Nostrae'))
            ->to($user->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
