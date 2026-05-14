<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_seometa_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        $defaults = [
            'site_title'           => '',
            'site_description'     => '',
            'og_image'             => '',
            'favicon'              => '',
            'theme_color'          => '#1a1a2e',
            'twitter_card_type'    => 'summary_large_image',
            'hosting_name'         => '',
            'hosting_logo'         => '',
            'enable_server_cards'  => '1',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('blueprint_seometa_settings')->insert([
                'key'        => $key,
                'value'      => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_seometa_settings');
    }
};
