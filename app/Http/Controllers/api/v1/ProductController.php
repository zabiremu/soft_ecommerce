<?php

namespace App\Http\Controllers\api\v1;

use App\CPU\CategoryManager;
use App\CPU\Helpers;
use App\CPU\ImageManager;
use App\CPU\ProductManager;
use App\Http\Controllers\Controller;
use App\Model\Banner;
use App\Model\Brand;
use App\Model\Category;
use App\Model\DealOfTheDay;
use App\Model\MostDemanded;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Review;
use App\Model\Seller;
use App\Model\ShippingMethod;
use App\Model\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use function App\CPU\translate;

class ProductController extends Controller
{
    public function __construct(
        private Product      $product,
        private Order        $order,
        private MostDemanded $most_demanded,
    ){}

    public function get_latest_products(Request $request)
    {
        $products = ProductManager::get_latest_products($request, $request['limit'], $request['offset']);
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    public function get_featured_products(Request $request)
    {
        $products = ProductManager::get_featured_products($request, $request['limit'], $request['offset']);
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    public function get_top_rated_products(Request $request)
    {
        $products = ProductManager::get_top_rated_products($request, $request['limit'], $request['offset']);
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    public function get_searched_products(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $products = ProductManager::search_products($request, $request['name'], 'all', $request['limit'], $request['offset']);
        if ($products['products'] == null) {
            $products = ProductManager::translated_product_search($request['name'], 'all', $request['limit'], $request['offset']);
        }
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    public function product_filter(Request $request)
    {
        $search = [base64_decode($request->search)];
        $categories =  json_decode($request->category);
        $brand = json_decode($request->brand);


        // products search
        $products = Product::active()->with(['rating','tags'])
            ->where(function ($query) use ($search) {
                foreach ($search as $value) {
                    $query->orWhere('name', 'like', "%{$value}%")
                    ->orWhereHas('tags',function($query)use($search){
                        $query->where(function($q)use($search){
                            foreach ($search as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            }
                        });
                    });
                }
            })
            ->when($request->has('brand') && count($brand)>0, function($query) use($request, $brand){
                return $query->whereIn('brand_id', $brand);
            })
            ->when($request->has('category') && count($categories)>0, function($query) use($categories){
                return $query->whereIn('category_id', $categories)
                    ->orWhereIn('sub_category_id', $categories)
                    ->orWhereIn('sub_sub_category_id', $categories);
            })
            ->when($request->has('sort_by') && !empty($request->sort_by), function($query) use($request){
                $query->when($request['sort_by'] == 'low-high', function($query){
                    return $query->orderBy('unit_price', 'ASC');
                })
                    ->when($request['sort_by'] == 'high-low', function($query){
                        return $query->orderBy('unit_price', 'DESC');
                    })
                    ->when($request['sort_by'] == 'a-z', function($query){
                        return $query->orderBy('name', 'ASC');
                    })
                    ->when($request['sort_by'] == 'z-a', function($query){
                        return $query->orderBy('name', 'DESC');
                    })
                    ->when($request['sort_by'] == 'latest', function($query){
                        return $query->latest();
                    });
            })
            ->when(!empty($request['price_min']) || !empty($request['price_max']), function($query) use($request){
                return $query->whereBetween('unit_price', [$request['price_min'], $request['price_max']]);
            });

        $products = $products->paginate($request['limit'], ['*'], 'page', $request['offset']);

        return [
            'total_size' => $products->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'products' => Helpers::product_data_formatting($products->items(),true)
        ];
    }

    public function get_suggestion_product(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $products = ProductManager::search_products($request, $request['name'], 'all', $request['limit'], $request['offset']);
        if ($products['products'] == null) {
            $products = ProductManager::translated_product_search($request['name'], 'all', $request['limit'], $request['offset']);
        }

        $products_array = [];
        if($products['products']){
            foreach($products['products'] as $product){
                $products_array[] = [
                    'id'=>$product->id,
                    'name'=>$product->name,
                ];
            }
        }

        return response()->json(['products'=>$products_array], 200);
    }

    public function get_product(Request $request, $slug)
    {
        $user = Helpers::get_customer($request);

        $product = Product::with(['reviews.customer', 'seller.shop','tags'])
            ->withCount(['wish_list' => function($query) use($user){
                $query->where('customer_id', $user != 'offline' ? $user->id : '0');
            }])
            ->where(['slug' => $slug])->first();

        if (isset($product)) {
            $product = Helpers::product_data_formatting($product, false);

            if(isset($product->reviews) && !empty($product->reviews)){
                $overallRating = \App\CPU\ProductManager::get_overall_rating($product->reviews);
                $product['average_review'] = $overallRating[0];
            }else{
                $product['average_review'] = 0;
            }

            $temporary_close = Helpers::get_business_settings('temporary_close');
            $inhouse_vacation = Helpers::get_business_settings('vacation_add');
            $inhouse_vacation_start_date = $product['added_by'] == 'admin' ? $inhouse_vacation['vacation_start_date'] : null;
            $inhouse_vacation_end_date = $product['added_by'] == 'admin' ? $inhouse_vacation['vacation_end_date'] : null;
            $inhouse_temporary_close = $product['added_by'] == 'admin' ? $temporary_close['status'] : false;
            $product['inhouse_vacation_start_date'] = $inhouse_vacation_start_date;
            $product['inhouse_vacation_end_date'] = $inhouse_vacation_end_date;
            $product['inhouse_temporary_close'] = $inhouse_temporary_close;
        }
        return response()->json($product, 200);
    }

    public function get_best_sellings(Request $request)
    {
        $products = ProductManager::get_best_selling_products($request, $request['limit'], $request['offset']);
        $products['products'] = isset($products['products'][0]) ? Helpers::product_data_formatting($products['products'], true) : [];

        return response()->json($products, 200);
    }

    public function get_home_categories(Request $request)
    {
        $categories = Category::where('home_status', true)->get();
        $categories->map(function ($data) use($request) {
            $data['products'] = Helpers::product_data_formatting(CategoryManager::products($data['id'], $request), true);
            return $data;
        });
        return response()->json($categories, 200);
    }

    public function get_related_products(Request $request, $id)
    {
        if (Product::find($id)) {
            $products = ProductManager::get_related_products($id, $request);
            $products = Helpers::product_data_formatting($products, true);
            return response()->json($products, 200);
        }
        return response()->json([
            'errors' => ['code' => 'product-001', 'message' => translate('Product not found!')]
        ], 404);
    }

    public function get_product_reviews($id)
    {
        $reviews = Review::with(['customer'])->where(['product_id' => $id])->get();

        $storage = [];
        foreach ($reviews as $item) {
            $item['attachment'] = json_decode($item['attachment']);
            array_push($storage, $item);
        }

        return response()->json($storage, 200);
    }

    public function get_product_rating($id)
    {
        try {
            $product = Product::find($id);
            $overallRating = \App\CPU\ProductManager::get_overall_rating($product->reviews);
            return response()->json(floatval($overallRating[0]), 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }

    public function counter($product_id)
    {
        try {
            $countOrder = OrderDetail::where('product_id', $product_id)->count();
            $countWishlist = Wishlist::where('product_id', $product_id)->count();
            return response()->json(['order_count' => $countOrder, 'wishlist_count' => $countWishlist], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }

    public function social_share_link($product_slug)
    {
        $product = Product::where('slug', $product_slug)->first();
        $link = route('product', $product->slug);
        try {

            return response()->json($link, 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }

    public function submit_product_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'comment' => 'required',
            'rating' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }


        $image_array = [];
        if (!empty($request->file('fileUpload'))) {
            foreach ($request->file('fileUpload') as $image) {
                if ($image != null) {
                    array_push($image_array, ImageManager::upload('review/', 'webp', $image));
                }
            }
        }

        Review::updateOrCreate(
            [
                'delivery_man_id'=> null,
                'customer_id'=>$request->user()->id,
                'product_id'=>$request->product_id
            ],
            [
                'customer_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'comment' => $request->comment,
                'rating' => $request->rating,
                'attachment' => json_encode($image_array),
            ]
        );

        return response()->json(['message' => translate('successfully review submitted!')], 200);
    }

    public function submit_deliveryman_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'comment' => 'required',
            'rating' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $order = Order::where([
                'id'=>$request->order_id,
                'customer_id'=>$request->user()->id,
                'payment_status'=>'paid'])->first();

        if(!isset($order->delivery_man_id)){
            return response()->json(['message' => translate('Invalid review!')], 403);
        }

        Review::updateOrCreate(
            [
                'delivery_man_id'=>$order->delivery_man_id,
                'customer_id'=>$request->user()->id,
                'order_id' => $order->id
            ],
            [
                'customer_id' => $request->user()->id,
                'order_id' => $order->id,
                'delivery_man_id' => $order->delivery_man_id,
                'comment' => $request->comment,
                'rating' => $request->rating,
            ]
        );

        return response()->json(['message' => translate('successfully review submitted!')], 200);
    }

    public function get_shipping_methods(Request $request)
    {
        $methods = ShippingMethod::where(['status' => 1])->get();
        return response()->json($methods, 200);
    }

    public function get_discounted_product(Request $request)
    {
        $products = ProductManager::get_discounted_product($request, $request['limit'], $request['offset']);
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    public function get_most_demanded_product(Request $request)
    {
        $user = Helpers::get_customer($request);
        // Most demanded product
        $products = MostDemanded::where('status',1)->with(['product'=>function($query) use($user){
            $query->withCount(['order_details','order_delivered','reviews','wish_list'=>function($query) use($user){
                $query->where('customer_id', $user != 'offline' ? $user->id : '0');
            }]);
        }])->whereHas('product', function ($query){
            return $query->active();
        })->first();

        if($products)
        {
            $products['banner'] = $products->banner ?? '';
            $products['product_id'] = $products->product['id'] ?? 0;
            $products['slug'] = $products->product['slug'] ?? '';
            $products['review_count'] = $products->product['reviews_count'] ?? 0;
            $products['order_count'] = $products->product['order_details_count'] ?? 0;
            $products['delivery_count'] = $products->product['order_delivered_count'] ?? 0;
            $products['wishlist_count'] = $products->product['wish_list_count'] ?? 0;

            unset($products->product['category_ids']);
            unset($products->product['images']);
            unset($products->product['details']);
            unset($products->product);
        }else{
            $products = [];
        }

        return response()->json($products, 200);
    }

    public function get_shop_again_product(Request $request)
    {
        $user = Helpers::get_customer($request);
        if($user != 'offline') {
            $products = Product::active()->with('seller.shop')
                ->withCount(['wish_list' => function($query) use($user){
                    $query->where('customer_id', $user != 'offline' ? $user->id : '0');
                }])
                ->whereHas('seller.orders', function ($query) use($request) {
                    $query->where(['customer_id' => $request->user()->id, 'seller_is' => 'seller']);
                })
                ->select('id','name','slug','thumbnail','unit_price','purchase_price','added_by','user_id')
                ->inRandomOrder()->take(12)->get();

            unset($products['reviews']);
        }else{
            $products = [];
        }


        return response()->json($products, 200);
    }

    public function just_for_you(Request $request)
    {
        $user = Helpers::get_customer($request);
        if($user != 'offline') {
            $orders = $this->order->where(['customer_id' => $user->id])->with(['details'])->get();

            if ($orders) {
                $orders = $orders?->map(function ($order) {
                    $order_details = $order->details->map(function ($detail) {
                        $product = json_decode($detail->product_details);
                        $category = json_decode($product->category_ids)[0]->id;
                        $detail['category_id'] = $category;
                        return $detail;
                    });
                    $order['id'] = $order_details[0]->id;
                    $order['category_id'] = $order_details[0]->category_id;

                    return $order;
                });

                $categories = [];
                foreach ($orders as $order) {
                    $categories[] = ($order['category_id']);;
                }
                $ids = array_unique($categories);


                $just_for_you = $this->product->with([
                        'compare_list'=>function($query) use($user){
                            return $query->where('user_id', $user != 'offline' ? $user->id : 0);
                        }
                    ])
                    ->withCount(['wish_list' => function($query) use($user){
                        $query->where('customer_id', $user != 'offline' ? $user->id : '0');
                    }])
                    ->active()
                    ->where(function ($query) use ($ids) {
                        foreach ($ids as $id) {
                            $query->orWhere('category_ids', 'like', "%{$id}%");
                        }
                    })
                    ->inRandomOrder()
                    ->take(8)
                    ->get();
            } else {
                $just_for_you = $this->product->with([
                        'compare_list'=>function($query) use($user){
                            return $query->where('user_id', $user != 'offline' ? $user->id : 0);
                        }
                    ])
                    ->withCount(['wish_list' => function($query) use($user){
                        $query->where('customer_id', $user != 'offline' ? $user->id : '0');
                    }])
                    ->active()
                    ->inRandomOrder()
                    ->take(8)
                    ->get();
            }
        } else {
            $just_for_you = $this->product->with([
                    'compare_list'=>function($query) use($user){
                        return $query->where('user_id', $user != 'offline' ? $user->id : 0);
                    }
                ])
                ->withCount(['wish_list' => function($query) use($user){
                    $query->where('customer_id', $user != 'offline' ? $user->id : '0');
                }])
                ->active()
                ->inRandomOrder()
                ->take(8)
                ->get();
        }

        $products = Helpers::product_data_formatting($just_for_you, true);

        return response()->json($products, 200);
    }

    public function get_most_searching_products(Request $request)
    {
        $products = ProductManager::get_best_selling_products($request, $request['limit'], $request['offset']);
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }
}
