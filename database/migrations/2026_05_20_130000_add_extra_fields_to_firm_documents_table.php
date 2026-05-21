<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firm_documents', function (Blueprint $table) {
            $table->string('retrieval_voucher')->nullable()->after('retrieved_at')->comment('Reference voucher number on return');
            $table->text('retrieval_notes')->nullable()->after('retrieval_voucher')->comment('Notes/receiver details on return');
            $table->json('image_paths')->nullable()->after('notes')->comment('Paths of uploaded scan/photo attachments');
        });
    }

    public function down(): void
    {
        Schema::table('firm_documents', function (Blueprint $table) {
            $table->dropColumn(['retrieval_voucher', 'retrieval_notes', 'image_paths']);
        });
    }
};
