<?php
namespace Tickets\Air;


class Segment {

    /** @var Craft Транспорт */
    private $craft;
    /** @var Carrier Перевозчик */
    private $carrier;
    /** @var Location точка старта */
    private $origin;
    /** @var  Location точка финиша */
    private $destination;

    /** @var string класс обслуживания */
    private $class;
    /** integer Длительность перелета в секундах */
    private $duration = 0;
    /** string Номер рейса */
    private $flightNumber = '';

//$aSegmentData = array(
//'aOrigin' => array(
//'iUTCTimestamp' => strtotime($oSegment->DepartureTime),
//'iTimeOffset' => Air_Interface_Travelport_Utils::getTimeOffset($oSegment->DepartureTime),
//'sTimeString' => $oSegment->DepartureTime,
//'sAirportCode' => $oSegment->Origin,
//'sTerminalCode' => $aFlightDetails[$oSegment->FlightDetailsRef->Key]->OriginTerminal
//),
//'aDestination' => array(
//'iUTCTimestamp' => strtotime($oSegment->ArrivalTime),
//'iTimeOffset' => Air_Interface_Travelport_Utils::getTimeOffset($oSegment->ArrivalTime),
//'sTimeString' => $oSegment->ArrivalTime,
//'sAirportCode' => $oSegment->Destination,
//'sTerminalCode' => $aFlightDetails[$oSegment->FlightDetailsRef->Key]->DestinationTerminal
//)
//);

} 