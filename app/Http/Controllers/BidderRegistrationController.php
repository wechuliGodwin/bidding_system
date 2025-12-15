<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Bidder;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BidderRegistrationController extends Controller
{
    public function create()
    {
        // Check if user already has a bidder profile
        if (Auth::user() && Auth::user()->bidder) {
            return redirect()->route('bidder.dashboard')
                ->with('info', 'You have already registered as a bidder.');
        }

        return view('bidder.register');
    }

    /**
     * Store a newly created bidder registration
     */
    public function store(Request $request)
    {
        // Ensure authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to register as a bidder.');
        }

        // Check if user already has a bidder profile
        if (Auth::user()->bidder) {
            return redirect()->route('bidder.dashboard')
                ->with('error', 'You have already registered as a bidder.');
        }

        // Validate the request
        $validated = $request->validate([
            'bidder_type' => 'required|in:individual,company',
            'company_name' => 'required_if:bidder_type,company|nullable|string|max:255',
            'registration_number' => 'required_if:bidder_type,company|nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'documents' => 'required|array|min:1',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ], [
            'documents.required' => 'Please upload at least one document.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
            'documents.*.max' => 'Each document must not exceed 5MB.',
        ]);

        try {
            DB::beginTransaction();

            // Create bidder profile
            $bidder = Bidder::create([
                'user_id' => Auth::id(),
                'bidder_type' => $validated['bidder_type'],
                'company_name' => $validated['company_name'] ?? null,
                'registration_number' => $validated['registration_number'] ?? null,
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'status' => 'pending',
            ]);

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $file) {
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();

                    // Create unique filename
                    $filename = 'doc_' . time() . '_' . $index . '.' . $extension;

                    // Store file in storage/app/public/documents
                    $path = $file->storeAs('documents', $filename, 'public');

                    // Create document record
                    Document::create([
                        'bidder_id' => $bidder->id,
                        'document_type' => $this->determineDocumentType($originalName, $index),
                        'file_path' => $path,
                    ]);
                }
            }

            DB::commit();

            // Log the registration
            Log::info('New bidder registration', [
                'bidder_id' => $bidder->id,
                'user_id' => Auth::id(),
                'bidder_type' => $validated['bidder_type']
            ]);

            return redirect()->route('bidder.dashboard')
                ->with('success', 'Registration submitted successfully! Your application is pending admin approval.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bidder registration failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }
    }

    /**
     * Determine document type based on filename
     */
    private function determineDocumentType($filename, $index)
    {
        $filename = strtolower($filename);

        if (strpos($filename, 'id') !== false || strpos($filename, 'identity') !== false) {
            return 'ID Document';
        } elseif (strpos($filename, 'certificate') !== false || strpos($filename, 'cert') !== false) {
            return 'Certificate';
        } elseif (strpos($filename, 'registration') !== false) {
            return 'Registration Document';
        } elseif (strpos($filename, 'license') !== false) {
            return 'License';
        } else {
            return 'Document ' . ($index + 1);
        }
    }

    /**
     * Show bidder status
     */
    public function status()
    {
        $bidder = Auth::user()->bidder;

        if (!$bidder) {
            return redirect()->route('bidder.register')
                ->with('info', 'Please complete your registration first.');
        }

        return view('bidder.status', compact('bidder'));
    }

    /**
     * Update bidder information
     */
    public function update(Request $request)
    {
        $bidder = Auth::user()->bidder;

        if (!$bidder) {
            return redirect()->route('bidder.register')
                ->with('error', 'No bidder profile found.');
        }

        if ($bidder->status === 'approved') {
            return back()->with('error', 'Cannot update approved profile. Contact admin for changes.');
        }

        $validated = $request->validate([
            'bidder_type' => 'required|in:individual,company',
            'company_name' => 'required_if:bidder_type,company|nullable|string|max:255',
            'registration_number' => 'required_if:bidder_type,company|nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
        ]);

        try {
            $bidder->update([
                'bidder_type' => $validated['bidder_type'],
                'company_name' => $validated['company_name'] ?? null,
                'registration_number' => $validated['registration_number'] ?? null,
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);

            return back()->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            Log::error('Bidder update failed', [
                'bidder_id' => $bidder->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Update failed. Please try again.');
        }
    }
}
