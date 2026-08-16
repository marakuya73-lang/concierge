<?php

namespace App\Controller\Admin;

use App\Service\BookingLifecycleService;
use App\Service\DashboardService;
use App\Service\GoogleCalendarSyncService;
use App\Service\IcalSyncService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractAdminController
{
    public function __construct(
        private IcalSyncService $icalSyncService,
        private GoogleCalendarSyncService $googleCalendarSyncService,
    ) {
    }

    #[Route('', name: 'admin_dashboard')]
    public function index(Request $request, DashboardService $dashboardService, BookingLifecycleService $bookingLifecycleService): Response
    {
        $bookingLifecycleService->markPastBookingsCompleted();

        $today = new \DateTimeImmutable('today');
        $year = max(2020, min(2035, (int) $request->query->get('year', $today->format('Y'))));
        $month = max(1, min(12, (int) $request->query->get('month', $today->format('n'))));

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $dashboardService->getStats($year, $month),
        ]);
    }

    #[Route('/sync-ical', name: 'admin_sync_ical', methods: ['POST'])]
    public function syncIcal(Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $result = $this->icalSyncService->sync();

        if (isset($result['message'])) {
            $this->addFlash('error', $result['message']);
        } else {
            $parts = [
                sprintf('%d novas', $result['created'] ?? 0),
                sprintf('%d actualizadas', $result['updated'] ?? 0),
                sprintf('%d Site (calendário Airbnb)', $result['siteBookings'] ?? 0),
                sprintf('%d concluídas', $result['completed'] ?? 0),
                sprintf('%d canceladas', $result['cancelled'] ?? 0),
            ];
            $this->addFlash('success', 'Airbnb sincronizado: '.implode(', ', $parts).'.');
        }

        $redirect = $request->request->get('_redirect', 'admin_dashboard');
        if (!\in_array($redirect, ['admin_dashboard', 'admin_bookings'], true)) {
            $redirect = 'admin_dashboard';
        }

        $calendarYear = (int) $request->request->get('_calendar_year', 0);
        $calendarMonth = (int) $request->request->get('_calendar_month', 0);
        $calendarParams = ($calendarYear >= 2020 && $calendarMonth >= 1 && $calendarMonth <= 12)
            ? ['year' => $calendarYear, 'month' => $calendarMonth]
            : [];

        return $this->redirectToRoute($redirect, $calendarParams);
    }

    #[Route('/sync-google-calendar', name: 'admin_sync_google_calendar', methods: ['POST'])]
    public function syncGoogleCalendar(Request $request): Response
    {
        $this->validateAdminCsrf($request);

        try {
            $result = $this->googleCalendarSyncService->sync();
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Falha ao sincronizar Google Calendar: '.$exception->getMessage());

            return $this->redirectAfterCalendarSync($request);
        }

        if (isset($result['message'])) {
            $this->addFlash('error', $result['message']);
        } else {
            $parts = [
                sprintf('%d actualizadas do Google', $result['updatedFromGoogle'] ?? 0),
                sprintf('%d estadias enviadas', $result['pushed'] ?? 0),
                sprintf('%d terapias enviadas', $result['therapyPushed'] ?? 0),
            ];
            if (($result['therapyConflicts'] ?? 0) > 0) {
                $parts[] = sprintf('%d conflitos de terapia', $result['therapyConflicts']);
            }
            $this->addFlash('success', 'Google Calendar sincronizado: '.implode(', ', $parts).'.');
        }

        return $this->redirectAfterCalendarSync($request);
    }

    private function redirectAfterCalendarSync(Request $request): Response
    {
        $redirect = $request->request->get('_redirect', 'admin_dashboard');
        if (!\in_array($redirect, ['admin_dashboard', 'admin_bookings'], true)) {
            $redirect = 'admin_dashboard';
        }

        $calendarYear = (int) $request->request->get('_calendar_year', 0);
        $calendarMonth = (int) $request->request->get('_calendar_month', 0);
        $calendarParams = ($calendarYear >= 2020 && $calendarMonth >= 1 && $calendarMonth <= 12)
            ? ['year' => $calendarYear, 'month' => $calendarMonth]
            : [];

        return $this->redirectToRoute($redirect, $calendarParams);
    }
}
