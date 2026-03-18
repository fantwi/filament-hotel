<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Room;
use App\Models\Booking;

class RoomCalendar extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';
    protected string $view = 'filament.admin.pages.room-calendar';
    protected static ?string $navigationLabel = 'Room Calendar';
    protected static ?string $title = 'Room Availability';

    public $roomFilter = null;
    public $roomTypeFilter = null;

    public function getEvents()
    {
        $query = Booking::with(['guest','room.roomType'])
            ->whereDate('check_out','>=',now()->subMonths(1));

        if ($this->roomTypeFilter) {
            $query->whereHas('room', function ($q) {
                $q->where('room_type_id', $this->roomTypeFilter);
            });
        }

        return $query->get()->map(function ($booking) {

            return [
                'id' => $booking->id,
                'title' => $booking->guest->full_name.' (Room '.$booking->room->room_number.')',
                'start' => $booking->check_in,
                'end' => $booking->check_out,
                'resourceId' => $booking->room_id,
                'color' => match($booking->status) {
                    'pending' => '#f59e0b',
                    'checked_in' => '#22c55e',
                    'checked_out' => '#6b7280',
                    default => '#3b82f6'
                }
            ];

        });

    }

    public function getRooms()
    {
        $query = Room::with('roomType');

        if ($this->roomTypeFilter) {
            $query->where('room_type_id', $this->roomTypeFilter);
        }

        return $query->get()->map(function ($room) {

            return [
                'id' => $room->id,
                'title' => 'Room '.$room->room_number.' ('.$room->roomType->name.')',
            ];

        });
    }

    public function updatedRoomTypeFilter()
    {
        $this->dispatch('refreshCalendar', [
            'rooms' => $this->getRooms(),
            'events' => $this->getEvents(),
        ]);
    }

    public function updatedRoomFilter()
    {
        $this->dispatch('refreshCalendar', $this->getEvents());
    }

    public function getOccupancyHeatmap()
    {
        $rooms = \App\Models\Room::count();

        $start = now()->startOfWeek();
        $end = now()->addWeeks(4);

        $heatmap = [];

        for ($date = $start; $date <= $end; $date->addDay()) {

            $booked = \App\Models\Booking::whereDate('check_in', '<=', $date)
                ->whereDate('check_out', '>', $date)
                ->count();

            $ratio = $rooms > 0 ? $booked / $rooms : 0;

            $color = '#22c55e'; // green

            if ($ratio >= 0.8) {
                $color = '#ef4444'; // red
            } elseif ($ratio >= 0.5) {
                $color = '#f59e0b'; // yellow
            }

            $heatmap[] = [
                'start' => $date->toDateString(),
                'end' => $date->copy()->addDay()->toDateString(),
                'display' => 'background',
                'color' => $color,
            ];
        }

        return $heatmap;
    }

    public function getCalendarEvents()
    {
        return array_merge(
            $this->getEvents()->toArray(),
            $this->getOccupancyHeatmap()
        );
    }
}
