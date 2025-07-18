<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $id_number
 * @property string|null $birthday
 * @property string|null $gender
 * @property array $phone_number
 * @property array $addresses
 * @property string|null $contact_person
 * @property string|null $contact_phone
 * @property string|null $contact_relationship
 * @property string|null $email
 * @property string|null $wheelchair
 * @property string|null $stair_climbing_machine
 * @property string|null $ride_sharing
 * @property string|null $identity
 * @property string|null $note
 * @property string|null $a_mechanism
 * @property string|null $a_manager
 * @property string|null $special_status
 * @property string $status 狀態：開案中、暫停中、已結案
 * @property string|null $county_care
 * @property string|null $service_company
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerEvent> $events
 * @property-read int|null $events_count
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereAManager($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereAMechanism($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereAddresses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereContactRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCountyCare($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereIdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereIdentity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereRideSharing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereServiceCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereSpecialStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereStairClimbingMachine($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereWheelchair($value)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $customer_id
 * @property string|null $event_date
 * @property string $event
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Customer $customer
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent whereEventDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerEvent whereUpdatedAt($value)
 */
	class CustomerEvent extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $id_number
 * @property string|null $fleet_number
 * @property string|null $plate_number
 * @property string|null $car_color
 * @property string|null $car_brand
 * @property string|null $car_vehicle_style
 * @property string|null $lc_company
 * @property string|null $order_type
 * @property string|null $service_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Driver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver query()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereCarBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereCarColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereCarVehicleStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereFleetNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereIdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLcCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereOrderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver wherePlateNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereServiceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereUpdatedAt($value)
 */
	class Driver extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property int|null $driver_id
 * @property string $customer_name
 * @property string $customer_id_number
 * @property string $customer_phone
 * @property string|null $driver_name
 * @property string|null $driver_fleet_number
 * @property string|null $driver_plate_number
 * @property string|null $order_type
 * @property string|null $service_company
 * @property string $ride_date
 * @property string $ride_time
 * @property string|null $pickup_county
 * @property string|null $pickup_district
 * @property string $pickup_address
 * @property string|null $pickup_lat
 * @property string|null $pickup_lng
 * @property string|null $dropoff_county
 * @property string|null $dropoff_district
 * @property string $dropoff_address
 * @property string|null $dropoff_lat
 * @property string|null $dropoff_lng
 * @property int $wheelchair
 * @property int $stair_machine
 * @property int $companions
 * @property int|null $carpool_customer_id
 * @property string|null $carpool_name
 * @property string|null $carpool_id
 * @property string|null $remark
 * @property string $created_by
 * @property string|null $identity
 * @property string|null $carpool_with
 * @property int $special_order
 * @property string|null $special_status
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Driver|null $driver
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCarpoolCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCarpoolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCarpoolName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCarpoolWith($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCompanions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerIdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDriverFleetNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDriverName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDriverPlateNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDropoffAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDropoffCounty($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDropoffDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDropoffLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDropoffLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIdentity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePickupAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePickupCounty($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePickupDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePickupLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePickupLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereRideDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereRideTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereServiceCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSpecialOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSpecialStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStairMachine($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereWheelchair($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

