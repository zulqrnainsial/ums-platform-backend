<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'profile_photo_path')) {
                    $table->string('profile_photo_path', 1000)->nullable()->after('email');
                }

                if (!Schema::hasColumn('students', 'profile_photo_uploaded_at')) {
                    $table->timestamp('profile_photo_uploaded_at')->nullable()->after('profile_photo_path');
                }
            });
        }

        if (Schema::hasTable('student_documents')) {
            Schema::table('student_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('student_documents', 'document_title')) {
                    $table->string('document_title', 255)->nullable()->after('student_id');
                }

                if (!Schema::hasColumn('student_documents', 'document_type')) {
                    $table->string('document_type', 100)->nullable()->after('document_title');
                }

                if (!Schema::hasColumn('student_documents', 'file_path')) {
                    $table->string('file_path', 1000)->nullable()->after('document_type');
                }

                if (!Schema::hasColumn('student_documents', 'file_name')) {
                    $table->string('file_name', 255)->nullable()->after('file_path');
                }

                if (!Schema::hasColumn('student_documents', 'mime_type')) {
                    $table->string('mime_type', 150)->nullable()->after('file_name');
                }

                if (!Schema::hasColumn('student_documents', 'file_size')) {
                    $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
                }

                if (!Schema::hasColumn('student_documents', 'uploaded_by_student')) {
                    $table->boolean('uploaded_by_student')->default(false)->after('file_size');
                }

                if (!Schema::hasColumn('student_documents', 'uploaded_at')) {
                    $table->timestamp('uploaded_at')->nullable()->after('uploaded_by_student');
                }

                if (!Schema::hasColumn('student_documents', 'verification_status')) {
                    $table->string('verification_status', 50)->default('pending')->index()->after('uploaded_at');
                }
            });
        }

        if (!Schema::hasTable('student_research_publications')) {
            Schema::create('student_research_publications', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('student_id')->index();

                $table->string('type', 50)->default('publication')->index();
                $table->string('title', 500);
                $table->string('journal_or_conference', 500)->nullable();
                $table->string('publisher', 255)->nullable();
                $table->string('doi', 255)->nullable();
                $table->string('url', 1000)->nullable();

                $table->unsignedSmallInteger('publication_year')->nullable();
                $table->text('abstract')->nullable();

                $table->string('attachment_path', 1000)->nullable();
                $table->string('attachment_name', 255)->nullable();

                $table->string('status', 50)->default('submitted')->index();
                $table->timestamp('submitted_at')->nullable();

                $table->text('remarks')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_research_publications');

        if (Schema::hasTable('student_documents')) {
            Schema::table('student_documents', function (Blueprint $table) {
                foreach ([
                    'verification_status',
                    'uploaded_at',
                    'uploaded_by_student',
                    'file_size',
                    'mime_type',
                    'file_name',
                    'file_path',
                    'document_type',
                    'document_title',
                ] as $column) {
                    if (Schema::hasColumn('student_documents', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                foreach ([
                    'profile_photo_uploaded_at',
                    'profile_photo_path',
                ] as $column) {
                    if (Schema::hasColumn('students', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};