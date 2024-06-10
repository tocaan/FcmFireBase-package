<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeviceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('device_token', 255)->nullable();
            $table->string('lang')->default('en');
            $table->string('platform', 20)->index()->nullable();
            $table->string('model', 200)->nullable();
            $table->string('app_version', 200)->nullable();
            $table->string('os_version', 200)->nullable();
            if(config("fcm-firebase.allow_morph", false)) {
                $morph = config("fcm-firebase.morph");
                $table->nullableMorphs(config("fcm-firebase.morph"));
                if(config("fcm-firebase.morph_index")) {
                    $table->index([$morph."_id",$morph."_type" ]);
                }
            } else {
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->foreign('user_id')->references('id')->on('users')
                      ->onUpdate('cascade')->onDelete('cascade');
            }

            $table->primary("id");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_tokens');
    }
}
