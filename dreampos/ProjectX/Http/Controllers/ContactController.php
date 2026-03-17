<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Contact;
use App\CustomerGroup;
use App\Events\ContactCreatedOrModified;
use App\Transaction;
use App\User;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    protected $contactUtil;

    protected $commonUtil;

    protected $moduleUtil;

    public function __construct(ContactUtil $contactUtil, Util $commonUtil, ModuleUtil $moduleUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display contacts list (supplier/customer). Uses root contact data.
     */
    public function index(Request $request)
    {
        if (! auth()->user()->can('supplier.view') && ! auth()->user()->can('customer.view') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $business_id = $request->session()->get('user.business_id');
        $type = $request->get('type', 'customer');
        $types = ['supplier', 'customer'];
        if (! in_array($type, $types)) {
            $type = 'customer';
        }

        $query = Contact::where('business_id', $business_id);
        if ($type === 'supplier') {
            $query->whereIn('type', ['supplier', 'both']);
        } else {
            $query->whereIn('type', ['customer', 'both']);
        }
        if (! auth()->user()->can('supplier.view') && auth()->user()->can('supplier.view_own')) {
            $query->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereHas('userHavingAccess', fn ($q2) => $q2->where('user_id', auth()->id()));
            });
        }
        if (! auth()->user()->can('customer.view') && auth()->user()->can('customer.view_own')) {
            $query->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereHas('userHavingAccess', fn ($q2) => $q2->where('user_id', auth()->id()));
            });
        }
        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('supplier_business_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%")
                    ->orWhere('landline', 'like', "%{$term}%")
                    ->orWhere('alternate_number', 'like', "%{$term}%")
                    ->orWhere('contact_id', 'like', "%{$term}%");
            });
        }
        $contacts = $query->orderBy('name')->limit(100)->get();

        $customer_groups = [];
        if ($type === 'customer') {
            $customer_groups = CustomerGroup::forDropdown($business_id);
        }
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];

        $contactsCount = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'supplier', 'both'])->count();
        $customerCount = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'both'])->count();
        $supplierCount = Contact::where('business_id', $business_id)->whereIn('type', ['supplier', 'both'])->count();

        return view('projectx::contacts.index', compact('type', 'contacts', 'customer_groups', 'users', 'contactsCount', 'customerCount', 'supplierCount'));
    }

    /**
     * Show create contact form.
     */
    public function create(Request $request)
    {
        if (! auth()->user()->can('supplier.create') && ! auth()->user()->can('customer.create') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $business_id = $request->session()->get('user.business_id');
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $types = [];
        if (auth()->user()->can('supplier.create') || auth()->user()->can('supplier.view_own')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create') || auth()->user()->can('customer.view_own')) {
            $types['customer'] = __('report.customer');
        }
        if ((auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) || auth()->user()->can('supplier.view_own') || auth()->user()->can('customer.view_own')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }

        $customer_groups = CustomerGroup::forDropdown($business_id);
        $selected_type = $request->get('type', 'customer');
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];

        return view('projectx::contacts.create', compact('types', 'customer_groups', 'selected_type', 'users'));
    }

    /**
     * Store new contact. Delegates to root ContactUtil.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('supplier.create') && ! auth()->user()->can('customer.create') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $business_id = $request->session()->get('user.business_id');
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $input = $request->only([
            'type', 'supplier_business_name', 'prefix', 'first_name', 'middle_name', 'last_name', 'tax_number',
            'pay_term_number', 'pay_term_type', 'mobile', 'landline', 'alternate_number', 'city', 'state', 'country',
            'address_line_1', 'address_line_2', 'customer_group_id', 'zip_code', 'contact_id',
            'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'custom_field5',
            'custom_field6', 'custom_field7', 'custom_field8', 'custom_field9', 'custom_field10',
            'email', 'shipping_address', 'position', 'dob', 'shipping_custom_field_details',
            'assigned_to_users', 'land_mark', 'street_name', 'building_number', 'additional_number',
        ]);

        $input['contact_type'] = $request->input('contact_type_radio', 'individual');
        if (! empty($input['dob'])) {
            $input['dob'] = $this->commonUtil->uf_date($input['dob']);
        }
        $input['business_id'] = $business_id;
        $input['created_by'] = $request->session()->get('user.id');
        $input['credit_limit'] = $request->input('credit_limit') !== '' && $request->input('credit_limit') !== null ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;
        $input['opening_balance'] = $this->commonUtil->num_uf($request->input('opening_balance', 0));

        if (! empty($request->input('name')) && empty($input['first_name']) && empty($input['last_name'])) {
            $input['first_name'] = $request->input('name');
        }
        $name_array = array_filter([$input['prefix'] ?? '', $input['first_name'] ?? '', $input['middle_name'] ?? '', $input['last_name'] ?? '']);
        $input['name'] = trim(implode(' ', $name_array)) ?: trim($request->input('name', ''));

        try {
            DB::beginTransaction();
            $output = $this->contactUtil->createNewContact($input);
            event(new ContactCreatedOrModified($input, 'added'));
            $this->contactUtil->activityLog($output['data'], 'added');
            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'msg' => $output['msg'], 'redirect' => route('projectx.contacts.show', $output['data']->id)]);
            }

            return redirect()->route('projectx.contacts.show', $output['data']->id)->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
            }

            return redirect()->back()->withInput()->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show single contact. Uses root ContactUtil::getContactInfo.
     */
    public function show($id)
    {
        if (! auth()->user()->can('supplier.view') && ! auth()->user()->can('customer.view') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $business_id = request()->session()->get('user.business_id');
        $contact = $this->contactUtil->getContactInfo($business_id, $id);
        if (! $contact) {
            abort(404);
        }

        $is_selected = User::isSelectedContacts(auth()->user()->id);
        $user_contacts = $is_selected ? auth()->user()->contactAccess->pluck('id')->toArray() : [];
        if (! auth()->user()->can('supplier.view') && auth()->user()->can('supplier.view_own')) {
            if ($contact->created_by != auth()->id() && ! in_array($contact->id, $user_contacts)) {
                abort(403, __('messages.unauthorized_action'));
            }
        }
        if (! auth()->user()->can('customer.view') && auth()->user()->can('customer.view_own')) {
            if ($contact->created_by != auth()->id() && ! in_array($contact->id, $user_contacts)) {
                abort(403, __('messages.unauthorized_action'));
            }
        }

        $showType = $contact->type === 'both' ? 'customer' : $contact->type;
        $showContacts = Contact::where('business_id', $business_id)
            ->whereIn('type', $contact->type === 'both' ? ['customer', 'supplier', 'both'] : [$contact->type, 'both'])
            ->orderBy('name')
            ->limit(50)
            ->get();
        $customer_groups = CustomerGroup::forDropdown($business_id);
        $contactsCount = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'supplier', 'both'])->count();
        $customerCount = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'both'])->count();
        $supplierCount = Contact::where('business_id', $business_id)->whereIn('type', ['supplier', 'both'])->count();

        return view('projectx::contacts.show', compact('contact', 'showContacts', 'showType', 'customer_groups', 'contactsCount', 'customerCount', 'supplierCount'));
    }

    /**
     * Show edit contact form.
     */
    public function edit($id)
    {
        if (! auth()->user()->can('supplier.update') && ! auth()->user()->can('customer.update') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $business_id = request()->session()->get('user.business_id');
        $contact = Contact::where('business_id', $business_id)->findOrFail($id);

        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }

        $customer_groups = CustomerGroup::forDropdown($business_id);
        $ob_transaction = Transaction::where('contact_id', $id)->where('type', 'opening_balance')->first();
        $opening_balance = ! empty($ob_transaction->final_total) ? $ob_transaction->final_total : 0;
        if (! empty($opening_balance) && ! empty($ob_transaction)) {
            $transactionUtil = app(\App\Utils\TransactionUtil::class);
            $paid = $transactionUtil->getTotalAmountPaid($ob_transaction->id);
            if (! empty($paid)) {
                $opening_balance = $opening_balance - $paid;
            }
            $opening_balance = $this->commonUtil->num_f($opening_balance);
        }
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];

        $editContacts = Contact::where('business_id', $business_id)
            ->whereIn('type', $contact->type === 'both' ? ['customer', 'supplier', 'both'] : [$contact->type, 'both'])
            ->orderBy('name')
            ->limit(50)
            ->get();

        $contactsCount = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'supplier', 'both'])->count();
        $customerCount = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'both'])->count();
        $supplierCount = Contact::where('business_id', $business_id)->whereIn('type', ['supplier', 'both'])->count();

        return view('projectx::contacts.edit', compact('contact', 'types', 'customer_groups', 'opening_balance', 'users', 'editContacts', 'contactsCount', 'customerCount', 'supplierCount'));
    }

    /**
     * Update contact. Delegates to root ContactUtil.
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('supplier.update') && ! auth()->user()->can('customer.update') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $business_id = $request->session()->get('user.business_id');
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $input = $request->only([
            'type', 'supplier_business_name', 'prefix', 'first_name', 'middle_name', 'last_name', 'tax_number',
            'pay_term_number', 'pay_term_type', 'mobile', 'address_line_1', 'address_line_2', 'zip_code', 'dob',
            'alternate_number', 'city', 'state', 'country', 'landline', 'customer_group_id', 'contact_id',
            'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'custom_field5',
            'custom_field6', 'custom_field7', 'custom_field8', 'custom_field9', 'custom_field10',
            'email', 'shipping_address', 'position', 'shipping_custom_field_details',
            'assigned_to_users', 'land_mark', 'street_name', 'building_number', 'additional_number',
        ]);

        if (! empty($request->input('name')) && empty($input['first_name']) && empty($input['last_name'])) {
            $input['first_name'] = $request->input('name');
        }
        $name_array = array_filter([$input['prefix'] ?? '', $input['first_name'] ?? '', $input['middle_name'] ?? '', $input['last_name'] ?? '']);
        $input['name'] = trim(implode(' ', $name_array)) ?: trim($request->input('name', ''));
        if (! empty($input['dob'])) {
            $input['dob'] = $this->commonUtil->uf_date($input['dob']);
        }
        $input['credit_limit'] = $request->input('credit_limit') !== '' && $request->input('credit_limit') !== null ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;
        $input['opening_balance'] = $this->commonUtil->num_uf($request->input('opening_balance', 0));

        try {
            $output = $this->contactUtil->updateContact($input, $id, $business_id);
            event(new ContactCreatedOrModified($output['data'], 'updated'));
            $this->contactUtil->activityLog($output['data'], 'edited');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'msg' => $output['msg'], 'redirect' => route('projectx.contacts.show', $id)]);
            }

            return redirect()->route('projectx.contacts.show', $id)->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
            }

            return redirect()->back()->withInput()->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Delete contact. Same rules as root (no transactions, not default).
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('supplier.delete') && ! auth()->user()->can('customer.delete') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, __('messages.unauthorized_action'));
        }

        if (! request()->ajax() && ! request()->wantsJson()) {
            abort(404);
        }

        try {
            $business_id = request()->user()->business_id;
            $count = Transaction::where('business_id', $business_id)->where('contact_id', $id)->count();
            if ($count > 0) {
                return response()->json(['success' => false, 'msg' => __('lang_v1.you_cannot_delete_this_contact')]);
            }
            $contact = Contact::where('business_id', $business_id)->findOrFail($id);
            if ($contact->is_default) {
                return response()->json(['success' => false, 'msg' => __('lang_v1.you_cannot_delete_this_contact')]);
            }
            $this->contactUtil->activityLog($contact, 'contact_deleted', ['id' => $contact->id, 'name' => $contact->name, 'supplier_business_name' => $contact->supplier_business_name]);
            User::where('crm_contact_id', $contact->id)->update(['allow_login' => 0]);
            $contact->delete();
            event(new ContactCreatedOrModified($contact, 'deleted'));

            return response()->json(['success' => true, 'msg' => __('contact.deleted_success'), 'redirect' => route('projectx.contacts.index')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }
}
