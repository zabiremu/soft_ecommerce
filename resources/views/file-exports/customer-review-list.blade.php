<html>
    <table>
        <thead>
            <tr>
                <th style="font-size:18px">{{translate('customer_Reviews')}}</th>
            </tr>
            <tr>

                <th>{{ translate('search_Criteria') }} -</th>
                <th></th>
                <th>
                    {{translate('search_Bar_Content')}} - {{!empty($data['key']) ?  ucwords($data['key']) : 'N/A'}}
                    <br>
                        {{translate('product')}} - {{ucwords($data['product_name'] == 'all_products' ? translate('all_Products') : $data['product_name'])}}
                    <br>
                        {{translate('customer')}} - {{ucwords($data['customer_name'] == 'all_customers' ? translate('all_Customers') : $data['customer_name']['f_name'].' '.$data['customer_name']['l_name'])}}
                    <br>
                         {{translate('status')}} - {{translate(!empty($data['status']) ? ($data['status'] == 1 ? 'active' : 'inactive') : 'all_status')}}
                    <br>
                        {{translate('from')}} - {{$data['from'] ?   date('d M, Y',strtotime($data['from'])) : '' }}
                    <br>
                        {{translate('to')}} - {{ $data['to'] ? date('d M, Y',strtotime($data['to'])) : ''}}
                    <br>
                </th>
            </tr>
            <tr>
                <td> {{translate('SL')}}	</td>
                <td> {{translate('product_Name')}}	</td>
                <td> {{translate('customer_Name')}}	</td>
                <td> {{translate('store_Name')}}	</td>
                <td> {{translate('item_Price')}}	</td>
                <td> {{translate('rating')}}</td>
                <td> {{translate('review')}}</td>
            </tr>
            <!-- loop  you data -->
            @foreach ($data['reviews'] as $key=>$item)
                <tr >
                    <td > {{++$key}}	</td>
                    <td> {{$item?->product?->name ?? translate('product_not_found')}}	</td>
                    <td>{{ucwords(($item->customer?->f_name ?? translate('customer_not_found')).' '.$item->customer?->l_name)}}</td>
                    <td> {{ucwords($item?->product?->seller?->shop->name ?? translate('store_not_found'))}}	</td>
                    <td> {{$item?->product ? \App\CPU\Helpers::currency_converter($item?->product->unit_price) : translate('not_found')}}</td>
                    <td> {{$item?->rating}}</td>
                    <td> {{$item?->comment}}</td>
                </tr>
            @endforeach
            <!-- end -->
        </thead>
    </table>
</html>
