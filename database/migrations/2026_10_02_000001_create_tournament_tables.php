<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('sport')->default('futbol'); // futbol, tenis, padel, baloncesto, fifa, voleibol, otro
            $table->string('format')->default('knockout'); // knockout, league, groups
            $table->string('status')->default('active'); // active, completed
            $table->string('location')->nullable(); // Sede / Ubicación
            $table->text('description')->nullable();
            $table->boolean('is_current')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('short_name', 10);
            $table->string('color', 20)->default('#1e3a8a');
            $table->string('city')->nullable();
            $table->string('coach')->nullable();
            $table->string('group_name')->nullable(); // Grupo A, Grupo B
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('dorsal')->nullable();
            $table->string('position')->default('Jugador');
            $table->timestamps();
        });

        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->string('stage'); // Cuartos, Semifinal, Final, Jornada 1, Grupo A
            $table->foreignId('home_team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('away_team_id')->constrained('teams')->onDelete('cascade');
            $table->integer('home_score')->default(0);
            $table->integer('away_score')->default(0);
            $table->integer('home_sets')->default(0);
            $table->integer('away_sets')->default(0);
            $table->integer('home_penalties')->default(0);
            $table->integer('away_penalties')->default(0);
            $table->string('current_period')->default('1T');
            $table->string('status')->default('scheduled'); // scheduled, live, finished
            $table->integer('elapsed_seconds')->default(0);
            $table->boolean('timer_running')->default(false);
            $table->json('tennis_data')->nullable();
            $table->json('stats')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('next_match_id')->nullable()->constrained('game_matches')->onDelete('set null');
            $table->string('next_match_slot')->nullable(); // home or away
            $table->string('scheduled_time')->nullable();
            $table->timestamps();
        });

        Schema::create('match_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained('game_matches')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('player_id')->nullable()->constrained('players')->onDelete('set null');
            $table->foreignId('assist_player_id')->nullable()->constrained('players')->onDelete('set null');
            $table->string('type'); // goal, card_yellow, card_red, point_tennis, ace, double_fault, point_basket_1, point_basket_2, point_basket_3
            $table->integer('minute')->default(0);
            $table->string('period')->default('1T');
            $table->string('detail')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_incidents');
        Schema::dropIfExists('game_matches');
        Schema::dropIfExists('players');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('tournaments');
    }
};
