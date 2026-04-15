<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = \App\Models\Employee::orderBy('employee_id', 'asc')->get();
        return view('employees.index', compact('employees'));
    }


    public function edit($id)
    {
        $employee = \App\Models\Employee::findOrFail($id);
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, $id)
    {
        $employee = \App\Models\Employee::findOrFail($id);
        $employee->update([
            'name' => $request->name,
            'department' => $request->department,
        ]);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

}
