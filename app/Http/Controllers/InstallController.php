<?php

namespace App\Http\Controllers;

use App\CPU\Helpers;
use App\Model\BusinessSetting;
use App\Model\ShippingType;
use App\Models\NotificationMessage;
use App\Traits\ActivationClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class InstallController extends Controller
{
    use ActivationClass;

    public function step0()
    {
        return view('installation.step0');
    }

    public function step1()
    {
        $permission['curl_enabled'] = function_exists('curl_version');
        $permission['db_file_write_perm'] = is_writable(base_path('.env'));
        $permission['routes_file_write_perm'] = is_writable(base_path('app/Providers/RouteServiceProvider.php'));
        return view('installation.step1', compact('permission'));
    }

    public function step2()
    {
        return view('installation.step2');
    }

    public function step3()
    {
        return view('installation.step3');
    }

    public function step4()
    {
        return view('installation.step4');
    }

    public function step5()
    {
        Artisan::call('config:cache');
        Artisan::call('config:clear');
        return view('installation.step5');
    }

    public function purchase_code(Request $request)
    {
        Helpers::setEnvironmentValue('SOFTWARE_ID', 'MzE0NDg1OTc=');
        Helpers::setEnvironmentValue('BUYER_USERNAME', $request['username']);
        Helpers::setEnvironmentValue('PURCHASE_CODE', $request['purchase_key']);

        $post = [
            'name' => $request['name'],
            'email' => $request['email'],
            'username' => $request['username'],
            'purchase_key' => $request['purchase_key'],
            'domain' => preg_replace("#^[^:/.]*[:/]+#i", "", url('/')),
        ];
        $response = $this->dmvf($post);

        return redirect($response . '?token=' . bcrypt('step_3'));
    }

    public function system_settings(Request $request)
    {
        DB::table('admins')->insertOrIgnore([
            'name' => $request['admin_name'],
            'email' => $request['admin_email'],
            'admin_role_id' => 1,
            'password' => bcrypt($request['admin_password']),
            'phone' => $request['admin_phone'],
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'company_name'], [
            'value' => $request['company_name']
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'currency_model'], [
            'value' => $request['currency_model']
        ]);

        DB::table('admin_wallets')->insert([
            'admin_id' => 1,
            'withdrawn' => 0,
            'commission_earned' => 0,
            'inhouse_earning' => 0,
            'delivery_charge_earned' => 0,
            'pending_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'product_brand'], [
            'value' => 1
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'digital_product'], [
            'value' => 1
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'delivery_boy_expected_delivery_date_message'], [
            'value' => json_encode([
                'status' => 0,
                'message' => ''
            ])
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'order_canceled'], [
            'value' => json_encode([
                'status' => 0,
                'message' => ''
            ])
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'offline_payment'], [
            'value' => json_encode([
                'status' => 0
            ])
        ]);

        $refund_policy = BusinessSetting::where(['type' => 'refund-policy'])->first();
        if ($refund_policy) {
            $refund_value = json_decode($refund_policy['value'], true);
            if(!isset($refund_value['status'])){
                BusinessSetting::where(['type' => 'refund-policy'])->update([
                    'value' => json_encode([
                        'status' => 1,
                        'content' => $refund_policy['value'],
                    ]),
                ]);
            }
        }elseif(!$refund_policy){
            BusinessSetting::insert([
                'type' => 'refund-policy',
                'value' => json_encode([
                    'status' => 1,
                    'content' => '',
                ]),
            ]);
        }

        $return_policy = BusinessSetting::where(['type' => 'return-policy'])->first();
        if ($return_policy) {
            $return_value = json_decode($return_policy['value'], true);
            if(!isset($return_value['status'])){
                BusinessSetting::where(['type' => 'return-policy'])->update([
                    'value' => json_encode([
                        'status' => 1,
                        'content' => $return_policy['value'],
                    ]),
                ]);
            }
        }elseif(!$return_policy){
            BusinessSetting::insert([
                'type' => 'return-policy',
                'value' => json_encode([
                    'status' => 1,
                    'content' => '',
                ]),
            ]);
        }

        $cancellation_policy = BusinessSetting::where(['type' => 'cancellation-policy'])->first();
        if ($cancellation_policy) {
            $cancellation_value = json_decode($cancellation_policy['value'], true);
            if(!isset($cancellation_value['status'])){
                BusinessSetting::where(['type' => 'cancellation-policy'])->update([
                    'value' => json_encode([
                        'status' => 1,
                        'content' => $cancellation_policy['value'],
                    ]),
                ]);
            }
        }elseif(!$cancellation_policy){
            BusinessSetting::insert([
                'type' => 'cancellation-policy',
                'value' => json_encode([
                    'status' => 1,
                    'content' => '',
                ]),
            ]);
        }

        DB::table('business_settings')->updateOrInsert(['type' => 'temporary_close'], [
            'type' => 'temporary_close',
            'value' => json_encode([
                'status' => 0,
            ])
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'vacation_add'], [
            'type' => 'vacation_add',
            'value' => json_encode([
                'status' => 0,
                'vacation_start_date' => null,
                'vacation_end_date' => null,
                'vacation_note' => null
            ])
        ]);

        DB::table('business_settings')->updateOrInsert(['type' => 'cookie_setting'], [
            'type' => 'cookie_setting',
            'value' => json_encode([
                'status' => 0,
                'cookie_text' => null
            ])
        ]);

        DB::table('colors')
            ->whereIn('id', [16,38,93])
            ->delete();

        //apple login information insert
        if (BusinessSetting::where(['type' => 'apple_login'])->first() == false) {
            DB::table('business_settings')->insert([
                'type' => 'apple_login',
                'value' => json_encode([
                    [
                        'login_medium' => 'apple',
                        'client_id' => '',
                        'client_secret' => '',
                        'status' => 0,
                        'team_id' => '',
                        'key_id' => '',
                        'service_file' => '',
                        'redirect_url' => '',
                    ]
                ]),
                'updated_at' => now()
            ]);
        }

        DB::table('business_settings')->updateOrInsert([
            'type' => 'ref_earning_status',
            'value' => 0,
            'updated_at' => now()
        ]);

        DB::table('business_settings')->updateOrInsert([
            'type' => 'ref_earning_exchange_rate',
            'value' => 0,
            'updated_at' => now()
        ]);

        // new payment module necessary table insert
        try {
            if (!Schema::hasTable('addon_settings')) {
                $sql = File::get(base_path('database/migrations/addon_settings.sql'));
                DB::unprepared($sql);
            }


            if (!Schema::hasTable('payment_requests')) {
                $sql = File::get(base_path('database/migrations/payment_requests.sql'));
                DB::unprepared($sql);
            }

            $this->set_data();


        } catch (\Exception $exception) {
            //
        }

        // guest checkout add
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'guest_checkout',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        // minimum_order_amount
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'minimum_order_amount',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'minimum_order_amount_by_seller',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'minimum_order_amount_status',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //admin_login_url
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'admin_login_url',
//            'value' => 'admin',
//            'updated_at' => now()
//        ]);
//
//        //employee_login_url
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'employee_login_url',
//            'value' => 'employee',
//            'updated_at' => now()
//        ]);
//
//        //free_delivery_status
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'free_delivery_status',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //free_delivery_responsibility
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'free_delivery_responsibility',
//            'value' => 'admin',
//            'updated_at' => now()
//        ]);
//
//        //free_delivery_over_amount
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'free_delivery_over_amount',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //free_delivery_over_amount
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'free_delivery_over_amount_seller',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //add_funds_to_wallet
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'add_funds_to_wallet',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //minimum_add_fund_amount
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'minimum_add_fund_amount',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //maximum_add_fund_amount
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'maximum_add_fund_amount',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //user_app_version_control
//        DB::table('business_settings')->updateOrInsert([
//            'type' => 'user_app_version_control',
//            'value' => 0,
//            'updated_at' => now()
//        ]);
//
//        //user_app_version_control
//        DB::table('business_settings')->insert([
//            'type' => 'user_app_version_control',
//            'value' => json_encode([
//                "for_android" => [
//                    "status" => 1,
//                    "version" => "14.1",
//                    "link" => ""
//                ],
//                "for_ios" => [
//                    "status" => 1,
//                    "version" => "14.1",
//                    "link" => ""
//                ]
//            ]),
//            'updated_at' => now()
//        ]);
//
//        //seller_app_version_control
//        DB::table('business_settings')->insert([
//            'type' => 'seller_app_version_control',
//            'value' => json_encode([
//                "for_android" => [
//                    "status" => 1,
//                    "version" => "14.1",
//                    "link" => ""
//                ],
//                "for_ios" => [
//                    "status" => 1,
//                    "version" => "14.1",
//                    "link" => ""
//                ]
//            ]),
//            'updated_at' => now()
//        ]);
//
//        //Delivery_man_app_version_control
//        DB::table('business_settings')->insert([
//            'type' => 'delivery_man_app_version_control',
//            'value' => json_encode([
//                "for_android" => [
//                    "status" => 1,
//                    "version" => "14.1",
//                    "link" => ""
//                ],
//                "for_ios" => [
//                    "status" => 1,
//                    "version" => "14.1",
//                    "link" => ""
//                ]
//            ]),
//            'updated_at' => now()
//        ]);
//
//        //whatsapp
//        DB::table('business_settings')->insert([
//            'type' => 'whatsapp',
//            'value' => json_encode([
//                "status"=>1,
//                "phone"=>"00000000000"
//                ]),
//            'updated_at' => now()
//        ]);
//
//        //currency_symbol_position
//        DB::table('business_settings')->insert([
//            'type' => 'currency_symbol_position',
//            'value' => "left",
//            'updated_at' => now()
//        ]);

        // data insert into shipping table
        $new_shipping_type = new ShippingType;
        $new_shipping_type->seller_id = 0;
        $new_shipping_type->shipping_type = 'order_wise';
        $new_shipping_type->save();

        self::notification_message_import(); // notification message add in the new table
        self::company_riliability_import(); // company riliability add in the new table



        $previousRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvier, $previousRouteServiceProvier);
        //sleep(5);
        return view('installation.step6');
    }

    public static function notification_message_import(){
        /** for customer */
        $user_type_customer = NotificationMessage::where('user_type','customer')->get();
        $array_for_customer_message_key = [
            'order_pending_message',
            'order_confirmation_message',
            'order_processing_message',
            'out_for_delivery_message',
            'order_delivered_message',
            'order_returned_message',
            'order_failed_message',
            'order_canceled',
            'order_refunded_message',
            'refund_request_canceled_message',
            'message_from_delivery_man',
            'message_from_seller',
            'fund_added_by_admin_message',
        ];
        foreach ($array_for_customer_message_key as $key=>$value ){
            $key_check = $user_type_customer->where('key',$value)->first();
            if($key_check == null){
                DB::table('notification_messages')->updateOrInsert([
                    'user_type'=>'customer',
                    'key'=>$value,
                    'message'=>'customize your'.' '.str_replace('_',' ', $value).' '.'message',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }
        }/**end for customer*/

        $user_type_seller = NotificationMessage::where('user_type','seller')->get();
        $array_for_seller_message_key = [
            'new_order_message',
            'refund_request_message',
            'order_edit_message',
            'withdraw_request_status_message',
            'message_from_customer',
            'delivery_man_assign_by_admin_message',
            'order_delivered_message',
            'order_canceled',
            'order_refunded_message',
            'refund_request_canceled_message',
            'refund_request_status_changed_by_admin',

        ];
        foreach ($array_for_seller_message_key as $key=>$value ){
            $key_check = $user_type_seller->where('key',$value)->first();
            if($key_check == null){
                DB::table('notification_messages')->insert([
                    'user_type'=>'seller',
                    'key'=>$value,
                    'message'=>'customize your'.' '.str_replace('_',' ', $value).' '.'message',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }
        }/**end for seller*/

        /**start delivery man*/
        $user_type_delivery_man = NotificationMessage::where('user_type','delivery_man')->get();
        $array_for_delivery_man_message_key = [
            'new_order_assigned_message',
            'expected_delivery_date',
            'delivery_man_charge',
            'order_canceled',
            'order_rescheduled_message',
            'order_edit_message',
            'message_from_seller',
            'message_from_admin',
            'message_from_customer',
            'cash_collect_by_admin_message',
            'cash_collect_by_seller_message',
            'withdraw_request_status_message',

        ];
        foreach ($array_for_delivery_man_message_key as $key=>$value ){
            $key_check = $user_type_delivery_man->where('key',$value)->first();
            if($key_check == null){
                DB::table('notification_messages')->insert([
                    'user_type'=>'delivery_man',
                    'key'=>$value,
                    'message'=>'customize your'.' '.str_replace('_',' ', $value).' '.'message',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }
        }/**end for delivery man*/
    }

    public static function company_riliability_import(){
        $datas = [
            [
                'item' => 'delivery_info',
                'title' => 'Fast Delivery all across the country',
                'image' => '',
                'status' => 1,
            ],
            [
                'item' => 'safe_payment',
                'title' => 'Safe Payment',
                'image' => '',
                'status' => 1,
            ],
            [
                'item' => 'return_policy',
                'title' => '7 Days Return Policy',
                'image' => '',
                'status' => 1,
            ],
            [
                'item' => 'authentic_product',
                'title' => '100% Authentic Products',
                'image' => '',
                'status' => 1,
            ],
        ];

        BusinessSetting::updateOrInsert(['type' => 'company_reliability'], [
            'value' => json_encode($datas),
        ]);
    }

    public function database_installation(Request $request)
    {
        if (self::check_database_connection($request->DB_HOST, $request->DB_DATABASE, $request->DB_USERNAME, $request->DB_PASSWORD)) {

            $key = base64_encode(random_bytes(32));
            $output = 'APP_NAME=6valley' . time() . '
                    APP_ENV=live
                    APP_KEY=base64:' . $key . '
                    APP_DEBUG=false
                    APP_INSTALL=true
                    APP_LOG_LEVEL=debug
                    APP_MODE=live
                    APP_URL=' . URL::to('/') . '

                    DB_CONNECTION=mysql
                    DB_HOST=' . $request->DB_HOST . '
                    DB_PORT=3306
                    DB_DATABASE=' . $request->DB_DATABASE . '
                    DB_USERNAME=' . $request->DB_USERNAME . '
                    DB_PASSWORD=' . $request->DB_PASSWORD . '

                    BROADCAST_DRIVER=log
                    CACHE_DRIVER=file
                    SESSION_DRIVER=file
                    SESSION_LIFETIME=60
                    QUEUE_DRIVER=sync

                    AWS_ENDPOINT=
                    AWS_ACCESS_KEY_ID=
                    AWS_SECRET_ACCESS_KEY=
                    AWS_DEFAULT_REGION=us-east-1
                    AWS_BUCKET=

                    REDIS_HOST=127.0.0.1
                    REDIS_PASSWORD=null
                    REDIS_PORT=6379

                    PUSHER_APP_ID=
                    PUSHER_APP_KEY=
                    PUSHER_APP_SECRET=
                    PUSHER_APP_CLUSTER=mt1

                    PURCHASE_CODE=' . session('purchase_key') . '
                    BUYER_USERNAME=' . session('username') . '
                    SOFTWARE_ID=MzE0NDg1OTc=

                    SOFTWARE_VERSION=' . SOFTWARE_VERSION . '
                    ';
            $file = fopen(base_path('.env'), 'w');
            fwrite($file, $output);
            fclose($file);

            $path = base_path('.env');
            if (file_exists($path)) {
                return redirect('step4');
            } else {
                session()->flash('error', 'Database error!');
                return redirect('step3');
            }
        } else {
            session()->flash('error', 'Database error!');
            return redirect('step3');
        }
    }

    public function import_sql()
    {
        try {
            $sql_path = base_path('installation/backup/database.sql');
            DB::unprepared(file_get_contents($sql_path));
            return redirect('step5');
        } catch (\Exception $exception) {
            session()->flash('error', 'Your database is not clean, do you want to clean database then import?');
            return back();
        }
    }

    public function force_import_sql()
    {
        try {
            Artisan::call('db:wipe');
            $sql_path = base_path('installation/backup/database.sql');
            DB::unprepared(file_get_contents($sql_path));
            return redirect('step5');
        } catch (\Exception $exception) {
            session()->flash('error', 'Check your database permission!');
            return back();
        }
    }

    function check_database_connection($db_host = "", $db_name = "", $db_user = "", $db_pass = "")
    {

        if (@mysqli_connect($db_host, $db_user, $db_pass, $db_name)) {
            return true;
        } else {
            return false;
        }
    }
}
