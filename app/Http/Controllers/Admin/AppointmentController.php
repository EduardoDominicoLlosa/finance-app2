<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $query = Appointment::query()
            ->when(request('status'), function ($query, $status) {
                return $query->where('status', AppointmentStatus::from($status));
            })
            ->latest();

        if (request()->has('exitPassCode')) {
            $query->where('exit_pass_code', request('exitPassCode'));
        }

        return $query->paginate()->through(function ($appointment) {
            return [
                'id' => $appointment->id,
                'start_time' => $appointment->start_time->format('Y-m-d h:i A'),
                'status' => [
                    'name' => $appointment->status->name,
                    'color' => $appointment->status->color(),
                ],
                'requestor_name' => $appointment->requestor_name,
                'description' => $appointment->description,
                'exit_pass_code' => $appointment->exit_pass_code,
                'transfer_location_from' => $appointment->transfer_location_from,
                'transfer_location_to' => $appointment->transfer_location_to,
                'item_condition' => $appointment->item_condition,
                'signature_path' => $appointment->signature_path,
                'finance_sig_path' => $appointment->finance_sig_path,
                'receive_sig_path' => $appointment->receive_sig_path,
                'incharge_sig_path' => $appointment->incharge_sig_path,
            ];
        });
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requestor_name' => 'required',
            'description' => 'required',
            'start_time' => 'required',
            'transfer_location_from' => 'required',
            'exit_pass_code' => 'required',
            'transfer_location_to' => 'required',
            'item_condition' => 'required',
            'signature' => 'image|max:2048',
            'finance_sig' => 'sometimes|image|max:2048|nullable',
            'receive_sig' => 'sometimes|image|max:2048|nullable',
            'incharge_sig' => 'sometimes|image|max:2048|nullable',
        ]);


        $appointment = new Appointment([
            'requestor_name' => $validated['requestor_name'],
            'start_time' => $validated['start_time'],
            'description' => $validated['description'],
            'status' => AppointmentStatus::SCHEDULED,
            'exit_pass_code' => $validated['exit_pass_code'],
            'transfer_location_from' => $validated['transfer_location_from'],
            'transfer_location_to' => $validated['transfer_location_to'],
            'item_condition' => $validated['item_condition'],
        ]);
    
        if ($request->hasFile('signature')) {
            $appointment->signature_path = $request->file('signature')->store('signatures/signature', 'public');
        }
        
        if ($request->hasFile('finance_sig')) { // Updated field name
            $appointment->finance_sig_path = $request->file('finance_sig')->store('signatures/finance_signature', 'public'); // Updated field name
        }
    
        if ($request->hasFile('receive_sig')) { // Updated field name
            $appointment->receive_sig_path = $request->file('receive_sig')->store('signatures/receiver_signature', 'public'); // Updated field name
        }
    
        if ($request->hasFile('incharge_sig')) { // Updated field name
            $appointment->incharge_sig_path = $request->file('incharge_sig')->store('signatures/incharge_signature', 'public'); // Updated field name
        }
    
        $appointment->save();
        
        return response()->json(['message' => 'success']);
    }
    
    public function update(Request $request, $id)

    {
        \Log::info($request->all());
        $validated = $request->validate([
            'requestor_name' => 'required',
            'description' => 'required',
            'start_time' => 'required',
            'transfer_location_from' => 'required',
            'transfer_location_to' => 'required',
            'item_condition' => 'required',
            'signature' => 'image|max:2048',
            'finance_sig' => 'sometimes|image|max:2048|nullable',
            'receive_sig' => 'sometimes|image|max:2048|nullable',
            'incharge_sig' => 'sometimes|image|max:2048|nullable',
        ]);
    
        $appointment = Appointment::findOrFail($id);
    
        $appointment->update([
            'requestor_name' => $validated['requestor_name'],
            'description' => $validated['description'],
            'start_time' => $validated['start_time'],
            'transfer_location_from' => $validated['transfer_location_from'],
            'transfer_location_to' => $validated['transfer_location_to'],
            'item_condition' => $validated['item_condition'],
        ]);
    
        if ($request->hasFile('signature')) {
            $appointment->signature_path = $request->file('signature')->store('signatures/signature', 'public');
        }
    
        if ($request->hasFile('finance_sig')) {
            $appointment->finance_sig_path = $request->file('finance_sig')->store('signatures/finance_signature', 'public');
        }
    
        if ($request->hasFile('receive_sig')) {
            $appointment->receive_sig_path = $request->file('receive_sig')->store('signatures/receiver_signature', 'public');
        }
    
        if ($request->hasFile('incharge_sig')) {
            $appointment->incharge_sig_path = $request->file('incharge_sig')->store('signatures/incharge_signature', 'public');
        }
    
        $appointment->save();
    
        return response()->json(['message' => 'Appointment updated successfully'], 200);
    }
    
public function edit(Appointment $appointment)
{
    return $appointment;
}
public function destroy(Appointment $appointment)
{
    // Delete the signature file
    Storage::disk('public')->delete($appointment->signature_path);
    
    // Delete the incharge signature file
    if ($appointment->incharge_sig_path) {
        Storage::disk('public')->delete($appointment->incharge_sig_path);
    }
    
    // Delete the finance signature file
    if ($appointment->finance_sig_path) {
        Storage::disk('public')->delete($appointment->finance_sig_path);
    }
    
    // Delete the receive signature file
    if ($appointment->receive_sig_path) {
        Storage::disk('public')->delete($appointment->receive_sig_path);
    }
    
    // Delete the appointment
    $appointment->delete();
    
    return response()->json(['success' => true], 200);
}
}
