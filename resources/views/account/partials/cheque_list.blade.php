  @if($cheque_lists->count() > 0)
      @foreach ($cheque_lists as $item)
        <tr>
          <td>
            {!! Form::checkbox('select_cheques[]', $item->id, false, ['class' => 'input-icheck select_cheques']) !!}
          </td>
          <td>{{ $item->customer_name }}</td>
          <td>{{ $item->paid_on ? @format_date($item->paid_on) : '' }}</td>
          <td>{{ $item->cheque_number }}</td>
          <td>
            @if(!empty($item->cheque_date) && $item->cheque_date != '0000-00-00')
              {{ @format_date($item->cheque_date) }}
            @endif
          </td>
          <td>{{ $item->given_bank_name }}</td> <!-- This should display the Given Bank name -->
          <td class="one_cheque_amount" data-string="{{ $item->amount }}">
            {{ @num_format($item->amount) }}
          </td>
        </tr>
      @endforeach
    @else
      <tr>
        <td colspan="7" class="text-center">
          <p>@lang('account.no_item_found')</p>
        </td>
      </tr>
    @endif


<script>
    $('.select_cheques').change(function() {
        var $tr = $(this).closest('tr');
        var cheque_value = parseFloat($tr.find('.one_cheque_amount').data('string'));
        var cheque_id = $(this).val();
        var $pmt = $(this).closest('.payment-row');
        var $pmtAmt = $pmt.find('.payment-amount');
        
        
        totalChequeValue = 0;
        $('.select_cheques:checked').each(function() {
            var $tr = $(this).closest('tr');
            var cheque_value = parseFloat($tr.find('.one_cheque_amount').data('string'));
        
            totalChequeValue += cheque_value; // Sum up the cheque_value
          });
          $pmtAmt.val(totalChequeValue)
        
    });
         
</script>