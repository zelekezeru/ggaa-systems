<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Dynamic Document Types list (e.g. Box Files, Receipts, Pads, Letters, etc.)
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Physical Shelves
        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique()->comment('Shelf ID label, e.g. Shelf A, Shelf B');
            $table->integer('total_rows')->default(5);
            $table->integer('total_columns')->default(5);
            $table->string('qr_code')->unique()->comment('Generated unique QR token for Shelf');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Grid Sections in a Shelf (row & column alignment)
        Schema::create('shelf_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_id')->constrained()->cascadeOnDelete();
            $table->integer('row');
            $table->integer('column');
            $table->string('label')->comment('E.g. Shelf A - R1-C2');
            $table->string('qr_code')->unique()->comment('Unique QR code for this specific grid block');
            $table->timestamps();

            $table->unique(['shelf_id', 'row', 'column']);
        });

        // 4. Firm physical documents tracking
        Schema::create('firm_documents', function (Blueprint $table) {
            $table->id();
            $table->string('unique_document_id')->unique()->comment('Dynamic doc identifier, e.g. DOC-202605-0001');
            $table->string('qr_code')->unique()->comment('Pre-printed or system generated unique document QR');
            $table->string('title');
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            
            // Placement in shelves
            $table->foreignId('shelf_section_id')->nullable()->constrained('shelf_sections')->nullOnDelete();
            $table->enum('status', ['received', 'placed', 'retrieved', 'archived'])->default('received');
            
            // Timestamps for audit
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('retrieved_at')->nullable();

            // Delay & storage charging specifications
            $table->timestamp('delay_charge_starts_at')->nullable();
            $table->integer('grace_days')->default(30)->comment('Days before storage delay fee kicks in');
            $table->decimal('charge_per_day', 10, 2)->default(50.00)->comment('Charge in local currency per day after delay_charge_starts_at');
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_documents');
        Schema::dropIfExists('shelf_sections');
        Schema::dropIfExists('shelves');
        Schema::dropIfExists('document_types');
    }
};
