<div class="container rtl pt-4 px-0 px-md-3">
    <div class="seller-card">
        <div class="card __shadow h-100">
            <div class="card-body">
                <div class="row d-flex justify-content-between">
                    <div class="seller-list-title">
                        <h5 class="font-bold m-0 text-capitalize">{{ translate('top_sellers')}}</h5>
                    </div>
                    <div class="seller-list-view-all">
                        <a class="text-capitalize view-all-text" style="color: {{$web_config['primary_color']}}!important"
                            href="{{route('sellers')}}">{{ translate('view_all')}}
                            <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left mr-1 ml-n1 mt-1 float-left' : 'right ml-1 mr-n1'}}"></i>
                        </a>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="others-store-slider owl-theme owl-carousel">
                        <!-- Others Store Card -->
                        @foreach ($top_sellers as $seller)
                            <a href="{{route('shopView',['id'=>$seller['id']])}}" class="others-store-card text-capitalize">
                                <div class="overflow-hidden other-store-banner">
                                    <img src="{{asset('storage/app/public/shop/banner/'.$seller->shop->banner)}}"
                                        onerror="this.src='{{ asset('/public/assets/front-end/img/seller-banner.png') }}'"
                                        class="w-100 h-100 object-cover" alt="">
                                </div>
                                <div class="name-area">
                                    <div class="position-relative">
                                        <div class="overflow-hidden other-store-logo rounded-full">
                                            <img class="rounded-full" onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                                src="{{ asset('storage/app/public/shop/'.$seller->shop->image)}}" alt="others-store">
                                        </div>
                                        <!-- Temporary Closed Store Status -->
                                        @if($seller->shop->temporary_close || ($seller->shop->vacation_status && ($current_date >= $seller->shop->vacation_start_date) && ($current_date <= $seller->shop->vacation_end_date)))
                                            <span class="temporary-closed position-absolute text-center rounded-full">
                                                <span>{{translate('closed_now')}}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="info pt-2">
                                        <h5 >{{ $seller->shop->name }}</h5>
                                        <div class="d-flex align-items-center">
                                            <h6 style="color:{{$web_config['primary_color']}}">{{number_format($seller->average_rating,1)}}</h6>
                                            <i class="tio-star text-star mx-1"></i>
                                            <small>{{ translate('rating') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-area">
                                    <div class="info-item">
                                        <h6 style="color:{{$web_config['primary_color']}}">{{$seller->review_count < 1000 ? $seller->review_count : number_format($seller->review_count/1000 , 1).'K'}}</h6>
                                        <span>{{ translate('reviews') }}</span>
                                    </div>
                                    <div class="info-item">
                                        <h6 style="color:{{$web_config['primary_color']}}">{{$seller->product_count < 1000 ? $seller->product_count : number_format($seller->product_count/1000 , 1).'K'}}</h6>
                                        <span>{{ translate('products') }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
