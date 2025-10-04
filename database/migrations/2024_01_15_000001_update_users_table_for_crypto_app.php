<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('phone_number')->nullable()->after('username');
            $table->date('date_of_birth')->nullable()->after('phone_number');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->string('country')->nullable()->after('gender');
            $table->string('state')->nullable()->after('country');
            $table->string('city')->nullable()->after('state');
            $table->text('address')->nullable()->after('city');
            $table->string('referral_code')->nullable()->after('address');
            $table->boolean('terms_accepted')->default(false)->after('referral_code');
            $table->boolean('kyc_verified')->default(false)->after('terms_accepted');
            $table->boolean('face_id_enabled')->default(false)->after('kyc_verified');
            $table->boolean('fingerprint_enabled')->default(false)->after('face_id_enabled');
            $table->string('email_verification_token')->nullable()->after('fingerprint_enabled');
            $table->string('email_verification_otp')->nullable()->after('email_verification_token');
            $table->timestamp('email_verification_otp_expires')->nullable()->after('email_verification_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'phone_number',
                'date_of_birth',
                'gender',
                'country',
                'state',
                'city',
                'address',
                'referral_code',
                'terms_accepted',
                'kyc_verified',
                'face_id_enabled',
                'fingerprint_enabled',
                'email_verification_token',
                'email_verification_otp',
                'email_verification_otp_expires',
            ]);
        });
    }
};
