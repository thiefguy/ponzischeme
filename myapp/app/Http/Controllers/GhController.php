<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApplicationHelpers;
use Illuminate\Http\Request;
use Auth;
use App\Http\Requests\PhRequest;
use App\Http\Helpers\MyCustomException;
use App\DonationHelp;
use Session;
use App\DonationTransaction;
use App\Referral;
use App\ReferralBonus;

class GhController extends Controller
{
	public function __construct() {
		$this->middleware(['auth', 'customChecks']);
	}
    public function create() {
        $user = Auth::User();
        //get the bitcoin rate
        $btc = ApplicationHelpers::getCurrentBitcoinRate();
        //get all the pending phs
        $donations = DonationHelp::where('userID', $user->id)->where('phGh', 'ph')
            ->where('status', DonationHelp::$SLIP_CONFIRMED)
            ->whereRaw('created_at <= DATE_SUB(curDate(), INTERVAL 30 DAY)')->get();

        //get the bonuses
        //first check for the registration bonus
        $reg_bonus = 0;
        if($user->isBonusCollected == 0) {
            $reg_bonus = $user->bonusAmount;
        }
        //secondly check for the referral bonus
        $referrals = Referral::where('relatedReferrerUserID', $user->id)->get();
        $ref_bonus = 0;
        //TODO
        foreach($referrals as $referral) {
            //loop through donations
            foreach($referral->member->donations as $donation) {
                if($donation->phGh == 'gh') {
                    continue;
                }
                $data = array(); 
                $bonus = 0.1 * $donation->amount;
                if(strtolower($donation->status) == DonationHelp::$SLIP_CONFIRMED) {
                    $data['bonus'] = $bonus;
                    $ref_bonus += $bonus;
                }
            }
        }
        //get the total amount to be paid
        $yield_amount = 0.3 * $donation->amount;
        $mature_date = strtotime($donation->created_at) + (30 * 24 * 60 * 60);
       
    	return view('gh.create', compact('btc', 'donations'
            , 'yield_amount', 'ref_bonus', 'reg_bonus', 'mature_date'));
    }
    public function store(PhRequest $request, $donation_id) {
     	$user = Auth::user();
    	try {
    		ApplicationHelpers::checkForActivePh($user);
            $donation = DonationHelp::where(['userID' => $user->id
                , 'id' => '$donation_id'])->first();

            //secondly check for the referral bonus
        $referrals = Referral::where('relatedReferrerUserID', $user->id)->get();
        $ref_bonus = 0;
        //TODO
        foreach($referrals as $referral) {
            //loop through donations
            foreach($referral->member->donations as $donation) {
                if($donation->phGh == 'gh') {
                    continue;
                }
                $data = array(); 
                $bonus = 0.1 * $donation->amount;
                if(strtolower($donation->status) == DonationHelp::$SLIP_CONFIRMED) {
                    $data['bonus'] = $bonus;
                    $ref_bonus += $bonus;
                }
            }
        }
        //get the total amount to be paid
        $ph_amount = 0.3 * $donation->amount;
        $collections = array();
        
        //create a transaction request now
        if(count($donations) > 0) {
            foreach($donations as $donation){
                //create a pending collection
                $check = DonationHelp::where('userID', $user->id)->where('phGh', 'gh')
                    ->where('status', DonationHelp::$SLIP_PENDING)->get();
                //check if the collection already exists
                $collection = new DonationHelp();
                $collection->paymentType = strtolower($donation->paymentType);
                $colleciton->amount = ($ph_amount + $ref_bonus + $reg_bonus);
                $collection->phGh = 'gh';
                $collection->status = DonationHelp::$SLIP_CONFIRMED;   
                $collection->userID = $user->id;
                $collection->recordId = uniqid();
                $collection->save();

                $collections[] = $collection;
            }
        }
            Session::flash('flash_message', "Your Request was successful.   
                Please wait while you are matched.");
            return redirect()->back();
    	}catch(MyCustomException $ex) {
    		return redirect()->back()->withInput()->withErrors($ex->getMessage());
    	}
    }
    public function displayReceivedPayment(){
        $user = Auth::User();
        //query the transaction table
        $collections = DonationHelp::where(['userID' => $user->id
            , 'status'=> DonationHelp::$SLIP_MATCHED, 'phGh'=> 'gh'])->get();
        return view('gh/received_payment', compact('collections'));
    }
    public function confirmReceivedPayment(Request $request, $trans_id){
        $user = Auth::User();
        $transaction = DonationTransaction::where('id', $trans_id)->first();
        if($transaction->collection->user->id !== $user->id) {
            return redirect()->back();
        }
        return view('gh/confirm_payment', compact('transaction'));
    }
    public function storeConfirmReceivedPayment(Request $request, $trans_id) {
        $user = Auth::User();
        $transaction = DonationTransaction::where('id', $trans_id)->first();
        if($transaction->collection->user->id !== $user->id) {
            return redirect()->back();
        }
        $transaction->receiverConfirmed = 1;
        $transaction->save();

        //we need to check if this person has received all amounts
        //get the collections
        $collection = DonationHelp::where(['userID' => $user->id
            , 'id'=> $transaction->collectionHelpID, 'phGh'=> 'gh'])->first();
        //query all other trasnsactions
        $gh_sum = DonationTransaction::where('collectionHelpID'
            , $transaction->collectionHelpID)->sum('amount');
        if($transaction->collection->amount == $gh_sum){
            //full payment received
            $transaction->collection->status = DonationHelp::$SLIP_WITHDRAWAL;
        }else{
            $transaction->collection->status = DonationHelp::$SLIP_PARTIALWITHDRAWAL;
        }
        $transaction->collection->save();

        //check if the donation has fully been paid
        $ph_sum = DonationTransaction::where('donationHelpID'
            , $transaction->donationHelpID)->sum('amount');
        if((int) $transaction->donation->amount == (int) $ph_sum){
            //check if thats the first donation then credit the reg bonus
            $donation = DonationHelp::where('userID', $transaction->donation->user->id)
            ->where('phGh', 'ph')->where('status', DonationHelp::$SLIP_CONFIRMED)->first();
            if(!$donation) {
                if($transaction->donation->user->isBonusCollected == 0){
                    if(strtolower($transaction->donation->paymentType) == 'bank') {
                        //credit the user with bonus
                        $bonus_amount = ApplicationHelpers::getRegistrationBonusInNaira(
                        $transaction->donation->amount);
                        $transaction->donation->user->bonusType = 'bank';
                    }elseif(strtolower($transaction->donation->paymentType) == 'bitcoin'){
                        $bonus_amount = ApplicationHelpers::getRegistrationBonusInDollar(
                        $transaction->donation->amount);
                        $transaction->donation->user->bonusType = 'bitcoin';
                    }
                    $transaction->donation->user->isBonusCollected = 1;
                    $transaction->donation->user->bonusAmount = $bonus_amount;
                }
            }

            //full payment received
            $transaction->donation->status = DonationHelp::$SLIP_CONFIRMED;
            //credit points to the donor
            $transaction->donation->user->points = $transaction->donation->user->points + 5;
            $transaction->donation->user->save();
        }

        $transaction->donation->save();
        //credit point to the recipient for confirming the payment
        $user->points = $user->points + 5;
        $user->save();

        Session::flash('flash_message', 'Payment confirmation successful. +5 Points added');
        return redirect('confirm/gh/payment');
    }
    public function viewGHAttachment(Request $request, $trans_id) {
        $user = Auth::User();
        $transaction = DonationTransaction::where(
            ['id'=>$trans_id])->first();    
        if($transaction->collection->user->id !== $user->id ) {
            if (!$user->hasRole('superadmin'))
                return redirect()->back();
        }
        return view('ph/attachment', compact('transaction'));
    }
    public function flagAsPop(Request $request, $trans_id){
        $user = Auth::User();
        $transaction = DonationTransaction::where(
            ['id'=>$trans_id])->first();    
        if($transaction->collection->user->id !== $user->id) {
            return redirect()->back();
        }
        $transaction->fakePOP = 1;
        $transaction->save();

        Session::flash('flash_message', 'Operation successful.
         Please wait, The system will automatically rematch you within 72 hours while the support team 
         takes a look at the failed transaction. We are sorry for any inconvieniences. Be rest assured, 
         This will be resolved in a timely fashion');
        return redirect()->back();
    }
    public function paymentHistory(Request $request) {
        $user = Auth::User();
        $collections = DonationHelp::where('userID', $user->id)
            ->where('phGh', 'gh')->where(function($query) {
                $query->where('status', DonationHelp::$SLIP_WITHDRAWAL)
                ->orWhere('status', DonationHelp::$SLIP_PARTIALWITHDRAWAL);
            })->get();
        return view('gh/history', compact('collections'));
    }
    public function extendDate(Request $request, $trans_id) {
        $user = Auth::User();
        $transaction = DonationTransaction::where('id', $trans_id)->first();
        if($transaction->collection->user->id !== $user->id) {
            return redirect()->back()->withErrors('You dont have access to this operation');
        } 
        //add 24 hours to penalty date
        $transaction->penaltyDate = strtotime($transaction->penaltyDate) + (24 * 60 * 60);
        $transaction->save();
        //penalize the donor by subtracting the points
        $transaction->donation->user->points = $transaction->donation->user->points - 25;
        if($transaction->donation->user->points <= 0) {
            //block the account
            $transaction->donation->user->isBlocked = 1;
            //then change the transaction ticket
            $transaction->isDefaulted = 1;
        }
        $transaction->donation->user->save();
        Session::flash('flash_message', 'Successful extension of date');
        return redirect()->back();
    }
}
