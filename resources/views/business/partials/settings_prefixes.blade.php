<div class="pos-tab-content">
    <div class="card mb-5 mb-xl-10">
        <div class="card-body p-9">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[purchase]', __('lang_v1.purchase') . ':') !!}
                        {!! Form::text('ref_no_prefixes[purchase]', data_get($business->ref_no_prefixes, 'purchase', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[purchase_return]', __('lang_v1.purchase_return') . ':') !!}
                        {!! Form::text('ref_no_prefixes[purchase_return]', data_get($business->ref_no_prefixes, 'purchase_return', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[purchase_requisition]', __('lang_v1.purchase_requisition') . ':') !!}
                        {!! Form::text('ref_no_prefixes[purchase_requisition]', data_get($business->ref_no_prefixes, 'purchase_requisition', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[purchase_order]', __('lang_v1.purchase_order') . ':') !!}
                        {!! Form::text('ref_no_prefixes[purchase_order]', data_get($business->ref_no_prefixes, 'purchase_order', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[stock_transfer]', __('lang_v1.stock_transfer') . ':') !!}
                        {!! Form::text('ref_no_prefixes[stock_transfer]', data_get($business->ref_no_prefixes, 'stock_transfer', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[stock_adjustment]', __('stock_adjustment.stock_adjustment') . ':') !!}
                        {!! Form::text('ref_no_prefixes[stock_adjustment]', data_get($business->ref_no_prefixes, 'stock_adjustment', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[sell_return]', __('lang_v1.sell_return') . ':') !!}
                        {!! Form::text('ref_no_prefixes[sell_return]', data_get($business->ref_no_prefixes, 'sell_return', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[expense]', __('expense.expenses') . ':') !!}
                        {!! Form::text('ref_no_prefixes[expense]', data_get($business->ref_no_prefixes, 'expense', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[contacts]', __('contact.contacts') . ':') !!}
                        {!! Form::text('ref_no_prefixes[contacts]', data_get($business->ref_no_prefixes, 'contacts', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[purchase_payment]', __('lang_v1.purchase_payment') . ':') !!}
                        {!! Form::text('ref_no_prefixes[purchase_payment]', data_get($business->ref_no_prefixes, 'purchase_payment', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[sell_payment]', __('lang_v1.sell_payment') . ':') !!}
                        {!! Form::text('ref_no_prefixes[sell_payment]', data_get($business->ref_no_prefixes, 'sell_payment', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[expense_payment]', __('lang_v1.expense_payment') . ':') !!}
                        {!! Form::text('ref_no_prefixes[expense_payment]', data_get($business->ref_no_prefixes, 'expense_payment', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[business_location]', __('business.business_location') . ':') !!}
                        {!! Form::text('ref_no_prefixes[business_location]', data_get($business->ref_no_prefixes, 'business_location', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[username]', __('business.username') . ':') !!}
                        {!! Form::text('ref_no_prefixes[username]', data_get($business->ref_no_prefixes, 'username', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[subscription]', __('lang_v1.subscription_no') . ':') !!}
                        {!! Form::text('ref_no_prefixes[subscription]', data_get($business->ref_no_prefixes, 'subscription', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[draft]', __('sale.draft') . ':') !!}
                        {!! Form::text('ref_no_prefixes[draft]', data_get($business->ref_no_prefixes, 'draft', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('ref_no_prefixes[sales_order]', __('lang_v1.sales_order') . ':') !!}
                        {!! Form::text('ref_no_prefixes[sales_order]', data_get($business->ref_no_prefixes, 'sales_order', ''), ['class' => 'form-control']); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
