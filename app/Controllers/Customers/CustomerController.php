<?php

namespace  App\Controllers\Customers;

use App\Auth\Auth;
use App\Validation\Validator;
use App\Response\CustomResponse;
use App\Models\Users\ClientUsers;
use App\Models\Customers\Customer;
use Illuminate\Database\Capsule\Manager as DB;
use App\Requests\CustomRequestHandler;
use Respect\Validation\Validator as v;

use App\Models\Customers\CustomerBooking;
use App\Models\Customers\CustomerBookingGrp;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class CustomerController
{

    protected $customResponse;

    protected $validator;

    protected $params;
    protected $responseMessage;
    protected $outputData;
    protected $success;
    protected $user;

    public function __construct()
    {
        $this->customResponse = new CustomResponse();
        $this->user = new ClientUsers();
        $this->validator = new Validator();
        $this->customer = new Customer();
        $this->customerBookingGrp = new CustomerBookingGrp();
        $this->customerBooking = new CustomerBooking();

        $this->responseMessage = "";
        $this->outputData = [];
        $this->success = false;
    }

    public function go(Request $request, Response $response)
    {
        $this->params = CustomRequestHandler::getAllParams($request);
        $action = isset($this->params->action) ? $this->params->action : "";

        $this->user = Auth::user($request);

        switch ($action) {
         
            case 'createCustomer':
                $this->createCustomer($request, $response);
                break;                 
            case 'customerInfo':
                $this->customerInfo();
                break;                 
            default:
                $this->responseMessage = "Invalid request!";
                return $this->customResponse->is400Response($response, $this->responseMessage);
                break;
        }

        if (!$this->success) {
            return $this->customResponse->is400Response($response, $this->responseMessage, $this->outputData);
        }

        return $this->customResponse->is200Response($response, $this->responseMessage, $this->outputData);
    }

    public function createCustomer(Request $request){
        $this->validator->validate($request, [
            "mobile"=>v::notEmpty(),
            "contact_type"=>v::notEmpty(),
            "title"=>v::notEmpty(),
            "fName"=>v::notEmpty(),
            "lName"=>v::notEmpty(),
            "gender"=>v::notEmpty(),
            "birth_date"=>v::notEmpty(),
            "nationality"=>v::notEmpty(),
            "country_id"=>v::notEmpty(),
            "state_id"=>v::notEmpty(),
            "city_id"=>v::notEmpty(),
            "pin_code"=>v::notEmpty(),
            "arrival_from"=>v::notEmpty(),
            "address"=>v::notEmpty(),
            "status"=>v::notEmpty(),

            "room_category_id"=>v::notEmpty(),
            "room_id"=>v::notEmpty(),
            "checkout_type"=>v::notEmpty(),

            "date_from"=>v::notEmpty(),

            
         ]);

         if($this->params->checkout_type === 'hourly'){
            $this->validator->validate($request, [
                "checkout_hour"=>v::notEmpty()
            ]);
         }

         if($this->params->adults < 0){
            $this->success = false;
            $this->responseMessage = 'Enter no.of adults !';
            return;
         }
         if($this->params->childs < 0){
            $this->success = false;
            $this->responseMessage = 'Enter no.of childs !';
            return;
         }


        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $existcustomer = $this->customer->where(["mobile"=>$this->params->mobile])->first();

        if($existcustomer){
            $customer = $existcustomer;
        }
        else{

            $customer = $this->customer;
        }

        //customer info
        $customer->mobile = $this->params->mobile;
        $customer->contact_type = $this->params->contact_type;
        $customer->title = $this->params->title;
        $customer->first_name = $this->params->fName;
        $customer->last_name = $this->params->lName;
        $customer->gender = $this->params->gender;

        if($this->params->birth_date){
            $customer->dob = date('Y-m-d',strtotime($this->params->birth_date));
        }
        if($this->params->anniversary_date !== null){
            $customer->anniversary_date = date('Y-m-d',strtotime($this->params->anniversary_date));
        }

        $customer->nationality = $this->params->nationality;
        $customer->country_id = $this->params->country_id;
        $customer->state_id = $this->params->state_id;
        $customer->city_id = $this->params->city_id;
        $customer->pin_code = $this->params->pin_code;
        $customer->arrival_from = $this->params->arrival_from;
        $customer->address = $this->params->address;
        $customer->status = $this->params->status;
        $customer->created_by = $this->user->id;
        $customer->save();

        //if customer created, then create customer booking group
        if($customer){
            $customer_booking_grp = $this->customerBookingGrp;

            $customer_booking_grp->customer_id = $customer->id;
            $customer_booking_grp->checkout_type = $this->params->checkout_type;

            if($this->params->checkout_type === 'hourly'){

                $customer_booking_grp->checkout_hour = $this->params->checkout_hour;
            }

            $customer_booking_grp->date_from = date('Y-m-d',strtotime($this->params->date_from));

            // Loop between timestamps, 24 hours at a time. Inserting data into customer_bookings
            $begin = $customer_booking_grp->date_from;

            if($this->params->date_to !== null){
                $customer_booking_grp->date_to = date('Y-m-d',strtotime($this->params->date_to));
                $end = $customer_booking_grp->date_to;
            }
            else{
                $end = $begin;
            }
            $arr = array();

            for($i = $begin; $i <= $end; $i=date('Y-m-d', strtotime("+1 day", strtotime($i)))){
                $arr[]= array(
                    'customer_id'=>$customer->id,
                    'room_id'=>$this->params->room_id,
                    'room_category_id'=>$this->params->room_category_id,
                    'date'=> date('Y-m-d',strtotime($i)),
                    'adults'=>$this->params->adults,
                    'childs'=>$this->params->childs,
                );
            }
            //end customer booking
            
            $customer_booking_grp->checkin_at = date("Y-m-d h:i:s");
            $customer_booking_grp->status = $this->params->booking_grp_status;
            $customer_booking_grp->created_by = $this->user->id;

            if(count($arr) > 0){

                $customer_booking_grp->save();
            }

        }

        if($customer_booking_grp){
            DB::table('customer_bookings')->insert($arr);
        }
        

        $this->responseMessage = "New Customer has been created successfully";
        $this->outputData = $customer;
        $this->success = true;
    }

    //Fetching customer info
    public function customerInfo(){
        $customer = $this->customer->where(["mobile"=>$this->params->mobile])->first();

        $this->responseMessage = "Customer Info has been fetched successfully";
        $this->outputData = $customer;

        $this->outputData['country']=$customer->country;
        $this->outputData['state']=$customer->state;
        $this->outputData['city']=$customer->city;
        
        $this->success = true;
        
    }

}