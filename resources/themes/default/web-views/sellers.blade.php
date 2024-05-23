@extends('layouts.front-end.app')

@section('title', translate('All_Seller_Page'))

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Brands of {{$web_config['name']->value}} "/>
    <meta property="og:url" content="{{env('APP_URL')}}">
    <meta property="og:description" content="{{ substr(strip_tags(str_replace('&nbsp;', ' ', $web_config['about']->value)),0,160) }}">

    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Brands of {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}">
    <meta property="twitter:description" content="{{ substr(strip_tags(str_replace('&nbsp;', ' ', $web_config['about']->value)),0,160) }}">
    <style>
        .page-item.active .page-link {
            background-color: {{$web_config['primary_color']}}    !important;
        }
        /**/
    </style>
@endpush

@section('content')

    <!-- Page Content-->
    <div class="container mb-md-4 {{Session::get('direction') === "rtl" ? 'rtl' : ''}} __inline-65">
        <div class="bg-primary-light rounded-10 my-4 p-3 p-sm-4" data-bg-img="{{ asset('public/assets/front-end/img/media/bg.png') }}">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8 col-md-6">
                    <div class="d-flex flex-column gap-1 text-primary">
                        <h4 class="mb-0 text-start fw-bold text-primary text-uppercase">{{ translate('all_Stores') }}</h4>
                        <p class="fs-14 fw-semibold mb-0">{{translate('Find your desired stores and shop your favourite products')}}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <form action="{{route('search-shop')}}">
                        <div class="input-group">
                            <input type="text" class="form-control rounded-10" value="{{request('shop_name')}}"  placeholder="{{translate('Search_Store')}}" name="shop_name">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary rounded-10" type="submit">{{translate('search')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Content  -->
            <section class="col-lg-12">
                <!-- Products grid-->
                <div class="row mx-n2 __min-h-200px">
                    @foreach ($sellers as $seller)
                        @php($current_date = date('Y-m-d'))
                        @php($start_date = date('Y-m-d', strtotime($seller['vacation_start_date'])))
                        @php($end_date = date('Y-m-d', strtotime($seller['vacation_end_date'])))

                        <div class="col-lg-3 col-md-6 col-sm-12 px-2 pb-4 text-center">
                            <a href="{{route('shopView',['id'=>$seller['id']])}}" class="others-store-card text-capitalize">
                                <div class="overflow-hidden other-store-banner">
                                    <img src="{{asset('storage/app/public/shop/banner/'.$seller->banner)}}"
                                         onerror="this.src='{{ asset('public/assets/front-end/img/seller-banner.png') }}'"
                                         class="w-100 h-100 object-cover" alt="">
                                </div>
                                <div class="name-area">
                                    <div class="position-relative">
                                        <div class="overflow-hidden other-store-logo rounded-full">
                                            <img class="rounded-full" onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                                 src="{{ asset('storage/app/public/shop/'.$seller->image)}}" alt="others-store">
                                        </div>
                                        <!-- Temporary Closed Store Status -->
                                        @if($seller->temporary_close || ($seller->vacation_status && ($current_date >= $seller->vacation_start_date) && ($current_date <= $seller->vacation_end_date)))
                                            <span class="temporary-closed position-absolute text-center rounded-full">
                                                <span>{{translate('closed_now')}}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="info pt-2">
                                        <h5 class="text-start">{{ $seller->name }}</h5>
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
                        </div>
                    @endforeach
                </div>

                <div class="row mx-n2">
                    <div class="col-md-12">
                        <center>
                            {{ $sellers->links() }}
                        </center>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('script')
@endpush
