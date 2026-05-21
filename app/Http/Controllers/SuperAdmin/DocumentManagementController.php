<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DocumentType;
use App\Models\FirmDocument;
use App\Models\Shelf;
use App\Models\ShelfSection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class DocumentManagementController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $documents = FirmDocument::with(['documentType', 'client', 'shelfSection.shelf'])
            ->latest()
            ->get();

        $documentTypes = DocumentType::all();
        $shelves = Shelf::with('sections.documents')->get();
        $clients = Client::all(['id', 'company_name', 'tin_number']);

        return Inertia::render('SuperAdmin/Documents', [
            'documents' => $documents,
            'documentTypes' => $documentTypes,
            'shelves' => $shelves,
            'clients' => $clients,
        ]);
    }

    public function storeType(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name',
            'description' => 'nullable|string',
        ]);

        DocumentType::create($validated);

        return back()->with('success', 'Document type added successfully.');
    }

    public function storeShelf(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'label' => 'required|string|max:255|unique:shelves,label',
            'total_rows' => 'required|integer|min:1|max:10',
            'total_columns' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string',
        ]);

        $shelf = Shelf::create([
            'label' => $validated['label'],
            'total_rows' => $validated['total_rows'],
            'total_columns' => $validated['total_columns'],
            'description' => $validated['description'],
            'qr_code' => 'SHLF-' . strtoupper(Str::random(10)),
        ]);

        // Generate the shelf sections in a grid layout (Rows x Columns)
        for ($r = 1; $r <= $validated['total_rows']; $r++) {
            for ($c = 1; $c <= $validated['total_columns']; $c++) {
                ShelfSection::create([
                    'shelf_id' => $shelf->id,
                    'row' => $r,
                    'column' => $c,
                    'label' => "{$shelf->label} - R{$r}-C{$c}",
                    'qr_code' => "SECT-{$shelf->id}-R{$r}C{$c}-" . strtoupper(Str::random(6)),
                ]);
            }
        }

        return back()->with('success', "Shelf {$shelf->label} with {$shelf->sections()->count()} grid sections created.");
    }

    public function updateShelf(Request $request, Shelf $shelf)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'label' => 'required|string|max:255|unique:shelves,label,' . $shelf->id,
            'alternative_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $shelf->update($validated);

        return back()->with('success', 'Shelf updated successfully.');
    }

    public function storeDocument(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'client_id' => 'required|exists:clients,id',
            'grace_days' => 'required|integer|min:0',
            'charge_per_day' => 'required|numeric|min:0',
            'received_at' => 'required|date',
            'notes' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'file|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $images[] = '/storage/' . $file->store('document_attachments', 'public');
            }
        }

        $doc = FirmDocument::create([
            'title' => $validated['title'],
            'document_type_id' => $validated['document_type_id'],
            'client_id' => $validated['client_id'],
            'grace_days' => $validated['grace_days'],
            'charge_per_day' => $validated['charge_per_day'],
            'notes' => $validated['notes'],
            'image_paths' => $images,
            'status' => 'received',
            'received_at' => $validated['received_at'],
        ]);

        return back()->with('success', "Document {$doc->unique_document_id} registered. QR Code: {$doc->qr_code}");
    }

    public function updateDocument(Request $request, FirmDocument $document)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'client_id' => 'required|exists:clients,id',
            'grace_days' => 'required|integer|min:0',
            'charge_per_day' => 'required|numeric|min:0',
            'received_at' => 'required|date',
            'notes' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'file|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $images = $document->image_paths ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $images[] = '/storage/' . $file->store('document_attachments', 'public');
            }
        }

        $document->update([
            'title' => $validated['title'],
            'document_type_id' => $validated['document_type_id'],
            'client_id' => $validated['client_id'],
            'grace_days' => $validated['grace_days'],
            'charge_per_day' => $validated['charge_per_day'],
            'notes' => $validated['notes'],
            'image_paths' => $images,
            'received_at' => $validated['received_at'],
        ]);

        return back()->with('success', "Document {$document->unique_document_id} updated successfully.");
    }

    /**
     * Scan and Place Placement Flow
     * Staff scans the Document QR first, then scans the Shelf Section QR step-by-step
     */
    public function placeDocument(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager', 'Employee']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'document_qr' => 'required|string',
            'section_qr' => 'required|string',
        ]);

        $document = FirmDocument::where('qr_code', $validated['document_qr'])->first();
        if (!$document) {
            return back()->with('error', 'Invalid Document QR Code scanned.');
        }

        $section = ShelfSection::where('qr_code', $validated['section_qr'])->first();
        if (!$section) {
            return back()->with('error', 'Invalid Shelf Section QR Code scanned.');
        }

        $document->update([
            'shelf_section_id' => $section->id,
            'status' => 'placed',
            'placed_at' => now(),
        ]);

        return back()->with('success', "Document {$document->unique_document_id} successfully shelved at {$section->label}.");
    }

    public function retrieveDocument(Request $request, FirmDocument $document)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager', 'Employee']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'retrieval_voucher' => 'required|string|max:255',
            'retrieval_notes' => 'nullable|string',
            'process_penalty_payment' => 'nullable|boolean',
        ]);

        // Process penalty payments in the existing payment process if checked and charge > 0
        if (($validated['process_penalty_payment'] ?? false) && $document->accumulated_charge > 0) {
            // Generate ServiceInvoice
            $invoice = \App\Models\ServiceInvoice::create([
                'invoice_number'    => \App\Models\ServiceInvoice::generateInvoiceNumber(),
                'client_id'         => $document->client_id,
                'period_start'      => $document->received_at,
                'period_end'        => now(),
                'amount'            => $document->accumulated_charge,
                'description'       => "Storage delay surcharge penalty for physical document #{$document->unique_document_id} ({$document->title}). Stayed {$document->duration_of_stay} days (allowed grace of {$document->grace_days} days).",
                'due_date'          => now()->addDays(7),
                'services_snapshot' => [
                    [
                        'name' => 'Storage Delay Surcharge',
                        'price' => $document->accumulated_charge,
                        'description' => "Delay penalty charge for {$document->unique_document_id}"
                    ]
                ],
                'status'            => 'sent', // Auto-approve and send
                'issued_at'         => now(),
                'created_by'        => auth()->id(),
            ]);

            // Auto-generate ServiceInvoicePayment matching this surcharge!
            \App\Models\ServiceInvoicePayment::create([
                'service_invoice_id' => $invoice->id,
                'amount'             => $document->accumulated_charge,
                'payment_method'     => 'Cash',
                'reference'          => $validated['retrieval_voucher'],
                'status'             => 'Completed', // Auto-recorded as paid via voucher settlement!
                'paid_at'            => now(),
                'recorded_by'        => auth()->id(),
                'approved_by'        => auth()->id(),
                'notes'              => 'Processed automatically upon physical document checkout. Voucher: ' . $validated['retrieval_voucher'],
            ]);
        }

        $document->update([
            'shelf_section_id' => null,
            'status' => 'retrieved',
            'retrieved_at' => now(),
            'retrieval_voucher' => $validated['retrieval_voucher'],
            'retrieval_notes' => $validated['retrieval_notes'],
        ]);

        return back()->with('success', "Document {$document->unique_document_id} marked as retrieved. Voucher logged successfully.");
    }
}
