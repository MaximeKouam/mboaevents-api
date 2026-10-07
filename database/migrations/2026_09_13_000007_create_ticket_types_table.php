<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();

            /* ---------------------------------------------------------- */
            /*  Relations                                                 */
            /* ---------------------------------------------------------- */
            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            /* ---------------------------------------------------------- */
            /*  Identité du billet                                        */
            /* ---------------------------------------------------------- */
            $table->string('name');                 // "Early Bird", "Standard", "VIP"
            $table->text('description')->nullable();

            /** Tag affiché au-dessus de la carte : "Populaire", "Épuisé bientôt"… */
            $table->string('tag')->nullable();

            /** Ordre d'affichage dans la sidebar */
            $table->unsignedInteger('position')->default(0);

            /** Permet d'archiver un tier sans le supprimer */
            $table->boolean('is_active')->default(true);

            /* ---------------------------------------------------------- */
            /*  Tarification                                              */
            /* ---------------------------------------------------------- */
            $table->boolean('is_free')->default(false);

            // Montant stocké en unité entière de la devise (XAF n'a pas de sous-unité)
            $table->unsignedInteger('price')->default(0);
            $table->string('currency', 3)->default('XAF');

            /* ---------------------------------------------------------- */
            /*  Contenu (avantages listés)                                */
            /* ---------------------------------------------------------- */
            /** ["Accès aux keynotes", "Pauses café", "Badge digital"] */
            $table->json('perks')->nullable();

            /* ---------------------------------------------------------- */
            /*  Stock                                                     */
            /* ---------------------------------------------------------- */
            $table->unsignedInteger('quantity_total');
            $table->unsignedInteger('quantity_sold')->default(0);

            // Limite optionnelle par commande (ex : max 5 billets)
            $table->unsignedInteger('max_per_order')->nullable();

            /* ---------------------------------------------------------- */
            /*  Fenêtre de vente                                          */
            /* ---------------------------------------------------------- */
            $table->timestamp('sales_start_at')->nullable();
            $table->timestamp('sales_end_at')->nullable();

            /* ---------------------------------------------------------- */
            /*  Timestamps & index                                        */
            /* ---------------------------------------------------------- */
            $table->timestamps();

            $table->index(['event_id', 'is_active']);
            $table->index(['event_id', 'position']);
            $table->index('is_free');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
