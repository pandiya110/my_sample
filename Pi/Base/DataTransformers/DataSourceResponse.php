<?php

namespace CodePi\Base\DataTransformers;

class DataSourceResponse {

    public $success = TRUE;
    public $messageCode;
    public $status_code;
    protected $listMessages = [
        'S_NoDataFound' => 'No Records Found.',
        'E_WrongParams' => 'Wrong parameters passed.',
        'S_Login' => 'You have logged in successfully.',
        'S_Logout' => 'You have logged out successfully.',
        'E_Login' => 'Invalid Email or Password.',
        'S_UpdateProfilePic' => 'Profile image updated successfully.',
        'E_UpdateProfilePic' => 'User does not exists.',
        'S_UsersList' => 'List of Users',
        'S_UpdateProfilePic' => 'User profile image updated successfully.',
        'S_GetPermissions' => 'Permissions saved successfully.',
        'E_ForgotPassword' => 'We cannot find an account linked to the email address that you entered.',
        'S_ForgotPassword' => 'An email with password reset instructions has been sent to your email address.',
        'S_CreateUser' => 'Saved successfully.',
        'E_CreateUser' => 'Email is already exist. Try with another email.',
        'Validation' => 'Validation Errors',
        'S_ResetPasswordLink' => 'Password Updated successfully.',
        'E_ResetPasswordLink' => 'Failed to update Password. Please try again',
        'S_EventsList' => 'List of events.',
        'S_EventDetails' => 'Event Details.',
        'S_EventDivisions' => 'Event Divisions',
        'E_EventDetails' => 'No Records Found.',
        'S_EventsVendorsList' => 'Event vendors list.',
        'S_UserRegister' => 'Added successfully.',
        'S_AddEvents' => 'Promotion created. You can now add more details to the Promotion.',
        'S_EventsVendorsDetails' => 'Event vendors event details.',
        'S_Settings' => 'Settings updated.',
        'S_DeleteEventAttachment' => 'Deleted successfully.',
        'S_DeleteEvent' => 'Deleted successfully.',
        'E_DeleteEvent' => 'Failed. please try again.',
        'E_DeleteEventAttachment' => 'Failed. please try again.',
        'S_InviteVendorsList' => 'Invited Vendors List',
        'E_InviteVendorsList' => 'Invalid Event',
        'S_BrandsAdd' => 'Saved successfully',
        'E_BrandsAdd' => 'Name is already exist. Try with another Brand Name.',
        'S_BrandsList' => 'Brands List',
        'E_BrandsList' => 'Please Try Again',
        'S_SendInvitation' => 'Sent Inviation',
        'E_SendInvitation' => 'Try again',
        'S_EditEvent' => 'Updated successfully',
        'E_EditEvent' => 'Event name is already exist. Try with another Event name',
        'E_AddEvents' => 'Event name is already exist. Try with another Event name',
        'S_EventDivisionsBanners' => 'Saved successfully',
        'E_EventDetailsAuthorize' => 'You are unauthorized to view this promotion. Please Contact Admin',
        'E_UserLogout' => 'User already logged out from the application',
        'S_InviteVendorsRedirect' => 'User updated password',
        'E_InviteVendorsRedirect' => 'User not updated password',
        'S_EventSponsorshipDetails' => 'Event Sponsorship Details',
        'E_EventSponsorshipDetails' => 'Invalid,Please Try Again',
        'S_VendorSponsorshipSelect' => 'Successfully Selected Your Sponsorship',
        'E_VendorSponsorshipSelect' => 'Failed. Please Try Again',
        'S_ProductsDivisions' => 'Products divisions status saved successfully',
        'E_ProductsDivisions' => 'Failed. Please Try Again',
        'S_SponsorshipItems' => 'Sponsorship items status saved successfully',
        'E_SponsorshipItems' => 'Failed. Please Try Again',
        'S_EventArchive' => 'Event Moved To Archive',
        'E_EventArchive' => 'Failed. Please Try Again',
        'S_EventDuplicate' => 'Duplicate event created successfully',
        'E_EventDuplicate' => 'A Event with this name already exists. Try with another Event name.',
        'S_ResendActivation' => 'An email with link has been sent to your email address.',
        'E_ResendActivation' => 'Failed. Please Try Again',
        'S_MasterItem' => 'Master Item Have Been Added Successfully.',
        'E_MasterItem' => 'Failed. Please Try Again.',
        'Invalid_Access' => 'Invalid Operation Performed.',
        'S_MasterItems' => 'List of Master Items',
        'E_MasterItems' => 'Failed to get master items. Please try again.',
        'S_Create_Campaign'=>'New campaign created successfully',
        'E_Create_Campaign'=>'Failure to save campaign',
        
    ];
    public $result;

    function __construct($data, $messageCode, $success = TRUE, $errorCode = 200) {
        $this->success = $success;
        $this->data = $data;
        $this->messageCode = $messageCode;
        $this->status_code = $errorCode;
    }

    function formatMessage() {
        $message = isset($this->listMessages[$this->messageCode]) ? $this->listMessages[$this->messageCode] : $this->messageCode;
        
        return ['result' => [
                'success' => $this->success,
                'data' => $this->data,
                'message' => $message,
                'status_code' => $this->status_code
        ]];
    }

}
