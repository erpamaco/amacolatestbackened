<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalQuotationDetail;
use Illuminate\Http\Request;

class RentalQuotationDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $q_details = RentalQuotationDetail::all();
        return response()->json($q_details);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $quotation_detail = RentalQuotationDetail::create($request->all());
        return $quotation_detail;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RentalQuotationDetail  $RentalQuotationDetail
     * @return \Illuminate\Http\Response
     */
    public function show(RentalQuotationDetail $RentalQuotationDetail)
    {
        return response()->json($RentalQuotationDetail);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RentalQuotationDetail  $RentalQuotationDetail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RentalQuotationDetail $RentalQuotationDetail)
    {
        $quotation_detail = $RentalQuotationDetail->update($request->all());
        return $quotation_detail;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RentalQuotationDetail  $RentalQuotationDetail
     * @return \Illuminate\Http\Response
     */
    public function destroy(RentalQuotationDetail $RentalQuotationDetail)
    {
        RentalQuotationDetail::delete($RentalQuotationDetail);
    }
}
