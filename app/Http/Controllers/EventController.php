<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
                'to' => $request->input('to'),
                'location' => $request->input('location'),
            ],
            'statuses' => ['draft', 'published', 'cancelled', 'sold_out'],
        ]);
    }

    public function visualOne(Request $request): Response
    {
        return Inertia::render('Events/VisualOne', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
                'to' => $request->input('to'),
                'location' => $request->input('location'),
            ],
            'statuses' => ['draft', 'published', 'cancelled', 'sold_out'],
        ]);
    }

    public function visualTwo(Request $request): Response
    {
        $now = Carbon::now();
        $defaultFrom = $now->startOfMonth()->toDateString();
        $defaultTo = $now->endOfMonth()->toDateString();

        return Inertia::render('Events/VisualTwo', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', $defaultFrom),
                'to' => $request->input('to', $defaultTo),
                'location' => $request->input('location'),
            ],
            'statuses' => ['draft', 'published', 'cancelled', 'sold_out'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$events, $stats] = $this->loadListing($request);

        return response()->json([
            'data' => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => $stats,
        ]);
    }

    public function show(Event $event): Response
    {
        $event->load('user');

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: array{ms: int, bytes: int}}
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);

        $events = Event::with('user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from, function ($q, $from) {
                try {
                    $timestamp = Carbon::parse($from)->startOfDay()->timestamp;

                    return $q->where('starts_at', '>=', $timestamp);
                } catch (\Exception $e) {
                    return $q;
                }
            })
            ->when($request->to, function ($q, $to) {
                try {
                    $timestamp = Carbon::parse($to)->endOfDay()->timestamp;

                    return $q->where('starts_at', '<=', $timestamp);
                } catch (\Exception $e) {
                    return $q;
                }
            })
            ->when($request->location, function ($q, $loc) {
                return $q->where(fn ($query) => $query
                    ->where('city', 'like', "%{$loc}%")
                    ->orWhere('country', 'like', "%{$loc}%")
                    ->orWhere('address', 'like', "%{$loc}%")
                );
            })
            ->orderByDesc('created_time')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen((string) json_encode($events->items())),
        ];

        return [$events, $stats];
    }
}
