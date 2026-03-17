@foreach($employees as $employee_id => $employee_name)
    <option value="{{ $employee_id }}">{{ $employee_name }}</option>
@endforeach
