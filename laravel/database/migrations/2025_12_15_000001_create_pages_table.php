<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable(); // For child pages
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable(); // Rich HTML content
            $table->enum('page_type', ['single', 'parent'])->default('single');
            // single = Shows as tab in header
            // parent = Has children, shows in hamburger menu
            $table->string('icon')->nullable(); // FontAwesome icon class
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_navigation')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('pages')->onDelete('cascade');
            $table->index(['parent_id', 'sort_order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
}






