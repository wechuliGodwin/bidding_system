<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidder;
use Illuminate\Http\Request;

class BidderController extends Controller
{
    public function index()
    {
        $bidders = Bidder::with('user')->latest()->paginate(15);
        return view('admin.bidders.index', compact('bidders'));
    }

    public function show(Bidder $bidder)
    {
        $bidder->load('documents', 'user');
        return view('admin.bidders.show', compact('bidder'));
    }

    public function approve(Bidder $bidder)
    {
        $bidder->update(['status' => 'approved']);
        return redirect()->back()->with('success', 'Bidder approved successfully.');
    }

    public function reject(Bidder $bidder)
    {
        $bidder->update(['status' => 'rejected']);
        return redirect()->back()->with('success', 'Bidder rejected.');
    }
}