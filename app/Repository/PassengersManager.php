<?php

namespace STS\Repository; 

use STS\Entities\Trip;
use STS\Entities\Passenger;
use STS\User;
use Validator;

class PassengerManager
{
    protected $friendsManager;
    public function __construct()
    {
        $this->friendsManager = new FriendsManager();
    }

    public function add ($user, $trip)
    {
        $p = new Passenger;
        $p->user_id = $user->id;
        $p->passenger_type = Passenger::TYPE_PASAJERO;
        $p->request_state = Passenger::STATE_PENDIENTE;
        return $trip->passenger()->save($p);
    }

    public function remove ($user, $trip)
    {
        return $trip->passenger()->whereUserId($user->id)->delete();
    }
 
    public function find ($user, $trip)
    {
        return $trip->passenger()->whereUserId($user->id)->first();
    }

    public function rideUp($user, $trip)
    {
        if ($trip->disponibles() > 0) {
            $p = $this->find($user, $trip);
            if ($p && $p->request_state == Passenger::STATE_RECHAZADO ) {
                $p->request_state = Passenger::STATE_PENDIENTE;
                return $p->save();
            } else if (is_null($p)) {
                return $this->add($user, $trip);
            }
        }
        return null;
    }

    public function rideDown($user, $trip)
    { 
        $p = $this->find($user, $trip);
        if ($p && $p->request_state == Passenger::STATE_ACEPTADO ) {
            $p->delete();
            return true;
        }
        return null;
    }

    public function confirmar($user, $trip)
    {
        $p = $this->find($user, $trip);
        if ($p && $p->request_state == Passenger::STATE_PENDIENTE ) {
            if ($trip->disponibles() > 0) {
                $p->request_state = Passenger::STATE_ACEPTADO;
                return $p->save();
            } else {
                return null;
            }
        }
        return null;
    }

    public function rechazar($user, $trip)
    {
        $p = $this->find($user, $trip);
        if ($p && $p->request_state == Passenger::STATE_PENDIENTE ) { 
            $p->request_state = Passenger::STATE_RECHAZADO;
            return $p->save();
        }
        return null;
    }

}