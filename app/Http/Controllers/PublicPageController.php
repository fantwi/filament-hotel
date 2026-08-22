<?php

namespace App\Http\Controllers;

use App\Models\ConferenceRoom;
use App\Models\ContactMessage;
use App\Models\Restaurant;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Coordinates the public page controller HTTP workflow.
 */
class PublicPageController extends Controller
{
    /**
     * Handles the home HTTP action.
     */
    public function home(): View
    {
        $roomTypes = RoomType::query()
            ->published()
            ->with(['facilities' => fn ($query) => $query->published()])
            ->take(3)
            ->get();
        $conferenceRooms = ConferenceRoom::query()
            ->published()
            ->with(['facilities' => fn ($query) => $query->published()])
            ->where('is_available', true)
            ->take(3)
            ->get();
        $restaurant = Restaurant::published()->first();

        return view('index', compact('roomTypes', 'conferenceRooms', 'restaurant'));
    }

    /**
     * Handles the contact HTTP action.
     */
    public function contact(): View
    {
        return view('contact');
    }

    /**
     * Handles the store contact HTTP action.
     */
    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create($validated);

        return back()->with('success', 'Message sent successfully.');
    }
}
