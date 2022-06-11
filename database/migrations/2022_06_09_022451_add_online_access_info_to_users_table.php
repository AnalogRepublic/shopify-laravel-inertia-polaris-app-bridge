<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('shopify_user_id')->unique()->before('created_at');
            $table->string('shop')->before('created_at');
            $table->string('first_name')->nullable()->before('created_at');
            $table->string('last_name')->nullable()->before('created_at');
            $table->boolean('account_owner')->nullable()->before('created_at');
            $table->string('locale')->nullable()->before('created_at');
            $table->boolean('collaborator')->nullable()->before('created_at');
            $table->dropUnique(['email']);
            $table->unique(['email', 'shop']);
            $table->dropColumn('name');
            $table->string('password')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shopify_user_id');
            $table->dropcolumn('shop');
            $table->dropcolumn('first_name');
            $table->dropcolumn('last_name');
            $table->dropColumn('account_owner');
            $table->dropcolumn('locale');
            $table->dropColumn('collaborator');
            $table->unique(['email']);
            $table->dropUnique(['email', 'shop']);
            $table->string('name')->after('id');
            $table->string('password')->nullable(false)->after('email_verified_at')->change();
        });
    }
};
