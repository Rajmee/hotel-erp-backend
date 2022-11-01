<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface;
use App\Controllers\HRM\LeaveController;

// HRM MODULE
use App\Controllers\HRM\HolidayController;
use App\Controllers\HRM\EmployeeController;
use App\Controllers\HRM\LocationController;
use App\Controllers\Users\OrganizationUsers;
use Psr\Http\Message\ServerRequestInterface;

use App\Controllers\HRM\DesignationController;
use App\Controllers\Inventory\CategoryController;
use App\Controllers\Inventory\WarehouseController;
use App\Controllers\Inventory\WarehouseLocationController;
use App\Controllers\Inventory\ItemController;
use App\Controllers\Inventory\VoucherController;


## ==> Mehadi API <== ##
use App\Controllers\Permissions\PermissionController;
use App\Controllers\Purchase\SupplierController;
use App\Controllers\Purchase\QuotationController;
use App\Controllers\Purchase\InvoiceController;
use App\Controllers\HRM\DepartmentController;

## ==> Mehadi API <== ##

use App\Controllers\RoomManagement\Tower\TowerController;
use App\Controllers\FileUpload\FileUploadController;
use App\Controllers\RoomManagement\RoomCategory\RoomCategoryController;
use App\Controllers\RoomManagement\RoomType\RoomTypeController;
use App\Controllers\RoomManagement\RoomFacility\RoomFacilityController;
use App\Controllers\Customers\CustomerController;

// END OF HRM MODULE


## ==> Rabiul API <== ##

use App\Controllers\Settings\GeneralController;

return function (App $app) {

    $app->group("/auth", function ($app) {
        $app->post("/login", [\App\Controllers\Auth\AuthController::class,"login"]);
    });


    //** ========================> Rakibul Zone <==========================*/
    // Employee
    $app->get('/app/hrm/employee', [EmployeeController::class,'go']);
    $app->post('/app/hrm/employee', [EmployeeController::class,'go']);
    //Employee End

    //Location
    $app->get('/app/hrm/location', [LocationController::class,'go']);
    $app->post('/app/hrm/location', [LocationController::class,'go']);

    //Room Management
    $app->get('/app/roomManagement/tower', [TowerController::class,'go']);
    $app->post('/app/roomManagement/tower', [TowerController::class,'go']);

    //Room Types
    $app->get('/app/roomManagement/room_type', [RoomTypeController::class,'go']);
    $app->post('/app/roomManagement/room_type', [RoomTypeController::class,'go']);
    //Room Facilities
    $app->get('/app/roomManagement/room_facility', [RoomFacilityController::class,'go']);
    $app->post('/app/roomManagement/room_facility', [RoomFacilityController::class,'go']);
    //Room Category
    $app->get('/app/roomManagement/roomCategory', [RoomCategoryController::class,'go']);
    $app->post('/app/roomManagement/roomCategory', [RoomCategoryController::class,'go']);

    //File Uploader
    $app->get('/app/uploader/upload', [FileUploadController::class,'go']);
    $app->post('/app/uploader/upload', [FileUploadController::class,'go']);
    //Customer
    $app->get('/app/customers/addNewCustomer', [CustomerController::class,'go']);
    $app->post('/app/customers/addNewCustomer', [CustomerController::class,'go']);

    //** ========================> End Rakibul Zone <==========================*/
  

    //** ========================> Mehadi Zone <==========================*/

    ######### -> Permission Controller Start
    $app->get('/app/permissions/permission', [PermissionController::class,'go']);
    $app->post('/app/permissions/permission', [PermissionController::class,'go']);
    ######### -> Permission Controller End

    ######### -> Supplier Controller Start
    $app->get('/app/purchase/supplier', [SupplierController::class,'go']);
    $app->post('/app/purchase/supplier', [SupplierController::class,'go']);
    ######### -> Supplier Controller End

    ######### ->Quotations Controller Start
    $app->get('/app/purchase/quotation', [QuotationController::class,'go']);
    $app->post('/app/purchase/quotation', [QuotationController::class,'go']);
    ######### ->Quotations Controller End

    ######### ->Invoice Controller Start
    $app->get('/app/purchase/invoice', [InvoiceController::class,'go']);
    $app->post('/app/purchase/invoice', [InvoiceController::class,'go']);
    ######### ->Invoice Controller End

    ######### -> Departments
    $app->get('/app/hrm/departments', [DepartmentController::class,'go']);
    $app->post('/app/hrm/departments', [DepartmentController::class,'go']);

    //** =========================> Mehadi Zone <=========================*/

    //** =========================> Hemel Zone <=========================*/
    
    // Designations
    $app->get('/app/hrm/designations', [DesignationController::class,'go']);
    $app->post('/app/hrm/designations', [DesignationController::class,'go']);

    // Holidays
    $app->get('/app/hrm/holidays', [HolidayController::class,'go']);
    $app->post('/app/hrm/holidays', [HolidayController::class,'go']);

    // Leaves
    $app->get('/app/hrm/leaves', [LeaveController::class,'go']);
    $app->post('/app/hrm/leaves', [LeaveController::class,'go']);

    //Inventory Management
    $app->get('/app/inventory/category', [CategoryController::class,'go']);
    $app->post('/app/inventory/category', [CategoryController::class,'go']);
    //Warehouse Management
    $app->get('/app/inventory/warehouse', [WarehouseController::class,'go']);
    $app->post('/app/inventory/warehouse', [WarehouseController::class,'go']);
    //Warehouse Location Management
    $app->get('/app/inventory/warehouse/location', [WarehouseLocationController::class,'go']);
    $app->post('/app/inventory/warehouse/location', [WarehouseLocationController::class,'go']);
    //Inventory Item
    $app->get('/app/inventory/items', [ItemController::class,'go']);
    $app->post('/app/inventory/items', [ItemController::class,'go']);
    //consumption-voucher
    $app->get('/app/inventory/consumption-voucher', [VoucherController::class,'go']);
    $app->post('/app/inventory/consumption-voucher', [VoucherController::class,'go']);

    //** =========================> End Hemel Zone <=========================*/


    //** =========================> Rabiul Zone <=========================*/ 
    // General setting
    $app->get('/app/settings/general', [GeneralController::class,'go']);
    $app->post('/app/settings/general', [GeneralController::class,'go']);

    //** =========================> End Rabiul Zone <=========================*/




    $app->get('/', function (
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $response->getBody()->write('Welcome to ManageBeds');

        return $response;
    });
};
