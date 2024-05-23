<html>
    <table>
        <thead>
            <tr>
                <th>{{translate('seller_Withdraw_Request')}}</th>
            </tr>
            <tr>

                <th>{{ translate('seller_Withdraw_Analytics') }} -</th>
                <th></th>
                <th>
                        {{translate('total_Withdraw_Request')}} - {{count($data['withdraw_request'])}}
                        <br>
                        @if($data['filter']=='all')
                            {{translate('total_Pending')}} - {{$data['pending']}}
                            <br>
                            {{translate('total_Approved')}} - {{$data['approved']}}
                            <br>
                            {{translate('total_Denied')}} - {{$data['denied']}}
                        @endif
                </th>
            </tr>
            <tr>
                <th>{{translate('filter_Criteria')}}-</th>
                <th></th>
                <th> {{translate($data['filter'])}}</th>
            </tr>
            <tr>
                <td> {{translate('SL')}}	</td>
                <td> {{translate('amount')}}</td>
                <td> {{translate('name')}}</td>
                <td> {{translate('request_Time')}}</td>
                <td> {{translate('status')}}</td>
            </tr>
            <!-- loop  you data -->
            @foreach ($data['withdraw_request'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td> {{\App\CPU\BackEndHelper::set_symbol(\App\CPU\BackEndHelper::usd_to_currency($item['amount']))}} </td>
                    <td>{{ ucwords(($item?->seller?->f_name ?? translate('not_found')) . ' ' . $item?->seller?->l_name) }}</td>
                    <td> {{ date_format( $item->created_at, 'd M ,Y, h:i:s A') }} </td>
                    <td> {{ucwords($item->approved==0 ? 'pending' : ($item->approved==1 ? 'approved' : 'denied'))}}</td>
                </tr>
            @endforeach
            <!-- end -->
        </thead>
    </table>
</html>
