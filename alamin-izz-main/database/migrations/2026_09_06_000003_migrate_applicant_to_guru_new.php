<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables with a legacy unsignedBigInteger applicant_id FK that needs to
     * become a VARCHAR(20) gn_id FK pointing at guru_new instead.
     */
    private array $dependents = ['enroll_guru_baru', 'attendance_guru_baru', 'notification_ladap', 'feedback_response'];

    public function up(): void
    {
        Schema::create('hr_administrator', function (Blueprint $table) {
            $table->string('hrID', 20)->primary();
            $table->string('hrname');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('password');
            $table->string('role')->default('hr');
            $table->timestamps();
        });

        DB::table('hr_administrator')->insert([
            'hrID'         => 'HR001',
            'hrname'       => 'HR Administrator',
            'email'        => 'hr@edutrain.test',
            'phone_number' => '03-00000000',
            'password'     => Hash::make('password'),
            'role'         => 'hr',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // 1. Add the new string PK to applicant and backfill sequential codes.
        Schema::table('applicant', function (Blueprint $table) {
            $table->string('gn_id', 20)->nullable()->after('applicant_id');
        });

        $applicants = DB::table('applicant')->orderBy('applicant_id')->pluck('applicant_id');
        foreach ($applicants as $i => $applicantId) {
            DB::table('applicant')->where('applicant_id', $applicantId)
                ->update(['gn_id' => 'GN' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)]);
        }

        // 2. Mirror gn_id onto every dependent table before repointing FKs.
        foreach ($this->dependents as $childTable) {
            Schema::table($childTable, function (Blueprint $table) {
                $table->string('gn_id', 20)->nullable()->after('applicant_id');
            });

            DB::statement("
                UPDATE {$childTable} c
                JOIN applicant a ON c.applicant_id = a.applicant_id
                SET c.gn_id = a.gn_id
            ");
        }

        // 3. Drop the old FKs/unique keys/columns on dependents.
        Schema::table('enroll_guru_baru', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropUnique('enroll_guru_baru_applicant_id_course_id_unique');
            $table->dropColumn('applicant_id');
        });
        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropUnique('attendance_guru_baru_applicant_id_session_id_unique');
            $table->dropColumn('applicant_id');
        });
        Schema::table('notification_ladap', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropColumn('applicant_id');
        });
        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropColumn('applicant_id');
        });

        // 4. Promote gn_id to the primary key on applicant, then rename table + column.
        Schema::table('applicant', function (Blueprint $table) {
            $table->dropColumn('applicant_id');
        });
        DB::statement('ALTER TABLE applicant MODIFY gn_id VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE applicant ADD PRIMARY KEY (gn_id)');

        Schema::rename('applicant', 'guru_new');
        Schema::table('guru_new', function (Blueprint $table) {
            $table->renameColumn('full_name', 'gn_name');
        });

        // 5. Add the new HR-record attributes.
        Schema::table('guru_new', function (Blueprint $table) {
            $table->string('ic_number', 20)->nullable()->unique()->after('gn_id');
            $table->string('marital_status', 20)->nullable()->after('email');
            $table->string('gender', 10)->nullable()->after('marital_status');
            $table->string('address')->nullable()->after('gender');
            $table->string('race', 50)->nullable()->after('address');
            $table->date('appointed_date')->nullable()->after('race');
            $table->date('service_date')->nullable()->after('appointed_date');
            $table->string('role', 20)->default('new_teacher')->after('service_date');
            $table->string('current_status', 20)->default('Inactive')->after('role');
            $table->string('hrID', 20)->nullable()->after('current_status');
            $table->unsignedBigInteger('schoolID')->nullable()->after('hrID');

            $table->foreign('hrID')->references('hrID')->on('hr_administrator')->nullOnDelete();
            $table->foreign('schoolID')->references('schoolID')->on('school')->nullOnDelete();
        });

        // 6. Rebuild FKs on dependents against guru_new.gn_id.
        Schema::table('enroll_guru_baru', function (Blueprint $table) {
            $table->unique(['gn_id', 'course_id']);
            $table->foreign('gn_id')->references('gn_id')->on('guru_new')->onDelete('cascade');
        });
        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->unique(['gn_id', 'session_id']);
            $table->foreign('gn_id')->references('gn_id')->on('guru_new')->onDelete('cascade');
        });
        Schema::table('notification_ladap', function (Blueprint $table) {
            $table->foreign('gn_id')->references('gn_id')->on('guru_new')->nullOnDelete();
        });
        Schema::table('feedback_response', function (Blueprint $table) {
            $table->foreign('gn_id')->references('gn_id')->on('guru_new')->nullOnDelete();
        });

        DB::statement('ALTER TABLE enroll_guru_baru MODIFY gn_id VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE attendance_guru_baru MODIFY gn_id VARCHAR(20) NOT NULL');
    }

    public function down(): void
    {
        Schema::table('enroll_guru_baru', function (Blueprint $table) {
            $table->dropForeign(['gn_id']);
            $table->dropUnique(['gn_id', 'course_id']);
        });
        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->dropForeign(['gn_id']);
            $table->dropUnique(['gn_id', 'session_id']);
        });
        Schema::table('notification_ladap', function (Blueprint $table) {
            $table->dropForeign(['gn_id']);
        });
        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign(['gn_id']);
        });

        Schema::table('guru_new', function (Blueprint $table) {
            $table->dropForeign(['hrID']);
            $table->dropForeign(['schoolID']);
            $table->dropColumn([
                'ic_number', 'marital_status', 'gender', 'address', 'race',
                'appointed_date', 'service_date', 'role', 'current_status', 'hrID', 'schoolID',
            ]);
            $table->renameColumn('gn_name', 'full_name');
        });

        Schema::rename('guru_new', 'applicant');

        Schema::table('applicant', function (Blueprint $table) {
            $table->id('applicant_id')->first();
        });
        DB::statement('ALTER TABLE applicant DROP PRIMARY KEY');
        DB::statement('ALTER TABLE applicant MODIFY applicant_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');

        $rows = DB::table('applicant')->orderBy('gn_id')->pluck('gn_id');
        foreach ($rows as $i => $gnId) {
            DB::table('applicant')->where('gn_id', $gnId)->update(['applicant_id' => $i + 1]);
        }

        foreach ($this->dependents as $childTable) {
            Schema::table($childTable, function (Blueprint $table) {
                $table->unsignedBigInteger('applicant_id')->nullable()->after('gn_id');
            });

            DB::statement("
                UPDATE {$childTable} c
                JOIN applicant a ON c.gn_id = a.gn_id
                SET c.applicant_id = a.applicant_id
            ");

            Schema::table($childTable, function (Blueprint $table) {
                $table->dropColumn('gn_id');
                $table->foreign('applicant_id')->references('applicant_id')->on('applicant')->onDelete('cascade');
            });
        }

        Schema::table('applicant', function (Blueprint $table) {
            $table->dropColumn('gn_id');
        });

        Schema::dropIfExists('hr_administrator');
    }
};
