<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{translate($data['type'] == 'earn' ? 'delivery_Man_Earnings' : 'delivery_Man_Order_List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('delivery_Man_Information') }} -</th>
                <th></th>
                <th>
                        {{translate('name')}} - {{$data['delivery_man']['f_name'].' '.$data['delivery_man']['l_name']}}
                        <br>
                        {{translate('rating')}} -  {{isset($data['delivery_man']?->rating[0]?->average) ? number_format($data['delivery_man']?->rating[0]?->average, 1) : 0 }}
                        <br>
                        {{translate('total_Order')}} - {{count($data['orders'])}}
                </th>
            </tr>


            <tr>
                @if ($data['type'] == 'earn')
                    <th>{{translate('earning_Analytics')}}-</th>
                    <th></th>
                    <th> {{translate('total_Earning')}} - {{ \App\CPU\BackEndHelper::set_symbol(\App\CPU\BackEndHelper::usd_to_currency($data['total_earn'])) }} </th>
                    <th></th>
                    <th> {{translate('withdrawable_Balance')}} - {{ \App\CPU\BackEndHelper::set_symbol(\App\CPU\BackEndHelper::usd_to_currency($data['withdrawable_balance'])) }}</th>
                    <th></th>
                    <th> {{translate('already_Withdrawn')}} - {{ \App\CPU\BackEndHelper::set_symbol(\App\CPU\BackEndHelper::usd_to_currency($data['delivery_man']?->wallet?->total_withdraw)) }}</th>

                @else
                    <th>{{translate('search_Criteria')}}-</th>
                    <th></th>
                    <th>  {{translate('search_Bar_Content')}} - {{!empty($data['search']) ? $data['search'] : 'N/A'}}</th>
                @endif
            </tr>
            <tr>
                <td> {{translate('SL')}}	</td>
                <td> {{translate('order_ID')}}</td>
                <td> {{translate('order_Date')}}</td>
                <td> {{translate('total_Item')}}</td>
                @if ($data['type'] == 'earn')
                    <td> {{translate('earnings')}}</td>
                @endif
                <td> {{translate('payment_status')}}</td>
                <td> {{translate('order_Status')}}</td>
            </tr>
            <!-- loop  you data -->
            @foreach ($data['orders'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td> {{$item->id}} </td>
                    <td> {{ date_format( $item->created_at, 'd M ,Y, h:i:s A') }} </td>
                    <td> {{$item->total_qty}} </td>
                    @if ($data['type'] == 'earn')
                        <td> {{ \App\CPU\BackEndHelper::set_symbol(\App\CPU\BackEndHelper::usd_to_currency($item->deliveryman_charge)) }}</td>
                    @endif
                    <td> {{translate($item->payment_status)}} </td>
                    <td> {{translate($item->order_status)}}</td>
                </tr>
            @endforeach
            <!-- end -->
        </thead>
    </table>
</html>
