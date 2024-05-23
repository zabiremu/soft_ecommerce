@extends('layouts.back-end.app')

@section('title',$seller->shop ? $seller->shop->name : translate("shop_name_not_found"))

@section('content')
    <div class="content container-fluid">
        <!-- Page Title -->
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex gap-2 align-items-center">
                <img src="{{asset('/public/assets/back-end/img/add-new-seller.png')}}" alt="">
                {{translate('seller_details')}}
            </h2>
        </div>
        <!-- End Page Title -->

        <!-- Page Heading -->
        <div class="flex-between d-sm-flex row align-items-center justify-content-between mb-2 mx-1">
            <div>
                @if ($seller->status=="pending")
                    <div class="mt-4 pr-2">
                        <div class="flex-between">
                            <div class="mx-1"><h4><i class="tio-shop-outlined"></i></h4></div>
                            <div><h4>{{translate('seller_request_for_open_a_shop.')}}</h4></div>
                        </div>
                        <div class="text-center">
                            <form class="d-inline-block" action="{{route('admin.sellers.updateStatus')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller->id}}">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit"
                                        class="btn btn--primary btn-sm">{{translate('approve')}}</button>
                            </form>
                            <form class="d-inline-block" action="{{route('admin.sellers.updateStatus')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller->id}}">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit"
                                        class="btn btn-danger btn-sm">{{translate('reject')}}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <!-- Page Header -->
        <div class="page-header">
            <div class="flex-between row mx-1">
                <div>
                    <h1 class="page-header-title">{{ $seller->shop ? $seller->shop->name : translate("shop_Name")." : ".translate("update_Please") }}</h1>
                </div>
            </div>
            <!-- Nav Scroller -->
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                <!-- Nav -->
                <ul class="nav nav-tabs flex-wrap page-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link "
                           href="{{ route('admin.sellers.view',$seller->id) }}">{{translate('shop')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="{{ route('admin.sellers.view',['id'=>$seller->id, 'tab'=>'order']) }}">{{translate('order')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="{{ route('admin.sellers.view',['id'=>$seller->id, 'tab'=>'product']) }}">{{translate('product')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="{{ route('admin.sellers.view',['id'=>$seller->id, 'tab'=>'setting']) }}">{{translate('setting')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="{{ route('admin.sellers.view',['id'=>$seller->id, 'tab'=>'transaction']) }}">{{translate('transaction')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active"
                           href="{{ route('admin.sellers.view',['id'=>$seller->id, 'tab'=>'review']) }}">{{translate('review')}}</a>
                    </li>
                </ul>
                <!-- End Nav -->
            </div>
            <!-- End Nav Scroller -->
        </div>
        <!-- End Page Header -->
        <div class="content container-fluid p-0">
            <!-- End Page Header -->
            <div class="row gx-2 gx-lg-3">
                <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                    <!-- Card -->
                    <div class="card">
                        <!-- Header -->
                        <div class="px-3 py-4">
                            <div class="row align-items-center">
                                <div class="col-sm-4 col-md-6 col-lg-8 mb-3 mb-sm-0">
                                    <h5 class="mb-0 d-flex gap-1 align-items-center">
                                        {{translate('review_table') }}
                                        <span
                                            class="badge badge-soft-dark radius-50 fz-12">{{ $reviews->total() }}</span>
                                    </h5>
                                </div>
                                <div class="col-sm-8 col-md-6 col-lg-4">
                                    <form action="{{ url()->current() }}" method="GET">
                                        <!-- Search -->
                                        <div class="input-group input-group-merge input-group-custom">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">
                                                    <i class="tio-search"></i>
                                                </div>
                                            </div>
                                            <input id="datatableSearch_" type="search" name="search"
                                                   class="form-control"
                                                   placeholder="{{translate('search_by_product_name')}}" aria-label="Search orders"
                                                   value="{{ $search }}">
                                            <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                                        </div>
                                        <!-- End Search -->
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- End Header -->

                        <!-- Table -->
                        <div class="table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                   style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};"
                                   class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                <thead class="thead-light thead-50 text-capitalize">
                                <tr>
                                    <th>{{translate('SL')}}</th>
                                    <th>{{translate('product')}}</th>
                                    <th>{{translate('review')}}</th>
                                    <th>{{translate('rating')}}</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($reviews as $key=>$review)
                                    <tr>
                                        <td>{{$reviews->firstItem()+ $key}}</td>
                                        <td>
                                        <span class="d-block font-size-sm text-body">
                                            <a href="{{route('admin.product.view',[$review->product['id']])}}"
                                                class="title-color hover-c1">
                                                {{$review->product?$review->product['name']: translate('product_not_found')}}
                                            </a>
                                        </span>
                                        </td>

                                        <td>
                                            <p class="text-wrap mb-1">
                                                {{$review->comment?$review->comment: translate("no_Comment_Found")}}
                                            </p>
                                            @if($review->attachment)
                                                @foreach (json_decode($review->attachment) as $img)
                                                    <a href="{{asset('storage/app/public/review')}}/{{$img}}"
                                                        data-lightbox="mygallery">
                                                        <img class="p-1" width="60" height="60"
                                                                src="{{asset('storage/app/public/review')}}/{{$img}}"
                                                                alt="" onerror="this.src='{{asset('public/assets/back-end/img/image-place-holder.png')}}'">
                                                    </a>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            <label class="mb-0 badge badge-soft-info">
                                                {{$review->rating}} <i class="tio-star"></i>
                                            </label>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                        </div>
                        <!-- End Table -->

                        <div class="table-responsive mt-4">
                            <div class="px-4 d-flex justify-content-lg-end">
                                <!-- Pagination -->
                                {{$reviews->links()}}
                            </div>
                        </div>

                        @if(count($reviews)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-160"
                                     src="{{asset('public/assets/back-end')}}/svg/illustrations/sorry.svg"
                                     alt="Image Description">
                                <p class="mb-0">{{ translate('no_data_to_show')}}</p>
                            </div>
                    @endif
                    <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush
