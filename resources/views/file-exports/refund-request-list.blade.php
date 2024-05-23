<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{translate('refund_Order_List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('filter_Criteria') }} -</th>
                <th></th>
                <th>
                    {{translate('refund_Status')}} - {{translate($data['status'])}}
                    <br>
                    {{translate('search_Bar_Content')}} - {{!empty($data['search']) ?  ucwords($data['search']) : 'N/A'}}
                    <br>
                    {{ucwords(translate('total'.' '.$data['status'].' '.'refund_Requests'))}} - {{count($data['refund_list'])}}
                    <br>
                    {{translate('filter_By')}} - {{ucwords(translate($data['filter_By']))}}
                </th>
            </tr>
            <tr>
                <td> {{translate('SL')}}	</td>
                <td> {{translate('order_ID')}}	</td>
                <td> {{translate('order_Date')}}</td>
                <td> {{translate('product_Infotmation')}}	</td>
                <td> {{translate('product_Amount')}}</td>
                <td> {{translate('Refund_Amount')}}</td>
                <td> {{translate('customer_Name')}}	</td>
                <td> {{translate('store_Name')}}</td>
                <td> {{translate('delivery_Name')}}</td>
                <td> {{translate('refund_Reason')}}</td>
            </tr>
            <!-- loop  you data -->
            @foreach ($data['refund_list'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td> {{$item->order_id}}</td>
                    <td> {{date('d M, Y h:i A',strtotime($item->order->created_at))}}</td>
                    <td>
                        {{$item?->product?->name ?? translate('product_not_found')}}
                        <br>
                        {{translate('qty') .'-'. $item?->order_details->qty}}
                    </td>
                    <td> {{$item?->product?->unit_price ? \App\CPU\Helpers::currency_converter($item?->product?->unit_price-\App\CPU\Helpers::get_product_discount($item?->product,$item?->product?->unit_price)) : 0}}	</td>
                    <td> {{\App\CPU\Helpers::currency_converter($item->amount)}}</td>
                    <td> {{ucwords(($item?->customer?->f_name ?? translate('not_found')) .' '. $item?->customer?->l_name)}}	</td>
                    <td>
                        {{ucwords($item?->order?->seller_is == 'seller' ? ($item?->order?->seller?->shop?->name ?? translate('not_found')) : translate('inhouse'))}}	</td>
                    <td> {{ucwords($item?->order?->delivery_type == 'self_delivery' ? ($item?->order?->delivery_man?->f_name ?? translate('not_found').' '.$item?->order?->delivery_man?->l_name) : ($item?->order?->delivery_service_name ??translate('not_found')."\n".$item?->order?->third_party_delivery_tracking_id ?? translate('not_found')))}}	</td>
                    <td> {{translate($item->refund_reason)}}</td>
                </tr>
            @endforeach
            <!-- end -->
        </thead>
    </table>
</html>
