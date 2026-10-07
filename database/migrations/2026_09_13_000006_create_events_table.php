<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            /* ---------------------------------------------------------- */
            /*  Relations                                                 */
            /* ---------------------------------------------------------- */
            $table->foreignId('organizer_page_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /* ---------------------------------------------------------- */
            /*  Contenu éditorial                                         */
            /* ---------------------------------------------------------- */
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->longText('description')->nullable();

            /** Tableaux JSON : liste à puces "Ce qui vous attend" */
            $table->json('highlights')->nullable();

            /** FAQ : [{ q: string, a: string }, …] */
            $table->json('faq')->nullable();

            /** Tags libres affichés en pills */
            $table->json('tags')->nullable();

            /* ---------------------------------------------------------- */
            /*  Média                                                     */
            /* ---------------------------------------------------------- */
            $table->string('cover_image_path')->nullable();
            $table->string('cover_video_path')->nullable();
            $table->string('audio_narration_path')->nullable();

            /** Chemins relatifs (disque public) de la galerie */
            $table->json('gallery')->nullable();

            /* ---------------------------------------------------------- */
            /*  Mode & lieu                                               */
            /* ---------------------------------------------------------- */
            $table->enum('mode', ['online', 'in_person', 'hybrid'])
                ->default('in_person');

            $table->string('online_url')->nullable();

            $table->string('venue_name')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            /* ---------------------------------------------------------- */
            /*  Dates                                                     */
            /* ---------------------------------------------------------- */
            $table->string('timezone')->default('Africa/Douala');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();

            /* ---------------------------------------------------------- */
            /*  Publication                                               */
            /* ---------------------------------------------------------- */
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])
                ->default('draft');

            /* ---------------------------------------------------------- */
            /*  Statistiques dénormalisées (évite les COUNT en cascade)   */
            /* ---------------------------------------------------------- */
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('registration_count')->default(0);
            $table->unsignedInteger('viewers_count')->default(0);

            $table->decimal('average_rating', 2, 1)->nullable();
            $table->unsignedInteger('review_count')->default(0);

            $table->unsignedInteger('lowest_price')->default(0);
            $table->boolean('is_free')->default(false);

            /* ---------------------------------------------------------- */
            /*  Timestamps & soft deletes                                 */
            /* ---------------------------------------------------------- */
            $table->timestamps();
            $table->softDeletes();

            /* ---------------------------------------------------------- */
            /*  Index                                                     */
            /* ---------------------------------------------------------- */
            $table->index(['status', 'starts_at']);
            $table->index(['latitude', 'longitude']);
            $table->index('is_free');
            $table->index('lowest_price');
            $table->index('category_id');
            $table->index('city_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
