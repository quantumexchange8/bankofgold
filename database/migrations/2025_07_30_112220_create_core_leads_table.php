<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('core_leads', function (Blueprint $table) {
            $table->id();

            // Meta and source info
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('import_id')->nullable();
            $table->string('source_id')->nullable();
            $table->string('lead_id')->nullable();
            $table->string('categories')->nullable();
            $table->date('date_added')->nullable();
            $table->date('date_logged')->nullable();
            $table->date('date_last_modified')->nullable();
            $table->string('referrer')->nullable();
            $table->string('modified_by')->nullable();
            $table->string('lead_status')->nullable();
            $table->string('status')->nullable()->default('new');
            $table->boolean('is_duplicate')->nullable()->default(false);

            // Personal Information
            $table->text('title')->nullable();
            $table->text('first_name')->nullable();
            $table->text('middle_name')->nullable();
            $table->text('surname')->nullable();
            $table->text('registered_full_name')->nullable();
            $table->text('private_email_1')->nullable();
            $table->text('private_email_2')->nullable();
            $table->text('home_telephone_1')->nullable();
            $table->text('home_telephone_2')->nullable();
            $table->text('mobile_telephone_1')->nullable();
            $table->text('mobile_telephone_2')->nullable();
            $table->text('private_fax')->nullable();
            $table->text('geo_location')->nullable();
            $table->text('ip_address')->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->text('address_line_3')->nullable();
            $table->text('city')->nullable();
            $table->text('state')->nullable();
            $table->text('country')->nullable();
            $table->text('postal_code')->nullable();
            $table->text('currency')->nullable();
            $table->text('nationality')->nullable();
            $table->text('religion')->nullable();
            $table->text('gender')->nullable();
            $table->date('dob')->nullable();
            $table->integer('age')->nullable();
            $table->text('age_group')->nullable();
            $table->text('marital_status')->nullable();
            $table->text('has_children')->nullable();
            $table->integer('number_of_children')->nullable();

            // Social Media
            $table->text('socials')->nullable();
            $table->text('facebook')->nullable();
            $table->text('twitter')->nullable();
            $table->text('linkedin')->nullable();
            $table->text('instagram')->nullable();
            $table->text('tiktok')->nullable();

            // Employment Info
            $table->text('occupation')->nullable();
            $table->text('company_name')->nullable();
            $table->text('nature_of_business')->nullable();
            $table->text('position')->nullable();
            $table->text('business_type')->nullable();
            $table->integer('years_employed')->nullable();
            $table->text('company_email_1')->nullable();
            $table->text('company_email_2')->nullable();
            $table->text('office_phone_1')->nullable();
            $table->text('office_phone_2')->nullable();
            $table->text('office_fax')->nullable();
            $table->text('home_owner')->nullable();
            $table->integer('number_of_properties')->nullable();
            $table->text('vehicle_owner')->nullable();
            $table->integer('car_count')->nullable();
            $table->integer('motorbike_count')->nullable();
            $table->date('license_start_date')->nullable();
            $table->date('license_expiry_date')->nullable();

            // Investment Interests
            $table->text('investment_products_interested_in')->nullable();
            $table->text('commercial_real_estate_investments')->nullable();
            $table->text('commercial_real_estate_owned')->nullable();
            $table->decimal('commercial_real_estate_value', 15, 2)->nullable();
            $table->text('rental_real_estate_investments')->nullable();
            $table->text('rental_real_estate_owned')->nullable();
            $table->decimal('rental_real_estate_value', 15, 2)->nullable();

            // IPO Investments
            $table->text('ipo_market_experience')->nullable();
            $table->text('ipo_owned')->nullable();
            $table->decimal('ipo_values', 15, 2)->nullable();

            // Stock Market
            $table->text('stock_market_experience')->nullable();
            $table->text('stocks_owned')->nullable();
            $table->decimal('stock_values', 15, 2)->nullable();

            // Funds & Bonds
            $table->text('fund_bond_experience')->nullable();
            $table->text('fund_bond_term')->nullable();
            $table->decimal('fund_bond_percentage', 5, 2)->nullable();

            $table->text('hedge_pension_experience')->nullable();
            $table->decimal('hedge_pension_value', 15, 2)->nullable();

            $table->text('commodities_experience')->nullable();
            $table->decimal('commodities_value', 15, 2)->nullable();

            // Cryptocurrency
            $table->boolean('cryptocurrency_market')->nullable()->default(false);
            $table->text('cryptocurrencies_held')->nullable();
            $table->text('crypto_exchanges')->nullable();
            $table->text('crypto_wallets')->nullable();

            // Investment experience regions
            $table->text('investment_experience_usa')->nullable();
            $table->text('investment_experience_europe')->nullable();
            $table->text('investment_experience_asia')->nullable();
            $table->text('investment_experience_australia')->nullable();
            $table->text('investment_experience_other')->nullable();
            
            // Broker info
            $table->boolean('broker_local')->nullable()->default(false); // Has a local broker? yes/no
            $table->text('broker_company_name')->nullable();
            $table->text('broker_name')->nullable();
            $table->boolean('broker_international')->nullable()->default(false); // Has international broker
            $table->text('broker_bank')->nullable();

            // Contact & transaction dates
            $table->date('last_contact_date')->nullable();
            $table->date('last_transaction_date')->nullable();

            // Contact type
            $table->text('type_of_contact')->nullable();

            // Financial figures
            $table->decimal('typical_investment_size_usd', 15, 2)->nullable();
            $table->decimal('liquidity_usd', 15, 2)->nullable();

            // Decision-making status
            $table->boolean('decision_maker')->nullable()->default(false); // Are they the decision maker?

            // Comments and scheduling
            $table->text('comments')->nullable();
            $table->text('best_days_to_call')->nullable(); // E.g., "Mon–Wed"
            $table->text('best_time_to_call')->nullable(); // E.g., "Afternoon"
            
            $table->text('tq_company_name')->nullable();
            $table->date('tq_date')->nullable();
            $table->text('tq_agent')->nullable();
            $table->text('verifier_agent')->nullable();
            $table->date('verify_date')->nullable();
            $table->time('verified_time')->nullable();
            
            $table->text('stock_trade_interest')->nullable();
            $table->text('pre_ipo_experience')->nullable();
            $table->text('preferred_investment_strategy')->nullable();
            $table->decimal('available_investment_capital', 15, 2)->nullable();
            $table->text('investment_timeframe')->nullable();
            $table->text('schedule_broker_call')->nullable();
            $table->text('investment_term')->nullable();
            $table->text('investment_objective')->nullable(); // Investment objective
            $table->text('investment_budget_range')->nullable(); // Investment budget (helps determine product availability)
            $table->text('next_investment_time')->nullable(); // When are you planning to invest next?
            
            $table->text('followup_email')->nullable(); // Best email (for alerts or notices)
            $table->text('followup_mobile')->nullable(); // Best mobile number
            
            $table->text('fund_manager_questions')->nullable(); // Questions for fund manager
            $table->text('request_email_or_call')->nullable(); // Request an email Package or Call?
            $table->text('appointment_scheduled_for')->nullable(); // Appointment Scheduled for
            $table->integer('grading')->nullable(); // Grading (1–5)
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('import_id')->references('id')->on('data_imports')->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_leads');
    }
};
